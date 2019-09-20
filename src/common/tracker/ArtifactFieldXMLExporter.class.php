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

abstract class ArtifactFieldXMLExporter
{

    /** @var ArtifactXMLNodeHelper */
    protected $node_helper;

    public function __construct(ArtifactXMLNodeHelper $node_helper)
    {
        $this->node_helper = $node_helper;
    }

    abstract public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row);

    abstract public function getFieldValueIndex();

    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        return $field_value_row;
    }

    public function isValueEqual($value1, $value2)
    {
        return $value1 == $value2;
    }
}
