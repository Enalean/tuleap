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

class ArtifactCCFieldXMLExporter extends ArtifactFieldXMLExporter {

    public function appendNode(DOMElement $changeset_node, $artifact_id, array $row) {
        $values = array_filter(explode(',', $row['new_value']));
        $field_node = $this->node_helper->createElement('field_change');
        $field_node->setAttribute('field_name', 'cc');
        $field_node->setAttribute('type', 'open_list');
        $field_node->setAttribute('bind', 'user');
        foreach ($values as $value) {
            $cc_value_node = $this->node_helper->getNodeWithValue('value', $value);
            $this->node_helper->addUserFormatAttribute($cc_value_node, $this->isValueAnEmailAddress($value));
            $field_node->appendChild($cc_value_node);
        }
        $changeset_node->appendChild($field_node);
    }

    private function isValueAnEmailAddress($value) {
        return strpos($value, '@') !== false;
    }
}
