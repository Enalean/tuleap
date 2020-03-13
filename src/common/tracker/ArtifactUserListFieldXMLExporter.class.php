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

class ArtifactUserListFieldXMLExporter extends ArtifactStaticListFieldXMLExporter
{
    public const TV3_VALUE_INDEX  = 'valueInt';
    public const TV3_TYPE         = 'SB_5';
    public const TV5_TYPE         = 'list';
    public const TV5_BIND         = 'users';

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $field_node = $this->getNode(self::TV5_TYPE, $row);
        $field_node->setAttribute('bind', self::TV5_BIND);
        $user_node = $this->getNodeValue($this->getValueLabel($row['new_value']));
        $this->node_helper->addUserFormatAttribute($user_node, false);
        $field_node->appendChild($user_node);
        $changeset_node->appendChild($field_node);
    }

    private function getValueLabel($value)
    {
        if ($value == 100) {
            return '';
        }
        $dar = $this->dao->searchUser($value);
        if ($dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['user_name'];
        }
        throw new Exception_TV3XMLException('Unknown user ' . $value);
    }

    public function getFieldValueIndex()
    {
        return self::TV3_VALUE_INDEX;
    }
}
