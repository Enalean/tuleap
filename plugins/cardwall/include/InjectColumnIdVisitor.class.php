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

require_once 'ColumnPresenter.class.php';
require_once TRACKER_BASE_DIR. '/Tracker/TreeNode/CardPresenterNode.class.php';
/**
 * Foreach artifact in a TreeNode, inject the id of the field used for the columns
 */
class Cardwall_InjectColumnIdVisitor {

    public function accumulateStatusFields(TreeNode $node) {
        $artifacts = $this->getArtifactsOutOfTree($node);
        return $this->getIndexedStatusFieldsOf($artifacts);
    }
    
    private function getArtifactsOutOfTree(TreeNode $root_node) {
        $artifacts = array();
        $flat_nodes = $root_node->flatten();
        foreach ($flat_nodes as $node) {
            if ($node instanceof Tracker_TreeNode_CardPresenterNode) {
                $presenter = $node->getCardPresenter();
                $artifacts[] = $presenter->getArtifact();
            }
        }
        return $artifacts;
        
    }
    private function getIndexedStatusFieldsOf(array $artifacts) {
        $status_fields = array_filter(array_map(array($this, 'getField'), $artifacts));
        $indexed_status_fields = $this->indexById($status_fields);
        return $indexed_status_fields;
    }
    
    private function indexById(array $fields) {
        $indexed_array = array();
        foreach ($fields as $field) {
            $indexed_array[$field->getId()] = $field;
        }
        return $indexed_array;
    }
        
    public function visit(TreeNode $node) {
        if ($node instanceof Tracker_TreeNode_CardPresenterNode) {
            $data      = $node->getData();
            $presenter = $node->getCardPresenter();
            $field     = $this->getField($presenter->getArtifact());
            $data['column_field_id'] = 0;
            if ($field) {
                $field_id                = $field->getId();
                $data['column_field_id'] = $field_id;
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

class Cardwall_ColumnPresenterNode extends Tracker_TreeNode_SpecializedNode {

    /** @var ColumnPresenter */
    private $presenter;
    
    function __construct(TreeNode $node, ColumnPresenter $presenter) {
        parent::__construct($node);
        $this->presenter = $presenter;
    }
    
    public function getColumnPresenter() {
        return $this->presenter;
    }

//                $new_node = new Cardwall_ColumnPresenterNode($node, new ColumnPresenter($presenter, $field_id));

}
?>
