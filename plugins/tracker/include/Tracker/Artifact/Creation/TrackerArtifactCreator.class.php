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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Creation;

use DataAccessException;
use DataAccessQueryException;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_FieldsValidator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\CreateInitialChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I create artifact from the request in a Tracker
 */
class TrackerArtifactCreator
{
    private Tracker_ArtifactDao $artifact_dao;

    public function __construct(
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        private readonly CreateInitialChangeset $changeset_creator,
        private readonly VisitRecorder $visit_recorder,
        private readonly \Psr\Log\LoggerInterface $logger,
        private readonly DBTransactionExecutor $db_transaction_executor,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
        $this->artifact_dao = $artifact_factory->getDao();
    }

    public static function build(
        CreateInitialChangeset $changeset_creator_base,
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        LoggerInterface $logger,
    ): self {
        return new self(
            Tracker_ArtifactFactory::instance(),
            $fields_validator,
            $changeset_creator_base,
            new VisitRecorder(new RecentlyVisitedDao()),
            $logger,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            \EventManager::instance(),
        );
    }

    /**
     * Add an artifact without its first changeset to a tracker
     * The artifact must be completed by writing its first changeset
     */
    public function createBare(Tracker $tracker, PFUser $user, int $submitted_on): ?Artifact
    {
        $artifact = $this->getBareArtifact($tracker, $submitted_on, (int) $user->getId(), 0);
        $success  = $this->insertArtifact($tracker, $user, $artifact, $submitted_on);
        if (! $success) {
            return null;
        }
        return $artifact;
    }

    /**
     * Creates the first changeset for a bare artifact.
     */
    public function createFirstChangeset(
        Artifact $artifact,
        InitialChangesetValuesContainer $changeset_values,
        PFUser $user,
        int $submitted_on,
        bool $send_notification,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $tracker_import_config,
    ): ?Tracker_Artifact_Changeset {
        $validation_context = new NullChangesetValidationContext();
        $are_fields_valid   = $this->fields_validator->validate(
            $artifact,
            $user,
            $changeset_values->getFieldsData(),
            $validation_context
        );
        if (! $are_fields_valid) {
            return null;
        }

        return $this->createFirstChangesetNoValidation(
            $artifact,
            $changeset_values,
            $user,
            $submitted_on,
            $send_notification,
            $url_mapping,
            $tracker_import_config,
            $validation_context
        );
    }

    /**
     * Creates the first changeset but do not check the fields because we
     * already have checked them.
     */
    private function createFirstChangesetNoValidation(
        Artifact $artifact,
        InitialChangesetValuesContainer $changeset_values,
        PFUser $user,
        int $submitted_on,
        bool $send_notification,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $tracker_import_config,
        ChangesetValidationContext $context,
    ): ?Tracker_Artifact_Changeset {
        $changeset_id = $this->db_transaction_executor->execute(
            function () use (
                $artifact,
                $changeset_values,
                $user,
                $submitted_on,
                $url_mapping,
                $tracker_import_config,
                $context
            ) {
                return $this->changeset_creator->create(
                    $artifact,
                    $changeset_values->getFieldsData(),
                    $user,
                    (int) $submitted_on,
                    $url_mapping,
                    $tracker_import_config,
                    $context
                );
            }
        );
        if (! $changeset_id) {
            return null;
        }

        $changeset = $this->createNewChangeset($changeset_id, $artifact, $user);

        $this->event_dispatcher->dispatch(
            new ArtifactCreated($artifact, $changeset, $user)
        );

        if (! $tracker_import_config->isFromXml()) {
            $changeset->executePostCreationActions($send_notification);
        }

        return $changeset;
    }

    public function create(
        Tracker $tracker,
        InitialChangesetValuesContainer $changeset_values,
        PFUser $user,
        int $submitted_on,
        bool $send_notification,
        bool $should_visit_be_recorded,
        ChangesetValidationContext $context,
    ): ?Artifact {
        $artifact = $this->getBareArtifact($tracker, $submitted_on, (int) $user->getId(), 0);

        $fields_data = $changeset_values->getFieldsData();

        if (! $this->fields_validator->validate($artifact, $user, $fields_data, $context)) {
            $this->logger->debug(
                sprintf('Creation of artifact in tracker #%d failed: fields are not valid', $tracker->getId())
            );
            return null;
        }

        if (! $this->insertArtifact($tracker, $user, $artifact, $submitted_on)) {
            return null;
        }

        $url_mapping = new CreatedFileURLMapping();
        if (
            ! $this->createFirstChangesetNoValidation(
                $artifact,
                $changeset_values,
                $user,
                $submitted_on,
                $send_notification,
                $url_mapping,
                new TrackerNoXMLImportLoggedConfig(),
                $context
            )
        ) {
            $this->logger->debug(
                sprintf('Reverting the creation of artifact in tracker #%d failed: changeset creation failed', $tracker->getId())
            );
            $this->revertBareArtifactInsertion($artifact);
            return null;
        }

        if ($should_visit_be_recorded) {
            $this->visit_recorder->record($user, $artifact);
        }

        return $artifact;
    }

    private function revertBareArtifactInsertion(Artifact $artifact): void
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
        Artifact $artifact,
        int $submitted_on,
    ): bool {
        $use_artifact_permissions = 0;
        $id                       = $this->artifact_dao->create($tracker->getId(), $user->getId(), $submitted_on, $use_artifact_permissions);
        if (! $id) {
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
        Artifact $artifact,
        int $submitted_on,
        int $submitted_by,
    ): int {
        $use_artifact_permissions = 0;

        return $this->artifact_dao->createWithId(
            $artifact->getId(),
            $tracker->getId(),
            $submitted_by,
            $submitted_on,
            $use_artifact_permissions
        );
    }

    public function createBareWithAllData(Tracker $tracker, int $artifact_id, int $submitted_on, int $submitted_by): ?Artifact
    {
        try {
            $artifact = $this->getBareArtifact($tracker, $submitted_on, $submitted_by, $artifact_id);
            $this->insertArtifactWithAllData($tracker, $artifact, $submitted_on, $submitted_by);

            return $artifact;
        } catch (DataAccessException $exception) {
            return null;
        }
    }

    private function getBareArtifact(Tracker $tracker, int $submitted_on, int $submitted_by, int $artifact_id): Artifact
    {
        $artifact = $this->artifact_factory->getInstanceFromRow(
            [
                'id'                       => $artifact_id,
                'tracker_id'               => $tracker->getId(),
                'submitted_by'             => $submitted_by,
                'submitted_on'             => $submitted_on,
                'use_artifact_permissions' => 0,
            ]
        );

        $artifact->setTracker($tracker);
        return $artifact;
    }

    protected function createNewChangeset(int $changeset_id, Artifact $artifact, PFUser $user): Tracker_Artifact_Changeset
    {
        $changeset = new Tracker_Artifact_Changeset(
            $changeset_id,
            $artifact,
            $artifact->getSubmittedBy(),
            $artifact->getSubmittedOn(),
            $user->getEmail()
        );
        return $changeset;
    }
}
