<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLImport;
use Tracker_XML_Exporter_ArtifactXMLExporter;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionManager;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\Tracker\XML\Updater\UpdateMoveChangesetXMLDuckTyping;

final class MegaMoverArtifactByDuckTyping implements MoveArtifactByDuckTyping
{
    public function __construct(
        private readonly ArtifactsDeletionManager $artifacts_deletion_manager,
        private readonly Tracker_XML_Exporter_ArtifactXMLExporter $xml_exporter,
        private readonly UpdateMoveChangesetXMLDuckTyping $xml_updater,
        private readonly Tracker_Artifact_PriorityManager $artifact_priority_manager,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly Tracker_Artifact_XMLImport $xml_import,
    ) {
    }

    public function move(Artifact $artifact, Tracker $source_tracker, Tracker $destination_tracker, PFUser $user, DuckTypedMoveFieldCollection $field_collection): int
    {
        if (! $destination_tracker->getProject()->isActive()) {
            throw new MoveArtifactTargetProjectNotActiveException();
        }

        return $this->transaction_executor->execute(function () use ($artifact, $source_tracker, $destination_tracker, $user, $field_collection) {
            $xml_artifacts = $this->getUpdatedXML($artifact, $source_tracker, $user, $field_collection);

            $global_rank = $this->artifact_priority_manager->getGlobalRank($artifact->getId());
            $limit       = $this->artifacts_deletion_manager->deleteArtifactBeforeMoveOperation($artifact, $user, $destination_tracker);

            if (! $this->processMove($xml_artifacts->artifact, $destination_tracker, (int) $global_rank, $user, $field_collection->mapping_fields)) {
                throw new MoveArtifactNotDoneException();
            }

            return $limit;
        });
    }

    private function getUpdatedXML(
        Artifact $artifact,
        Tracker $source_tracker,
        PFUser $user,
        DuckTypedMoveFieldCollection $field_collection,
    ): \SimpleXMLElement {
        $xml_artifacts = $this->getXMLRootNode();
        $this->xml_exporter->exportFullHistory(
            $xml_artifacts,
            $artifact
        );
        $this->xml_updater->updateFromDuckTypingCollection(
            $user,
            $xml_artifacts->artifact,
            $artifact->getSubmittedByUser(),
            $artifact->getSubmittedOn(),
            time(),
            $field_collection,
            $source_tracker
        );

        return $xml_artifacts;
    }

    private function getXMLRootNode(): SimpleXMLElement
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }

    /**
     * @throws \Tracker_Artifact_Exception_XMLImportException
     *
     */
    private function processMove(SimpleXMLElement $artifact_xml, Tracker $tracker, int $global_rank, PFUser $user, array $field_mapping): bool
    {
        $tracker->getWorkflow()->disable();

        $moved_artifact = $this->xml_import->importArtifactWithAllDataFromXMLContent($tracker, $artifact_xml, $user, true, $field_mapping);

        if (! $moved_artifact) {
            return false;
        }

        $this->artifact_priority_manager->putArtifactAtAGivenRank($moved_artifact, $global_rank);
        return true;
    }
}
