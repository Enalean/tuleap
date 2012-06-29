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
require_once 'ColumnPresenterNode.class.php';
require_once 'MappingCollection.class.php';
require_once dirname(__FILE__).'/../../tracker/include/Tracker/IProvideFieldGivenAnArtifact.class.php';
require_once 'common/TreeNode/TreeNodeCallback.class.php';

class ColumnPresenterCallback implements TreeNodeCallback {

    /**
     * @var Tracker_IProvideFieldGivenAnArtifact
     */
    private $field_retriever;
    
    /**
     * @var Cardwall_MappingCollection
     */
    private $mappings;


    public function __construct(Tracker_IProvideFieldGivenAnArtifact $retriever, Cardwall_MappingCollection $mappings) {
        $this->field_retriever = $retriever;
        $this->mappings        = $mappings;
    }
    
    public function apply(TreeNode $node) {
        if (!$node instanceof Tracker_TreeNode_CardPresenterNode) {
            return clone $node;
        }
        $artifact         = $node->getCardPresenter()->getArtifact();
        $card_field_id    = $this->getFieldId($artifact);
        $swim_line_values = $this->mappings->getSwimLineValues($card_field_id);
        $presenter        = new ColumnPresenter($node->getCardPresenter(), $card_field_id, $this->getParentNodeId($node), $swim_line_values);
        return new Cardwall_ColumnPresenterNode($node, $presenter);
    }

    private function getParentNodeId(TreeNode $node) {
        $parent_node = $node->getParentNode();
        return $parent_node ? $parent_node->getId() : 0;
    }

    private function getFieldId($artifact) {
        $field = $this->field_retriever->getField($artifact);
        return $field ? $field->getId() : 0;
    }
    
}


?>
