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

class ArtifactTextFieldXMLExporter extends ArtifactAlphaNumFieldXMLExporter
{
    public const string TV3_DISPLAY_TYPE = 'TA';
    public const string TV3_DATA_TYPE    = '1';
    public const string TV3_TYPE         = 'TA_1';
    public const string TV5_TYPE         = 'text';
    public const string TV3_VALUE_INDEX  = 'valueText';

    #[\Override]
    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        if (isset($row['new_value'])) {
            $new_value        = util_unconvert_htmlspecialchars($row['new_value']);
            $row['new_value'] = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($new_value);
        }

        $this->appendStringNode($changeset_node, self::TV5_TYPE, $row);
    }

    #[\Override]
    public function getFieldValueIndex()
    {
        return self::TV3_VALUE_INDEX;
    }

    #[\Override]
    public function isValueEqual($history_field_value, $field_value)
    {
        return $history_field_value == util_unconvert_htmlspecialchars($field_value);
    }
}
