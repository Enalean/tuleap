<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_XMLImport {

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_ArtifactCreator */
    private $artifact_creator;

    /** @var Tracker_Artifact_Changeset_NewChangesetCreatorBase */
    private $new_changeset_creator;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_Artifact_XMLImport_XMLImportHelper */
    private $xml_import_helper;

    /** @var Tracker_FormElement_Field_List_Bind_Static_ValueDao */
    private $static_value_dao;

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactCreator $artifact_creator,
        Tracker_Artifact_Changeset_NewChangesetCreatorBase $new_changeset_creator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Artifact_XMLImport_XMLImportHelper $xml_import_helper,
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao
    ) {

        $this->rng_validator         = $rng_validator;
        $this->artifact_creator      = $artifact_creator;
        $this->new_changeset_creator = $new_changeset_creator;
        $this->formelement_factory   = $formelement_factory;
        $this->xml_import_helper     = $xml_import_helper;
        $this->static_value_dao      = $static_value_dao;
    }

    public function importFromArchive(Tracker $tracker, Tracker_Artifact_XMLImport_XMLImportZipArchive $archive) {
        $archive->extractFiles();
        $xml = simplexml_load_string($archive->getXML());
        $extraction_path = $archive->getExtractionPath();
        $this->importFromXML($tracker, $xml, $extraction_path);
        $archive->cleanUp();
    }

    public function importFromXML(Tracker $tracker, SimpleXMLElement $xml_element, $extraction_path) {
        $this->rng_validator->validate($xml_element);
        foreach ($xml_element->artifact as $artifact) {
            $files_importer = new Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact($artifact);
            $fields_data_builder = new Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder(
                $this->formelement_factory,
                $this->xml_import_helper,
                $tracker,
                $files_importer,
                $extraction_path,
                $this->static_value_dao
            );
            $this->importOneArtifact($tracker, $artifact, $fields_data_builder);
        }
    }

    private function importOneArtifact(
        Tracker $tracker,
        SimpleXMLElement $xml_artifact,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        if (count($xml_artifact->changeset) > 0) {
            $changesets      = $this->getSortedBySubmittedOn($xml_artifact->changeset);
            $first_changeset = array_shift($changesets);
            $artifact        = $this->importInitialChangeset($tracker, $first_changeset, $fields_data_builder);
            if (count($changesets)) {
                $this->importRemainingChangesets($artifact, $changesets, $fields_data_builder);
            }
        }
    }

    private function getSortedBySubmittedOn(SimpleXMLElement $changesets) {
        $changeset_array = array();
        foreach ($changesets as $changeset) {
            $changeset_array[$this->getSubmittedOn($changeset)] = $changeset;
        }
        ksort($changeset_array, SORT_NUMERIC);
        return $changeset_array;
    }

    private function importInitialChangeset(
        Tracker $tracker,
        SimpleXMLElement $xml_changeset,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $fields_data = $fields_data_builder->getFieldsData($xml_changeset->field_change);
        if (count($fields_data) > 0) {
            $email              = '';
            $send_notifications = false;
            $artifact = $this->artifact_creator->create(
                $tracker,
                $fields_data,
                $this->getSubmittedBy($xml_changeset),
                $email,
                $this->getSubmittedOn($xml_changeset),
                $send_notifications
            );
            if ($artifact) {
                return $artifact;
            } else {
                throw new Tracker_Artifact_Exception_CannotCreateInitialChangeset();
            }
        }
        throw new Tracker_Artifact_Exception_EmptyChangesetException();
    }

    private function importRemainingChangesets(
        Tracker_Artifact $artifact,
        array $xml_changesets,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        foreach($xml_changesets as $xml_changeset) {
            $comment           = '';
            $send_notification = false;
            $result = $this->new_changeset_creator->create(
                $artifact,
                $fields_data_builder->getFieldsData($xml_changeset->field_change),
                $comment,
                $this->getSubmittedBy($xml_changeset),
                $this->getSubmittedOn($xml_changeset),
                $send_notification,
                Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
            );
            if (! $result) {
                throw new Tracker_Artifact_Exception_CannotCreateNewChangeset();
            }
        }
    }

    private function getSubmittedBy(SimpleXMLElement $xml_changeset) {
        return $this->xml_import_helper->getUser($xml_changeset->submitted_by);
    }

    private function getSubmittedOn(SimpleXMLElement $xml_changeset) {
        return strtotime((string)$xml_changeset->submitted_on);
    }
}
