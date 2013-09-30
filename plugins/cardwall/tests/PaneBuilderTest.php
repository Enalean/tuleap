<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__) .'/../../agiledashboard/include/AgileDashboard/BacklogItemDao.class.php';

class Cardwall_PaneBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->node_factory       = mock('Cardwall_CardInCellPresenterNodeFactory');
        $this->artifact_factory   = mock('Tracker_ArtifactFactory');
        $this->dao                = mock('AgileDashboard_BacklogItemDao');
        $this->user               = aUser()->build();

        $this->milestone_artifact = anArtifact()->withId(1)->build();

    }

    public function itReturnsARootWithMilestone() {
        stub($this->dao)->getBacklogArtifacts()->returnsEmptyDar();

        $expected_root = new ArtifactNode($this->milestone_artifact);

        $pane_builder = new Cardwall_PaneBuilder($this->node_factory, $this->artifact_factory, $this->dao);

        $this->assertEqual($expected_root, $pane_builder->getPlannedArtifacts($this->user, $this->milestone_artifact));
    }

    public function itReturnsARootWithMilestoneAndBacklog() {
        $swimline_artifact = aMockArtifact()->withId('the id')->allUsersCanView()->build();
        stub($swimline_artifact)->getChildrenForUser()->returns(array());

        $row = array('id' => 'the id');
        stub($this->artifact_factory)->getInstanceFromRow($row)->returns($swimline_artifact);
        stub($this->dao)->getBacklogArtifacts()->returnsDar($row);

        stub($this->node_factory)->getCardInCellPresenterNode($swimline_artifact, $swimline_artifact->getId())->returns(new TreeNode(null, $swimline_artifact->getId()));

        $pane_builder = new Cardwall_PaneBuilder($this->node_factory, $this->artifact_factory, $this->dao);
        $root = $pane_builder->getPlannedArtifacts($this->user, $this->milestone_artifact);
        $children = $root->getChildren();
        $this->assertCount($children, 1);
        $first_child = array_shift($children);
        $grand_children = $first_child->getChildren();
        $this->assertCount($grand_children, 1);
        $first_grand_child = array_shift($grand_children);
        $this->assertEqual($first_grand_child->getId(), 'the id');
    }

    public function itReturnsASwimlineNodeAndAChild() {
        $child_artifact = aMockArtifact()->withId('child')->allUsersCanView()->build();
        $child_artifact_presenter = mock('Cardwall_CardInCellPresenterNode');

        $this->assertIsA($child_artifact_presenter, 'TreeNode');

        $swimline_artifact = aMockArtifact()->withId('whatever')->allUsersCanView()->build();
        stub($swimline_artifact)->getChildrenForUser()->returns(array($child_artifact));

        $row = array('id' => 'whatever');
        stub($this->artifact_factory)->getInstanceFromRow($row)->returns($swimline_artifact);
        stub($this->dao)->getBacklogArtifacts()->returnsDar($row);

        stub($this->node_factory)->getCardInCellPresenterNode($swimline_artifact)->returns(new TreeNode());
        stub($this->node_factory)->getCardInCellPresenterNode($child_artifact, '*')->returns($child_artifact_presenter);

        $pane_builder = new Cardwall_PaneBuilder($this->node_factory, $this->artifact_factory, $this->dao);
        $root = $pane_builder->getPlannedArtifacts($this->user, $this->milestone_artifact);
        $children = $root->getChildren();
        $first_child = array_shift($children);
        $this->assertEqual($first_child->getChildren(), array($child_artifact_presenter));
    }
}

?>
