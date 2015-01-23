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

class Tracker_XML_Importer_ChildrenXMLImporter {

    /**
     * @var Tracker_XML_ChildrenCollector
     */
    private $children_collector;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_Artifact_XMLImport
     */
    private $xml_importer;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(
        Tracker_Artifact_XMLImport $xml_importer,
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_XML_ChildrenCollector $children_collector
    ) {
        $this->xml_importer       = $xml_importer;
        $this->tracker_factory    = $tracker_factory;
        $this->artifact_factory   = $artifact_factory;
        $this->children_collector = $children_collector;
    }

    public function importChildren(
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_imported_mapping,
        SimpleXMLElement $xml_artifacts,
        $extraction_path,
        Tracker_Artifact $root_artifact,
        PFUser $user
    ) {
        $length = count($xml_artifacts->artifact);
        if ($length <= 1) {
            return;
        }

        $this->addChildrenFromXML($xml_artifacts->artifact[0]);

        for ($i = 1 ; $i < $length ; $i++) {
            $xml_artifact = $xml_artifacts->artifact[$i];
            $tracker = $this->tracker_factory->getTrackerById((int) $xml_artifact['tracker_id']);
            if (! $tracker) {
                throw new Tracker_XML_Importer_TrackerIdNotDefinedException();
            }
            $artifact = $this->xml_importer->importOneArtifactFromXML(
                $tracker,
                $xml_artifact,
                $extraction_path
            );
            if ($artifact) {
                $this->addChildrenFromXML($xml_artifact);
                $artifacts_imported_mapping->add((int) $xml_artifact['id'], $artifact->getId());
            }
        }

        $this->createLinksBetweenArtifacts($user, $artifacts_imported_mapping, $root_artifact);
    }

    private function addChildrenFromXML(SimpleXMLElement $xml) {
        if (! isset($xml->changeset)) {
            return;
        }

        $field_changes = $xml->changeset->field_change;
        foreach ($field_changes as $field_change) {
            if ($field_change['type'] == "art_link") {
                $this->saveAllChildrenFromXMLFieldChange((int) $xml['id'], $field_change);
            }
        }
    }

    private function saveAllChildrenFromXMLFieldChange($parent_id, SimpleXMLElement $xml) {
        foreach ($xml->value as $value) {
            $artifact_id = (int) $value;
            if ($artifact_id) {
                $this->children_collector->addChild($artifact_id, $parent_id);
            }
        }
    }

    private function createLinksBetweenArtifacts(
        PFUser $user,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_imported_mapping,
        Tracker_Artifact $root_artifact
    ) {
        $comment_message    = '';
        $send_notifications = false;
        $original_id        = $artifacts_imported_mapping->getOriginal($root_artifact->getId());
        $children           = $this->children_collector->getChildrenForParent($original_id);
        if (! $children) {
            return;
        }

        foreach ($children as $key => $original_child_id) {
            $children[$key] = $artifacts_imported_mapping->get($original_child_id);
        }

        $field_id = $root_artifact->getAnArtifactLinkField($user)->getId();
        $fields_data = array(
            $field_id => array(
                Tracker_FormElement_Field_ArtifactLink::NEW_VALUES_KEY => implode(',', $children)
            )
        );
        $root_artifact->createNewChangeset(
            $fields_data,
            $comment_message,
            $user,
            $send_notifications,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );

        foreach ($children as $child_id) {
            $artifact = $this->artifact_factory->getArtifactById($child_id);
            $this->createLinksBetweenArtifacts(
                $user,
                $artifacts_imported_mapping,
                $artifact
            );
        }
    }
}
