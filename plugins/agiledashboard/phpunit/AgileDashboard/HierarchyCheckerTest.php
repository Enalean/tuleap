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
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_HierarchyCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();

        $project       = \Mockery::spy(\Project::class, ['getID' => 34, 'getUnixName' => false, 'isPublic' => false]);
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(12);
        $this->tracker->shouldReceive('getProject')->andReturn($project);
        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        $this->kanban_factory    = \Mockery::spy(\AgileDashboard_KanbanFactory::class);
        $this->hierarchy         = \Mockery::spy(\Tracker_Hierarchy::class);
        $this->tracker_factory   = \Mockery::spy(\TrackerFactory::class);

        $this->hierarchy_checker = new AgileDashboard_HierarchyChecker(
            $this->planning_factory,
            $this->kanban_factory,
            $this->tracker_factory
        );
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns(array(78));
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns(array());

        $this->hierarchy->shouldReceive('flatten')->andReturns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns(array());
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns(array(45));

        $this->hierarchy->shouldReceive('flatten')->andReturns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInScrumAndKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns(array(58));
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns(array(45));
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns(array());

        $this->hierarchy->shouldReceive('flatten')->andReturns(array(12,78,68));

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns(array());
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns(array());
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns(array(45,68));
        $this->hierarchy->shouldReceive('flatten')->andReturns(array(12,45,78,68));

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInKanban(): void
    {
        $this->tracker->shouldReceive('getHierarchy')->andReturns($this->hierarchy);
        $this->planning_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturns(array());
        $this->planning_factory->shouldReceive('getBacklogTrackerIdsByGroupId')->andReturns(array());
        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->andReturns(array(98,63));
        $this->hierarchy->shouldReceive('flatten')->andReturns(array(12,45,78,68));

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumOrKanbanHierarchy($this->tracker));
    }
}
