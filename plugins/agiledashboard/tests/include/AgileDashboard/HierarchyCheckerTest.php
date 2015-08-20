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

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var PFUser */
    private $user;

    public function setUp() {
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

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(78));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array());

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInScrum() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(58));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(45));

        stub($this->hierarchy)->flatten()->returns(array(12,78,68));

        $this->assertFalse($this->hierarchy_checker->isScrumHierarchy($this->tracker));
    }

    public function itReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(45,68));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isKanbanHierarchy($this->tracker));
    }

    public function itReturnsFalseIfNoTrackerIsUsedInKanban() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(98,63));
        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));

        $this->assertFalse($this->hierarchy_checker->isKanbanHierarchy($this->tracker));
    }

    public function itReturnsNoDeniedTrackersIfTheSelectedTrackerIsNotPartOfAnyHierarchyOrConcernedByAgileDashboard() {
        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);
        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(98,63));
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(58));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(42));

        stub($this->hierarchy)->flatten()->returns(array(12,45,78,68));
        $this->assertArrayEmpty($this->hierarchy_checker->getDeniedTrackersForATrackerHierarchy($this->tracker, $this->user));
    }

    public function itReturnsDeniedTrackersIfTheSelectedTrackerIsOfAScrumierarchyOrConcernedByScrum() {
        $tracker42 = aMockTracker()->withId(42)->build();
        stub($tracker42)->getHierarchy()->returns($this->hierarchy);

        $tracker78 = aMockTracker()->withId(78)->build();
        stub($tracker78)->getHierarchy()->returns($this->hierarchy);

        $tracker68 = aMockTracker()->withId(68)->build();
        stub($tracker68)->getHierarchy()->returns($this->hierarchy);

        $hierarchy34_55 = stub('Tracker_Hierarchy')->flatten()->returns(array(34,55));

        $tracker34 = aMockTracker()->withId(34)->build();
        stub($tracker34)->getHierarchy()->returns($hierarchy34_55);

        $tracker55 = aMockTracker()->withId(55)->build();
        stub($tracker55)->getHierarchy()->returns($hierarchy34_55);

        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);

        stub($this->tracker_factory)->getTrackersByGroupIdUserCanView()->returns(array(
            12 => $this->tracker,
            42 => $tracker42,
            78 => $tracker78,
            68 => $tracker68,
            34 => $tracker34,
            55 => $tracker55
        ));

        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(34,55));
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array(12));
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(42));

        stub($this->hierarchy)->flatten()->returns(array(12,42,78,68));

        $this->assertEqual(
            array_keys($this->hierarchy_checker->getDeniedTrackersForATrackerHierarchy($this->tracker, $this->user)),
            array(34,55)
        );
    }

    public function itReturnsDeniedTrackersIfTheSelectedTrackerIsOfAKanbanHierarchyOrConcernedByKanban() {
        $hierarchy42_78_68 = stub('Tracker_Hierarchy')->flatten()->returns(array(42,78,68));

        $tracker42 = aMockTracker()->withId(42)->build();
        stub($tracker42)->getHierarchy()->returns($hierarchy42_78_68);

        $tracker78 = aMockTracker()->withId(78)->build();
        stub($tracker78)->getHierarchy()->returns($hierarchy42_78_68);

        $tracker68 = aMockTracker()->withId(68)->build();
        stub($tracker68)->getHierarchy()->returns($hierarchy42_78_68);

        $tracker34 = aMockTracker()->withId(34)->build();
        stub($tracker34)->getHierarchy()->returns($this->hierarchy);

        $tracker55 = aMockTracker()->withId(55)->build();
        stub($tracker55)->getHierarchy()->returns($this->hierarchy);

        stub($this->tracker)->getHierarchy()->returns($this->hierarchy);

        stub($this->tracker_factory)->getTrackersByGroupIdUserCanView()->returns(array(
            12 => $this->tracker,
            42 => $tracker42,
            78 => $tracker78,
            68 => $tracker68,
            34 => $tracker34,
            55 => $tracker55
        ));

        stub($this->kanban_factory)->getKanbanTrackerIds()->returns(array(12,34,55));
        stub($this->planning_factory)->getPlanningTrackerIdsByGroupId()->returns(array());
        stub($this->planning_factory)->getBacklogTrackerIdsByGroupId()->returns(array(42));

        stub($this->hierarchy)->flatten()->returns(array(12,34,55));
        $this->assertEqual(
            array_keys($this->hierarchy_checker->getDeniedTrackersForATrackerHierarchy($this->tracker, $this->user)),
            array(42,78,68)
        );
    }
}