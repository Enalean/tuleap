<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Kanban\KanbanFactory;

require_once __DIR__ . '/../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_HierarchyCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var KanbanFactory */
    private $kanban_factory;

    /** @var AgileDashboard_HierarchyChecker */
    private $hierarchy_checker;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Hierarchy */
    private $hierarchy;

    /** @var TrackerFactory */
    private $tracker_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $project       = \Mockery::spy(\Project::class, ['getID' => 34, 'getUserName' => false, 'isPublic' => false]);
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(12);
        $this->tracker->shouldReceive('getProject')->andReturn($project);
        $this->planning_factory = \Mockery::spy(\PlanningFactory::class);
        $this->kanban_factory   = \Mockery::spy(\Tuleap\Kanban\KanbanFactory::class);
        $this->hierarchy        = \Mockery::spy(\Tracker_Hierarchy::class);
        $this->tracker_factory  = \Mockery::spy(\TrackerFactory::class);

        $this->hierarchy_checker = new AgileDashboard_HierarchyChecker(
            $this->planning_factory,
            $this->kanban_factory,
            $this->tracker_factory
        );
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns([78]);
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns([]);

        $this->hierarchy->shouldReceive('flatten')->andReturns([12, 45, 78, 68]);

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns([]);
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns([45]);

        $this->hierarchy->shouldReceive('flatten')->andReturns([12, 45, 78, 68]);

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInScrumAndKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns([58]);
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns([45]);
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns([]);

        $this->hierarchy->shouldReceive('flatten')->andReturns([12, 78, 68]);

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns([]);
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns([]);
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns([45, 68]);
        $this->hierarchy->shouldReceive('flatten')->andReturns([12, 45, 78, 68]);

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns([]);
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns([]);
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns([98, 63]);
        $this->hierarchy->shouldReceive('flatten')->andReturns([12, 45, 78, 68]);

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }
}
