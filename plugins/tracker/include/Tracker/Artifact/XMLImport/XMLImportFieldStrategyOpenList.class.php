<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy {

    const FORMAT_ID       = 'id';
    const FORMAT_LDAP     = 'ldap';
    const FORMAT_USERNAME = 'username';
    const BIND_USERS      = 'users';

    /** @var TrackerXmlFieldsMapping */
    private $xml_fields_mapping;

    /** @var Tracker_XMLImport_XMLImportHelper */
    private $xml_import_helper;

    public function __construct(
        TrackerXmlFieldsMapping $xml_fields_mapping,
        Tracker_XMLImport_XMLImportHelper $xml_import_helper
    ) {
        $this->xml_fields_mapping = $xml_fields_mapping;
        $this->xml_import_helper  = $xml_import_helper;
    }

    /**
     * Extract Field data from XML input
     *
     * @param Tracker_FormElement_Field $field
     * @param SimpleXMLElement $field_change
     *
     * @return mixed
     */
    public function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        $values = array();
        $bind   = (string) $field_change['bind'];

        foreach ($field_change->value as $value) {
            if ($bind === self::BIND_USERS) {
                $values[] = (string) $this->getUserValue($field, $value);
            } else {
                $values[] = (string) $this->getFieldChangeId($field, $value);
            }
        }

        return implode(',', $values);
    }

    private function getUserValue(Tracker_FormElement_Field $field, $value) {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID) {
            return (string) $value;
        }

        if ($this->doesValueConcernUser($value)){
            $user = $this->xml_import_helper->getUser($value);

            if ($user->isAnonymous()) {
                return '';
            }

            return Tracker_FormElement_Field_OpenList::BIND_PREFIX.$user->getId();
        }

        return $field->getFieldData((string) $value);
    }

    private function doesValueConcernUser($value) {
        return isset($value['format']) &&
            ((string) $value['format'] === self::FORMAT_LDAP ||
             (string) $value['format'] === self::FORMAT_USERNAME
            );
    }

    private function getFieldChangeId(Tracker_FormElement_Field $field, $value) {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID){
            return $this->xml_fields_mapping->getNewOpenValueId((string) $value);
        }

        return $field->getFieldData((string) $value);
    }
}