<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Foreach artifact in a TreeNode, inject the drop-into-* classnames depending 
 * on the column_field_id defined.
 */
class Cardwall_InjectDropIntoClassnamesVisitor {

    /**
     * @var Cardwall_MappingCollection
     */
    private $mappings;

    public function __construct(Cardwall_MappingCollection $mappings) {
        $this->mappings = $mappings;
    }

    public function visit(TreeNode $node, $row_index = 0) {
        $data     = $node->getData();
        if (isset($data['column_field_id'])) {
            $mappings = $this->mappings->getMappingsByFieldId($data['column_field_id']);
            $data['drop-into-class'] = '';
            foreach ($mappings as $mapping) {
                $data['drop-into-class'] .= ' drop-into-'. $row_index .'-'. $mapping->column_id;
            }
            $node->setData($data);
        }
        foreach ($node->getChildren() as $child) {
            $child->accept($this, $row_index);
        }
    }
}
?>
