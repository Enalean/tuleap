<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyList extends Tracker_Artifact_XMLImport_XMLImportFieldStrategyAlphanumeric
{
    public const BIND_STATIC  = 'static';
    public const BIND_UGROUPS = 'ugroups';
    public const FORMAT_ID    = 'id';

    /** @var BindStaticValueDao */
    private $static_value_dao;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var TrackerXmlFieldsMapping */
    private $xml_fields_mapping;

    public function __construct(
        BindStaticValueDao $static_value_dao,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlFieldsMapping $xml_fields_mapping,
    ) {
        $this->static_value_dao   = $static_value_dao;
        $this->user_finder        = $user_finder;
        $this->xml_fields_mapping = $xml_fields_mapping;
    }

    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ): array {
        $bind = (string) $field_change['bind'];
        $data = [];

        if ($bind === self::BIND_STATIC) {
            foreach ($field_change as $value) {
                $data[] = $this->getStaticListDataValue($field, $value);
            }
        } elseif ($bind === self::BIND_UGROUPS) {
            foreach ($field_change as $value) {
                $data[] = $this->getUgroupListDataValue($value);
            }
        } else {
            foreach ($field_change as $value) {
                $data[] = $this->getUserListDataValue($value);
            }
        }

        return $data;
    }

    private function getStaticListDataValue(Tracker_FormElement_Field $field, ?SimpleXMLElement $value): ?int
    {
        if (! $value) {
            return null;
        }

        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID) {
            return (int) $this->xml_fields_mapping->getNewValueId(trim((string) $value));
        }

        $result = $this->static_value_dao->searchValueByLabel($field->getId(), (string) $value);
        $row    = $result->getRow();

        if ($row === false) {
            return null;
        }

        return (int) $row['id'];
    }

    private function getUgroupListDataValue(?SimpleXMLElement $value): ?int
    {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID) {
            return (int) $this->xml_fields_mapping->getNewValueId((int) $value);
        }

        return null;
    }

    private function getUserListDataValue(?SimpleXMLElement $value): ?int
    {
        if (! $value) {
            return null;
        }

        $user = $this->user_finder->getUser($value);

        return (int) $user->getId();
    }
}
