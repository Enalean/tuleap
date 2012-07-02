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

require_once TRACKER_BASE_DIR. '/Tracker/TreeNode/CardPresenterNode.class.php';
require_once 'ColumnPresenterCallback.class.php';
require_once 'FieldRetrievers/SemanticStatusFieldRetriever.class.php';

/**
 * Foreach artifact in a TreeNode tree, make a collection of the semantic status fields and
 * index them by their id
 */
class Cardwall_StatusFieldsExtractor {

    public function extractAndIndexStatusFields(TreeNode $node) {
        $artifacts = $this->getArtifactsOutOfTree($node);
        return $this->getIndexedStatusFieldsOf($artifacts);
    }
    
    private function getArtifactsOutOfTree(TreeNode $root_node) {
        $artifacts = array();
        $flat_nodes = $root_node->flatten();
        foreach ($flat_nodes as $node) {
            $this->appendIfCardPresenterNode($artifacts, $node);
        }
        return $artifacts;
        
    }

    private function appendIfCardPresenterNode(array &$artifacts, TreeNode $node) {
        if ($node instanceof Tracker_TreeNode_CardPresenterNode) {
            $artifacts[] = $node->getCardPresenter()->getArtifact();
        }
    }
        
    private function getIndexedStatusFieldsOf(array $artifacts) {
        $status_field_retriever = new Cardwall_FieldRetrievers_SemanticStatusFieldRetriever();
        $status_fields = array_filter(array_map(array($status_field_retriever, 'getField'), $artifacts));
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

}
?>
