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
 * Provides a TreeNode/ArtifactNode tree given some artifact ids. Organisation of nodes :
 * root -> forest -> artifactNodes
 */
class Cardwall_ArtifactNodeTreeProvider {

    /** @var Cardwall_CardInCellPresenterNodeFactory */
    private $node_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(Cardwall_CardInCellPresenterNodeFactory $node_factory, Tracker_ArtifactFactory $artifact_factory) {
        $this->node_factory     = $node_factory;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @return TreeNode
     */
    public function flatForestOfArtifacts(array $artifact_ids, $swimline_id) {
        $cards = $this->getCards($artifact_ids);
        return $this->wrapInAThreeLevelArtifactTree($cards, $swimline_id);
    }

    /**
     * @return TreeNode
     */
    protected function wrapInAThreeLevelArtifactTree(array $cards, $swimline_id) {
        $forest = new TreeNode();
        $forest->setId($swimline_id);
        $forest->setChildren($cards);
        $root = new TreeNode();
        $root->addChild($forest);
        return $root;
    }

    /**
     * @return Cardwall_CardInCellPresenterNode[]
     */
    protected function getCards(array $artifact_ids) {
        $cards = array();
        foreach ($artifact_ids as $id) {
            $artifact = $this->artifact_factory->getArtifactById($id);
            $cards[]  = $this->node_factory->getCardInCellPresenterNode($artifact);
        }
        return $cards;
    }

}
?>
