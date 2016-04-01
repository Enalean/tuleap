<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
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

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy {

    public function __construct(
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        Logger $logger
    ) {
        $this->artifact_id_mapping = $artifact_id_mapping;
        $this->logger = $logger;
    }

    /**
     * Extract Field data from XML input
     *
     * @param Tracker_FormElement_Field $field
     * @param SimpleXMLElement $field_change
     *
     * @return array
     */
    public function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change, PFUser $submitted_by) {
        $artifact_links = array();
        $natures = array();
        foreach ($field_change as $artifact_link) {
            $linked_artifact_id = (int)(string) $artifact_link;
            if($this->artifact_id_mapping->containsSource($linked_artifact_id) ) {
                $link = $this->artifact_id_mapping->get($linked_artifact_id);
                $artifact_links[] = $link;
                $natures[$link] = (string) $artifact_link['nature'];
            } else {
                $this->logger->error("Could not find artifact with id=$linked_artifact_id in xml.");
            }
        }
        $artifact_links = join(',', $artifact_links);
        return array("new_values" => $artifact_links, "natures" => $natures);
    }
}
