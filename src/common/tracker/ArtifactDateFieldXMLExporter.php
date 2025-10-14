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

class ArtifactDateFieldXMLExporter extends ArtifactAlphaNumFieldXMLExporter
{
    public const string TV3_DISPLAY_TYPE = 'DF';
    public const string TV3_DATA_TYPE    = '4';
    public const string TV3_VALUE_INDEX  = 'valueDate';
    public const string TV3_TYPE         = 'DF_4';
    public const string TV5_TYPE         = 'date';

    public const string SPECIAL_DATE_FIELD = 'close_date';

    #[\Override]
    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $this->appendStringNode($changeset_node, self::TV5_TYPE, $row);
    }

    #[\Override]
    protected function getNodeValue($value)
    {
        return $this->node_helper->getDateNodeFromTimestamp('value', $value);
    }

    #[\Override]
    public function getFieldValueIndex()
    {
        return self::TV3_VALUE_INDEX;
    }

    #[\Override]
    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        if ($field_value_row['field_name'] === self::SPECIAL_DATE_FIELD) {
            return;
        }

        return parent::getCurrentFieldValue($field_value_row, $tracker_id);
    }
}
