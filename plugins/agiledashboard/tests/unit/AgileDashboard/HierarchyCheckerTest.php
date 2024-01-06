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

require_once __DIR__ . '/../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_HierarchyCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AgileDashboard_HierarchyChecker $hierarchy_checker;
    private Tracker|\PHPUnit\Framework\MockObject\MockObject $tracker;
    private PlanningFactory|\PHPUnit\Framework\MockObject\Stub $planning_factory;
    private Tracker_Hierarchy|\PHPUnit\Framework\MockObject\Stub $hierarchy;
    private TrackerFactory|\PHPUnit\Framework\MockObject\Stub $tracker_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $project       = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()
            ->withId(34)
            ->withAccessPublic()
            ->build();
        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(12);
        $this->tracker->method('getProject')->willReturn($project);
        $this->planning_factory = $this->createStub(\PlanningFactory::class);
        $this->hierarchy        = $this->createStub(\Tracker_Hierarchy::class);
        $this->tracker_factory  = $this->createStub(\TrackerFactory::class);

        $this->hierarchy_checker = new AgileDashboard_HierarchyChecker(
            $this->planning_factory,
            $this->tracker_factory
        );
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumPlanning(): void
    {
        $this->tracker->method('getHierarchy')->willReturn($this->hierarchy);
        $this->planning_factory->method('getPlanningTrackerIdsByGroupId')->willReturn([78]);
        $this->planning_factory->method('getBacklogTrackerIdsByGroupId')->willReturn([]);

        $this->hierarchy->method('flatten')->willReturn([12, 45, 78, 68]);

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumHierarchy($this->tracker));
    }

    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInScrumBacklog(): void
    {
        $this->tracker->method('getHierarchy')->willReturn($this->hierarchy);
        $this->planning_factory->method('getPlanningTrackerIdsByGroupId')->willReturn([]);
        $this->planning_factory->method('getBacklogTrackerIdsByGroupId')->willReturn([45]);

        $this->hierarchy->method('flatten')->willReturn([12, 45, 78, 68]);

        $this->assertTrue($this->hierarchy_checker->isPartOfScrumHierarchy($this->tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInScrum(): void
    {
        $this->tracker->method('getHierarchy')->willReturn($this->hierarchy);
        $this->planning_factory->method('getPlanningTrackerIdsByGroupId')->willReturn([58]);
        $this->planning_factory->method('getBacklogTrackerIdsByGroupId')->willReturn([45]);

        $this->hierarchy->method('flatten')->willReturn([12, 78, 68]);

        $this->assertFalse($this->hierarchy_checker->isPartOfScrumHierarchy($this->tracker));
    }
}
