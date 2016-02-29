<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../common.php';

class Planning_MilestonePaneFactory4Tests extends Planning_MilestonePaneFactory {
    public function getAvailableMilestones(Planning_Milestone $milestone) {
        return parent::getAvailableMilestones($milestone);
    }
}

class Planning_MilestonePaneFactory_AvailableMilestonesTest extends TuleapTestCase {

    private $sprint_planning;
    private $sprint_1;
    private $sprint_2;
    private $milestone_factory;
    private $pane_factory;
    private $request;

    public function setUp() {
        parent::setUp();

        $this->sprint_planning = aPlanning()->withBacklogTracker(aTracker()->build())->build();

        $this->sprint_1 = mock('Planning_Milestone');
        stub($this->sprint_1)->hasAncestors()->returns(true);
        $this->sprint_2 = aMilestone()->withArtifact(aMockArtifact()->withId(1234)->build())->build();

        $this->milestone_factory = mock('Planning_MilestoneFactory');

        $this->current_user = aUser()->build();
        $this->request = aRequest()->withUser($this->current_user)->build();

        $this->pane_presenter_builder_factory = mock('AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory');
        stub($this->pane_presenter_builder_factory)->getContentPresenterBuilder()->returns(mock('AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder'));
    }

    public function itDisplaysOnlySiblingsMilestones() {
        stub($this->milestone_factory)->getAllBareMilestones()->returns(array());
        stub($this->milestone_factory)->getSiblingMilestones()->returns(array($this->sprint_1, $this->sprint_2));
        $this->pane_factory = new Planning_MilestonePaneFactory4Tests(
            $this->request,
            $this->milestone_factory,
            $this->pane_presenter_builder_factory,
            mock('AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder'),
            mock('AgileDashboard_PaneInfoFactory'),
            mock('AgileDashboard_Milestone_MilestoneRepresentationBuilder'),
            mock('AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder')
        );

        $selectable_artifacts = $this->pane_factory->getAvailableMilestones($this->sprint_1);
        $this->assertCount($selectable_artifacts, 2);
        $this->assertEqual(array_shift($selectable_artifacts), $this->sprint_1);
        $this->assertEqual(array_shift($selectable_artifacts), $this->sprint_2);
    }

    public function itDisplaysASelectorOfArtifactWhenThereAreNoMilestoneSelected() {
        $current_milstone = new Planning_NoMilestone(mock('Project'), $this->sprint_planning);

        $milstone_1001 = aMilestone()->withArtifact(aMockArtifact()->withId(1001)->withTitle('An open artifact')->build())->build();
        $milstone_1002 = aMilestone()->withArtifact(aMockArtifact()->withId(1002)->withTitle('Another open artifact')->build())->build();

        stub($this->milestone_factory)->getAllBareMilestones($this->current_user, $this->sprint_planning)->returns(array($milstone_1001, $milstone_1002));
        $this->pane_factory = new Planning_MilestonePaneFactory4Tests(
            $this->request,
            $this->milestone_factory,
            $this->pane_presenter_builder_factory,
            mock('AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder'),
            mock('AgileDashboard_PaneInfoFactory'),
            mock('AgileDashboard_Milestone_MilestoneRepresentationBuilder'),
            mock('AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder')
        );

        $selectable_artifacts = $this->pane_factory->getAvailableMilestones($current_milstone);
        $this->assertCount($selectable_artifacts, 2);
        $this->assertEqual(array_shift($selectable_artifacts), $milstone_1001);
        $this->assertEqual(array_shift($selectable_artifacts), $milstone_1002);
    }
}

?>
