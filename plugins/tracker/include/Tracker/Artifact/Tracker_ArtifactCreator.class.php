<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

/**
 * I create artifact from the request in a Tracker
 */
class Tracker_ArtifactCreator //phpcs:ignore
{
    /** @var Tracker_ArtifactDao */
    private $artifact_dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_Artifact_Changeset_InitialChangesetCreatorBase */
    private $changeset_creator;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_Artifact_Changeset_InitialChangesetCreatorBase $changeset_creator,
        VisitRecorder $visit_recorder,
        \Psr\Log\LoggerInterface $logger,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->artifact_dao      = $artifact_factory->getDao();
        $this->artifact_factory  = $artifact_factory;
        $this->fields_validator  = $fields_validator;
        $this->changeset_creator = $changeset_creator;
        $this->visit_recorder    = $visit_recorder;
        $this->logger            = $logger;
        $this->db_transaction_executor = $db_transaction_executor;
    }

    /**
     * Add an artifact without its first changeset to a tracker
     * The artifact must be completed by writing its first changeset
     *
     * @return Tracker_Artifact|false false if an error occurred
     */
    public function createBare(Tracker $tracker, PFUser $user, $submitted_on)
    {
        $artifact = $this->getBareArtifact($tracker, $submitted_on, $user->getId(), 0);
        $success = $this->insertArtifact($tracker, $user, $artifact, $submitted_on, 0);
        if (!$success) {
            return false;
        }
        return $artifact;
    }

    /**
     * Creates the first changeset for a bare artifact.
     * @return Tracker_Artifact|false false if an error occurred
     */
    public function createFirstChangeset(
        Tracker $tracker,
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $user,
        $submitted_on,
        $send_notification,
        CreatedFileURLMapping $url_mapping
    ) {
        if (!$this->fields_validator->validate($artifact, $user, $fields_data)) {
            return;
        }

        return $this->createFirstChangesetNoValidation(
            $artifact,
            $fields_data,
            $user,
            $submitted_on,
            $send_notification,
            $url_mapping
        );
    }

    /**
     * Creates the first changeset but do not check the fields because we
     * already have checked them. This function is private
     */
    private function createFirstChangesetNoValidation(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $user,
        $submitted_on,
        $send_notification,
        CreatedFileURLMapping $url_mapping
    ) {
        $changeset_id = $this->db_transaction_executor->execute(function () use ($artifact, $fields_data, $user, $submitted_on, $url_mapping) {
            return $this->changeset_creator->create($artifact, $fields_data, $user, (int) $submitted_on, $url_mapping);
        });
        if (! $changeset_id) {
            return;
        }

        $changeset = new Tracker_Artifact_Changeset(
            $changeset_id,
            $artifact,
            $artifact->getSubmittedBy(),
            $artifact->getSubmittedOn(),
            $user->getEmail()
        );

        if ($send_notification) {
            $changeset->executePostCreationActions();
        }

        return $artifact;
    }

    /**
     * Add an artefact in the tracker
     *
     * @return Tracker_Artifact|false false if an error occurred
     */
    public function create(
        Tracker $tracker,
        array $fields_data,
        PFUser $user,
        $submitted_on,
        $send_notification
    ) {
        $artifact = $this->getBareArtifact($tracker, $submitted_on, $user->getId(), 0);

        if (!$this->fields_validator->validate($artifact, $user, $fields_data)) {
            $this->logger->debug(
                sprintf('Creation of artifact in tracker #%d failed: fields are not valid', $tracker->getId())
            );
            return false;
        }

        if (! $this->insertArtifact($tracker, $user, $artifact, $submitted_on, 0)) {
            return false;
        }

        $url_mapping = new CreatedFileURLMapping();
        if (! $this->createFirstChangesetNoValidation(
            $artifact,
            $fields_data,
            $user,
            $submitted_on,
            $send_notification,
            $url_mapping
        )) {
            $this->logger->debug(
                sprintf('Reverting the creation of artifact in tracker #%d failed: changeset creation failed', $tracker->getId())
            );
            $this->revertBareArtifactInsertion($artifact);
            return false;
        }

        if ($artifact !== false) {
            $this->visit_recorder->record($user, $artifact);
        }

        return $artifact;
    }

    private function revertBareArtifactInsertion(Tracker_Artifact $artifact)
    {
        $this->artifact_dao->delete($artifact->getId());
    }

    /**
     * Inserts the artifact into the database and returns it with its id set.
     * @return bool true on success or false if an error occurred
     */
    private function insertArtifact(
        Tracker $tracker,
        PFUser $user,
        Tracker_Artifact $artifact,
        $submitted_on,
        $use_artifact_permissions
    ) {
        $use_artifact_permissions = 0;
        $id = $this->artifact_dao->create($tracker->id, $user->getId(), $submitted_on, $use_artifact_permissions);
        if (!$id) {
            $this->logger->error(
                sprintf('Insert of an artifact in tracker #%d failed', $tracker->getId())
            );
            return false;
        }
        ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_CREATED);

        $artifact->setId($id);
        return true;
    }

    /**
     * @throws DataAccessException
     * @throws DataAccessQueryException
     */
    private function insertArtifactWithAllData(
        Tracker $tracker,
        Tracker_Artifact $artifact,
        $submitted_on,
        $submitted_by
    ) {
        $use_artifact_permissions = 0;

        return $this->artifact_dao->createWithId(
            $artifact->getId(),
            $tracker->id,
            $submitted_by,
            $submitted_on,
            $use_artifact_permissions
        );
    }

    /**
     * @return Tracker_Artifact|false if an error occured
     */
    public function createBareWithAllData(Tracker $tracker, $artifact_id, $submitted_on, $submitted_by)
    {
        try {
            $artifact = $this->getBareArtifact($tracker, $submitted_on, $submitted_by, $artifact_id);
            $this->insertArtifactWithAllData($tracker, $artifact, $submitted_on, $submitted_by);

            return $artifact;
        } catch (DataAccessException $exception) {
            return false;
        }
    }

    private function getBareArtifact(Tracker $tracker, $submitted_on, $submitted_by, $artifact_id)
    {
        $artifact = $this->artifact_factory->getInstanceFromRow(
            array(
                'id'                       => $artifact_id,
                'tracker_id'               => $tracker->id,
                'submitted_by'             => $submitted_by,
                'submitted_on'             => $submitted_on,
                'use_artifact_permissions' => 0,
            )
        );

        $artifact->setTracker($tracker);
        return $artifact;
    }
}
