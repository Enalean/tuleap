<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class AgileDashboard_HierarchyCheckerTest extends TuleapTestCase
{

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

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var PFUser */
    private $user;

    public function setUp()
    {
        parent::setUp();

        $project       = aMockProject()->withId(34)->build();
        $this->tracker = aMockTracker()->withId(12)->withProject($project)->build();
        $this->user    = aUser()->build();

        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');
        $this->planning_factory  = mock('PlanningFactory');
        $this->kanban_factory    = mock('AgileDashboard_KanbanFactory');
        $this->hierarchy         = mock('Tracker_Hierarchy');
        $this->tracker_factory   = mock('TrackerFactory');

        $this->hierarchy_checker = new AgileDashboard_HierarchyChecker(
            $this->planning_factory,
            $this->kanban_factory,
            $this->tracker_factory
        );
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning()
    {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(78));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array());

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog()
    {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInScrumAndKanban()
    {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(58));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array());

        stub($this->hierarchy)->flatten()->returns(array(12,78,68));

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban()
    {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array());
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(45,68));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInKanban()
    {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array());
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(98,63));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }
}
