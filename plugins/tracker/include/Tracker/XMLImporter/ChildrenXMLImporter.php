<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_XMLImporter_ChildrenXMLImporter {

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_Artifact_XMLImport
     */
    private $xml_importer;

    public function __construct(Tracker_Artifact_XMLImport $xml_importer, TrackerFactory $tracker_factory) {
        $this->xml_importer    = $xml_importer;
        $this->tracker_factory = $tracker_factory;
    }

    public function importChildren(
        Tracker_XMLImporter_ArtifactImportedMapping $artifacts_imported_mapping,
        SimpleXMLElement $xml_artifacts,
        $extraction_path,
        Tracker_XMLExporter_ChildrenCollector $children_collector
    ) {
        for ($i = 1 ; $i < count($xml_artifacts->artifact) ; $i++) {
            $xml_artifact = $xml_artifacts->artifact[$i];
            $tracker = $this->tracker_factory->getTrackerById((int) $xml_artifact['tracker_id']);
            if (! $tracker) {
                throw new Tracker_XMLImporter_TrackerIdNotDefinedException();
            }
            $artifact = $this->xml_importer->importOneArtifactFromXML(
                $tracker,
                $xml_artifact,
                $extraction_path
            );
            if ($artifact) {
                $artifacts_imported_mapping->add((int) $xml_artifact['id'], $artifact->getId());
            }
        }

        $this->createLinksBetweenArtifacts($artifacts_imported_mapping, $children_collector);
    }

    private function createLinksBetweenArtifacts(
            Tracker_XMLImporter_ArtifactImportedMapping $artifacts_imported_mapping,
            Tracker_XMLExporter_ChildrenCollector $children_collector
    ) {
        FIXME;
    }
}
