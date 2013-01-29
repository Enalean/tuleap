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

require_once 'common/TreeNode/TreeNodeCallback.class.php';

/**
 * Create a Cardwall_ColumnPresnterNode given a Tracker_TreeNode_CardPresenterNode
 */
class Cardwall_CardInCellPresenterCallback implements TreeNodeCallback {

    /**
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
     */
    private $field_retriever;
    
    /**
     * @var Cardwall_MappingCollection
     */
    private $mappings;


    public function __construct(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $retriever, Cardwall_MappingCollection $mappings) {
        $this->field_retriever = $retriever;
        $this->mappings        = $mappings;
    }
    
    /**
     * @see TreeNodeCallback and class comment
     */
    public function apply(TreeNode $node) {
        if (!$node instanceof Tracker_TreeNode_CardPresenterNode) {
            return clone $node;
        }
        $card_field_id    = $this->getFieldId($node);
        $swim_line_values = $this->mappings->getSwimLineValues($card_field_id);
        $presenter        = new Cardwall_CardInCellPresenter($node->getCardPresenter(), $card_field_id, $this->getParentNodeId($node), $swim_line_values);
        return new Cardwall_CardInCellPresenterNode($node, $presenter);
    }

    private function getParentNodeId(TreeNode $node) {
        $parent_node = $node->getParentNode();
        return $parent_node ? $parent_node->getId() : 0;
    }

    private function getFieldId(Tracker_TreeNode_CardPresenterNode $node) {
        $artifact = $node->getCardPresenter()->getArtifact();
        $field    = $this->field_retriever->getField($artifact);
        return $field ? $field->getId() : 0;
    }
    
}


?>
