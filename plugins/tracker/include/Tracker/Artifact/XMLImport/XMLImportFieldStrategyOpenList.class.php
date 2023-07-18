<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    public const FORMAT_ID       = 'id';
    public const FORMAT_LDAP     = 'ldap';
    public const FORMAT_USERNAME = 'username';
    public const BIND_USERS      = 'users';

    /** @var TrackerXmlFieldsMapping */
    private $xml_fields_mapping;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    public function __construct(
        TrackerXmlFieldsMapping $xml_fields_mapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
    ) {
        $this->xml_fields_mapping = $xml_fields_mapping;
        $this->user_finder        = $user_finder;
    }

    /**
     * Extract Field data from XML input
     *
     *
     * @return mixed
     */
    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ) {
        $values = [];
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

    private function getUserValue(Tracker_FormElement_Field $field, SimpleXMLElement $value)
    {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID) {
            return (string) $value;
        }

        if ($this->doesValueConcernUser($value)) {
            $user = $this->user_finder->getUser($value);

            if ($user->isAnonymous()) {
                return '';
            }

            return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $user->getId();
        }

        return $field->getFieldData((string) $value);
    }

    private function doesValueConcernUser($value)
    {
        return isset($value['format']) &&
            ((string) $value['format'] === self::FORMAT_LDAP ||
             (string) $value['format'] === self::FORMAT_USERNAME
            );
    }

    private function getFieldChangeId(Tracker_FormElement_Field $field, SimpleXMLElement $value)
    {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID) {
            $value_id = $this->xml_fields_mapping->getNewOpenValueId((string) $value);
            if (is_numeric($value_id)) {
                return Tracker_FormElement_Field_List_BindValue::BIND_PREFIX . $value_id;
            }

            return $value_id;
        }

        return $field->getFieldData((string) $value);
    }
}
