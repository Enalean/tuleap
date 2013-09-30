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
 * Build the artifact tree to be presented on the cardwall
 */
class Cardwall_PaneBuilder {

    private $artifact_factory;
    private $node_factory;
    private $dao;

    public function __construct(Cardwall_CardInCellPresenterNodeFactory $node_factory, Tracker_ArtifactFactory $artifact_factory, AgileDashboard_BacklogItemDao $dao) {
        $this->node_factory = $node_factory;
        $this->artifact_factory = $artifact_factory;
        $this->dao = $dao;
    }

    /**
     * Retrieves the artifacts planned for the given milestone artifact.
     *
     * @param PFUser             $user
     * @param Planning         $planning
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return TreeNode
     */
    public function getPlannedArtifacts(PFUser $user, Tracker_Artifact $milestone_artifact) {
        $root = new ArtifactNode($milestone_artifact);
        foreach ($this->dao->getBacklogArtifacts($milestone_artifact->getId()) as $row) {
            $swimline_artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($swimline_artifact->userCanView($user)) {
                $children = $swimline_artifact->getChildrenForUser($user);
                if ($children) {
                    $swimline_node = $this->node_factory->getCardInCellPresenterNode($swimline_artifact);
                    foreach ($children as $child) {
                        $swimline_node->addChild($this->node_factory->getCardInCellPresenterNode($child, $swimline_node->getId()));
                    }
                } else {
                    $swimline_node = new TreeNode(null, $swimline_artifact->getId());
                    $solo_node = $this->node_factory->getCardInCellPresenterNode($swimline_artifact, $swimline_node->getId());
                    $swimline_node->addChild($solo_node);
                }
                $root->addChild($swimline_node);
            }
        }
        return $root;
    }

}

?>
