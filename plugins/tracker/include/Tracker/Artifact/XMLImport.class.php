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

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var UserManager */
    private $user_manager;

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $formelement_factory,
        UserManager $user_manager) {

        $this->rng_validator        = $rng_validator;
        $this->artifact_factory     = $artifact_factory;
        $this->formelement_factory  = $formelement_factory;
        $this->user_manager         = $user_manager;
    }

    public function importFromFile(Tracker $tracker, $filepath) {
        $this->importFromXML($tracker, simplexml_load_file($filepath));
    }

    public function importFromXML(Tracker $tracker, SimpleXMLElement $xml_element) {
        $this->rng_validator->validate($xml_element);
        foreach ($xml_element->artifact as $artifact) {
            $this->importOneArtifact($tracker, $artifact);
        }
    }

    private function importOneArtifact(Tracker $tracker, SimpleXMLElement $xml_artifact) {
        $this->importInitialChangeset($tracker, $xml_artifact->changeset[0]);
    }

    private function importInitialChangeset(Tracker $tracker, SimpleXMLElement $xml_changeset) {
        $fields_data        = $this->getFieldsData($tracker, $xml_changeset->field_change);
        if (count($fields_data) > 0) {
            $email              = '';
            $send_notifications = false;

            $this->artifact_factory->createArtifactAt(
                $tracker,
                $fields_data,
                $this->getSubmittedBy($xml_changeset),
                $email,
                $this->getSubmittedOn($xml_changeset),
                $send_notifications
            );
        }
    }


    private function getFieldsData(Tracker $tracker, SimpleXMLElement $xml_field_change) {
        $data = array();
        $field = $this->formelement_factory->getFormElementByName($tracker->getId(), (string) $xml_field_change['field_name']);
        if ($field) {
            $data[$field->getId()] = (string) $xml_field_change->value;
        }
        return $data;
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
