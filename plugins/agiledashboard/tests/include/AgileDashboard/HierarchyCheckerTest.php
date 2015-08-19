<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../../bootstrap.php';

class AgileDashboard_HierarchyCheckerTest extends TuleapTestCase {

    /** @var  Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var AgileDashboard_HierarchyChecker */
    private $hierarchy_checker;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Hierarchy */
    private $hierarchy;

    public function setUp() {
        parent::setUp();

        $project       = aMockProject()->withId(34)->build();
        $this->tracker = aMockTracker()->withId(12)->withProject($project)->build();

        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');
        $this->planning_factory  = mock('PlanningFactory');
        $this->kanban_factory    = mock('AgileDashboard_KanbanFactory');
        $this->hierarchy         = mock('Tracker_Hierarchy');

        stub($this->hierarchy_factory)->getHierarchy()->returns($this->hierarchy);

        $this->hierarchy_checker = new AgileDashboard_HierarchyChecker(
            $this->hierarchy_factory,
            $this->planning_factory,
            $this->kanban_factory
        );
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning() {
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(78));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array());

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog() {
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInScrum() {
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(58));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));

        stub($this->hierarchy)->flatten()->returns(array(12,78,68));

        $this->assertFalse($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban() {
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(45,68));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isKanbanHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInKanban() {
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(98,63));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertFalse($this->hierarchy_checker->isKanbanHierarchy($this->tracker));
    }
}