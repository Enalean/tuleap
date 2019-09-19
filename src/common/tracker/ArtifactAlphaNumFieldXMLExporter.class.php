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

abstract class ArtifactAlphaNumFieldXMLExporter extends ArtifactFieldXMLExporter
{

    protected function appendStringNode(DOMElement $changeset_node, $type, array $row)
    {
        $field_node = $this->getNode($type, $row);
        $field_node->appendChild($this->getNodeValue($row['new_value']));
        $changeset_node->appendChild($field_node);
    }

    protected function getNode($type, array $row)
    {
        $field_node = $this->node_helper->createElement('field_change');
        $field_node->setAttribute('field_name', $row['field_name']);
        $field_node->setAttribute('type', $type);
        return $field_node;
    }

    protected function getNodeValue($value)
    {
        return $this->node_helper->getNodeWithValue('value', $value);
    }
}
