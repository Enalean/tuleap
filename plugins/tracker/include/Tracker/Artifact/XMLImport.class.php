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
    const FIELDNAME_CHANGE_SUMMARY     = 'summary';
    const FIELDNAME_CHANGE_ATTACHEMENT = 'attachment';

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var String */
    private $extraction_path;

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $formelement_factory,
        UserManager $user_manager,
        $extraction_path
    ) {
        $this->rng_validator        = $rng_validator;
        $this->artifact_factory     = $artifact_factory;
        $this->formelement_factory  = $formelement_factory;
        $this->user_manager         = $user_manager;
        $this->extraction_path      = $extraction_path;
    }

    public function importFromArchive(Tracker $tracker, ZipArchive $archive) {
        $this->importFromXML($tracker, simplexml_load_string($archive->getFromName('artifacts.xml')));
    }

    public function importFromXML(Tracker $tracker, SimpleXMLElement $xml_element) {
        $this->rng_validator->validate($xml_element);
        foreach ($xml_element->artifact as $artifact) {
            $files_importer = new Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact($artifact);
            $this->importOneArtifact($tracker, $artifact, $files_importer);
        }
    }

    private function importOneArtifact(Tracker $tracker, SimpleXMLElement $xml_artifact, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        if (count($xml_artifact->changeset) > 0) {
            $changesets                  = $this->getSortedBySubmittedOn($xml_artifact->changeset);
            $first_changeset             = array_shift($changesets);
            $artifact                    = $this->importInitialChangeset($tracker, $first_changeset, $files_importer);
            if (count($changesets)) {
                $this->importRemainingChangesets($tracker, $artifact, $changesets, $files_importer);
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

    private function importInitialChangeset(Tracker $tracker, SimpleXMLElement $xml_changeset, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        $fields_data        = $this->getFieldsData($tracker, $xml_changeset->field_change, $files_importer);
        if (count($fields_data) > 0) {
            $email              = '';
            $send_notifications = false;

            $artifact = $this->artifact_factory->createArtifactAt(
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

    private function importRemainingChangesets(Tracker $tracker, Tracker_Artifact $artifact, array $xml_changesets, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        foreach($xml_changesets as $xml_changeset) {
            $comment           = '';
            $send_notification = false;
            $result = $artifact->createNewChangesetAt(
                $this->getFieldsData($tracker, $xml_changeset->field_change, $files_importer),
                $comment,
                $this->getSubmittedBy($xml_changeset),
                $this->getSubmittedOn($xml_changeset),
                $send_notification
            );
            if (! $result) {
                throw new Tracker_Artifact_Exception_CannotCreateNewChangeset();
            }
        }
    }

    private function getFieldsData(Tracker $tracker, SimpleXMLElement $xml_field_change, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        $data = array();

        foreach ($xml_field_change as $field_change) {
            $field = $this->formelement_factory->getFormElementByName($tracker->getId(), (string) $field_change['field_name']);

            if ($field) {
                $data[$field->getId()] = $this->getFieldData($field_change, $files_importer);
            }
        }
        return $data;
    }

    private function getFieldData(SimpleXMLElement $field_change, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        switch ($field_change['field_name']) {
            case self::FIELDNAME_CHANGE_SUMMARY :
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategySummary();
                break;
            case self::FIELDNAME_CHANGE_ATTACHEMENT :
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment($this->extraction_path, $files_importer);
                break;
        }

        return $strategy->getFieldData($field_change);
    }

    private function getSubmittedBy(SimpleXMLElement $xml_changeset) {
        $submitter    = $this->user_manager->getUserByIdentifier($this->getUserFormat($xml_changeset->submitted_by));
        if (! $submitter) {
            $submitter = $this->user_manager->getUserAnonymous();
            $submitter->setEmail((string) $xml_changeset->submitted_by);
        }
        return $submitter;
    }

    private function getUserFormat(SimpleXMLElement $xml_submitted_by) {
        $format       = (string) $xml_submitted_by['format'];
        $submitted_by = (string) $xml_submitted_by;
        switch($format) {
            case 'id':
            case 'email':
                return "$format:$submitted_by";

            case 'ldap':
                return "ldapId:$submitted_by";

            default :
                return (string) $xml_submitted_by;
        }
    }

    private function getSubmittedOn(SimpleXMLElement $xml_changeset) {
        return strtotime((string)$xml_changeset->submitted_on);
    }
}
