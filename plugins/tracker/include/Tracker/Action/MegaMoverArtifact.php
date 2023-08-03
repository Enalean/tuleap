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
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLImport;
use Tracker_XML_Exporter_ArtifactXMLExporter;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionManager;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\Tracker\XML\Updater\MoveChangesetXMLSemanticUpdater;

final class MegaMoverArtifact implements MoveArtifact, CheckMoveArtifact
{
    public function __construct(
        private readonly ArtifactsDeletionManager $artifacts_deletion_manager,
        private readonly Tracker_XML_Exporter_ArtifactXMLExporter $xml_exporter,
        private readonly MoveChangesetXMLSemanticUpdater $xml_updater,
        private readonly Tracker_Artifact_XMLImport $xml_import,
        private readonly Tracker_Artifact_PriorityManager $artifact_priority_manager,
        private readonly BeforeMoveArtifact $before_move_artifact,
        private readonly DBTransactionExecutor $transaction_executor,
    ) {
    }

    public function checkMoveIsPossible(
        Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        try {
            $this->before_move_artifact->artifactCanBeMoved($artifact->getTracker(), $target_tracker, $feedback_field_collector);
            $this->getUpdatedXML($artifact, $target_tracker, $user, $feedback_field_collector);
        } catch (MoveArtifactSemanticsException $exception) {
            $this->artifact_priority_manager->rollback();
            throw $exception;
        }
    }

    public function move(
        Artifact $artifact,
        Tracker $destination_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector,
        LoggerInterface $logger,
    ): int {
        if (! $destination_tracker->getProject()->isActive()) {
            throw new MoveArtifactTargetProjectNotActiveException();
        }

        return $this->transaction_executor->execute(function () use ($artifact, $destination_tracker, $user, $feedback_field_collector, $logger) {
            $this->checkMoveIsPossible($artifact, $destination_tracker, $user, $feedback_field_collector);

            $xml_artifacts = $this->getUpdatedXML($artifact, $destination_tracker, $user, $feedback_field_collector);

            $global_rank = $this->artifact_priority_manager->getGlobalRank($artifact->getId());
            $limit       = $this->artifacts_deletion_manager->deleteArtifactBeforeMoveOperation($artifact, $user, $destination_tracker);

            if (! $this->processMove($xml_artifacts->artifact, $destination_tracker, $global_rank, $user, $logger)) {
                throw new MoveArtifactNotDoneException();
            }

            return $limit;
        });
    }

    /**
     * @return SimpleXMLElement
     */
    private function getUpdatedXML(
        Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector,
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

    private function processMove(SimpleXMLElement $artifact_xml, Tracker $tracker, int $global_rank, PFUser $user, LoggerInterface $logger): bool
    {
        $tracker->getWorkflow()->disable();

        $moved_artifact = $this->xml_import->importArtifactWithAllDataFromXMLContentInAMoveContext(
            $tracker,
            $artifact_xml,
            $user,
            false,
            [],
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $logger
        );

        if (! $moved_artifact) {
            return false;
        }

        $this->artifact_priority_manager->putArtifactAtAGivenRank($moved_artifact, $global_rank);
        return true;
    }

    private function getXMLRootNode(): SimpleXMLElement
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }
}
