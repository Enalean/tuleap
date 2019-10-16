<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLImport;
use Tracker_XML_Exporter_ArtifactXMLExporter;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionManager;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

class MoveArtifact
{

    /**
     * @var ArtifactsDeletionManager
     */
    private $artifacts_deletion_manager;

    /**
     * @var Tracker_XML_Exporter_ArtifactXMLExporter
     */
    private $xml_exporter;

    /**
     * @var MoveChangesetXMLUpdater
     */
    private $xml_updater;

    /**
     * @var Tracker_Artifact_XMLImport
     */
    private $xml_import;

    /**
     * @var Tracker_Artifact_PriorityManager
     */
    private $artifact_priority_manager;

    /**
     * @var BeforeMoveArtifact
     */
    private $before_move_artifact;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        ArtifactsDeletionManager $artifacts_deletion_manager,
        Tracker_XML_Exporter_ArtifactXMLExporter $xml_exporter,
        MoveChangesetXMLUpdater $xml_updater,
        Tracker_Artifact_XMLImport $xml_import,
        Tracker_Artifact_PriorityManager $artifact_priority_manager,
        BeforeMoveArtifact $before_move_artifact,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->artifacts_deletion_manager = $artifacts_deletion_manager;
        $this->xml_exporter               = $xml_exporter;
        $this->xml_updater                = $xml_updater;
        $this->xml_import                 = $xml_import;
        $this->artifact_priority_manager  = $artifact_priority_manager;
        $this->before_move_artifact       = $before_move_artifact;
        $this->transaction_executor       = $transaction_executor;
    }

    /**
     * @throws MoveArtifactSemanticsException
     */
    public function checkMoveIsPossible(
        Tracker_Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector
    ) {
        try {
            $this->before_move_artifact->artifactCanBeMoved($artifact->getTracker(), $target_tracker, $feedback_field_collector);
            $this->getUpdatedXML($artifact, $target_tracker, $user, $feedback_field_collector);
        } catch (MoveArtifactSemanticsException $exception) {
            $this->artifact_priority_manager->rollback();
            throw $exception;
        }
    }

    /**
     * @throws \Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException
     * @throws \Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException
     * @throws MoveArtifactNotDoneException
     * @throws MoveArtifactSemanticsException
     * @throws MoveArtifactTargetProjectNotActiveException
     */
    public function move(
        Tracker_Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector
    ) {
        if (! $target_tracker->getProject()->isActive()) {
            throw new MoveArtifactTargetProjectNotActiveException();
        }

        return $this->transaction_executor->execute(function () use ($artifact, $target_tracker, $user, $feedback_field_collector) {
            $this->checkMoveIsPossible($artifact, $target_tracker, $user, $feedback_field_collector);

            $xml_artifacts = $this->getUpdatedXML($artifact, $target_tracker, $user, $feedback_field_collector);

            $global_rank = $this->artifact_priority_manager->getGlobalRank($artifact->getId());
            $limit       = $this->artifacts_deletion_manager->deleteArtifactBeforeMoveOperation($artifact, $user);

            if (! $this->processMove($xml_artifacts->artifact, $target_tracker, $global_rank)) {
                throw new MoveArtifactNotDoneException();
            }

            return $limit;
        });
    }

    /**
     * @return SimpleXMLElement
     */
    private function getUpdatedXML(
        Tracker_Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector
    ) {
        $xml_artifacts = $this->getXMLRootNode();
        $this->xml_exporter->exportFullHistory(
            $xml_artifacts,
            $artifact
        );

        $this->xml_updater->update(
            $user,
            $artifact->getTracker(),
            $target_tracker,
            $xml_artifacts->artifact,
            $artifact->getSubmittedByUser(),
            $artifact->getSubmittedOn(),
            time(),
            $feedback_field_collector
        );

        return $xml_artifacts;
    }

    private function processMove(SimpleXMLElement $artifact_xml, Tracker $tracker, $global_rank)
    {
        $tracker->getWorkflow()->disable();

        $moved_artifact = $this->xml_import->importArtifactWithAllDataFromXMLContent($tracker, $artifact_xml);

        if (! $moved_artifact) {
            return false;
        }

        $this->artifact_priority_manager->putArtifactAtAGivenRank($moved_artifact, $global_rank);
        return true;
    }

    private function getXMLRootNode()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }
}
