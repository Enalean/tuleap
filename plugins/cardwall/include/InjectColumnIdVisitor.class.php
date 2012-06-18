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
 * Foreach artifact in a TreeNode, inject the id of the field used for the columns
 */
class Cardwall_InjectColumnIdVisitor {

    /**
     * @var array Accumulated array of Tracker_FormElement_Field_Selectbox
     */
    private $accumulated_status_fields = array();

    /**
     * @return array Accumulated array of Tracker_FormElement_Field_Selectbox
     */
    public function getAccumulatedStatusFields() {
        return $this->accumulated_status_fields;
    }

    public function visit(TreeNode $node) {
        $data      = $node->getData();
        $presenter = $node->getObject();
        if ($presenter) {
            $field = $this->getField($presenter->getArtifact());
            $data['column_field_id'] = 0;
            if ($field) {
                $field_id                = $field->getId();
                $data['column_field_id'] = $field_id;
                $this->accumulated_status_fields[$field_id] = $field;
            }
            $node->setData($data);
        }
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }

    /**
     * @return Tracker_FormElement_Field_Selectbox
     */
    protected function getField(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        return Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
    }
}
?>
