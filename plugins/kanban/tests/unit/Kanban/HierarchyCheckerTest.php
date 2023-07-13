<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Kanban;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class HierarchyCheckerTest extends TestCase
{
    public function testItReturnsTrueIfATrackerInTheTrackerHierarchyIsUsedInKanban(): void
    {
        $hierarchy = $this->createMock(\Tracker_Hierarchy::class);

        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getHierarchy')->willReturn($hierarchy);
        $tracker->method('getProject')->willReturn($project);

        $kanban_factory = $this->createMock(KanbanFactory::class);
        $kanban_factory->method('getKanbanTrackerIds')->willReturn([45, 68]);
        $hierarchy->method('flatten')->willReturn([12, 45, 78, 68]);

        $hierarchy_checker = new HierarchyChecker($kanban_factory, \TrackerFactory::instance());
        $this->assertTrue($hierarchy_checker->isPartOfKanbanHierarchy($tracker));
    }

    public function testItReturnsFalseIfNoTrackerIsUsedInKanban(): void
    {
        $hierarchy = $this->createMock(\Tracker_Hierarchy::class);

        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getHierarchy')->willReturn($hierarchy);
        $tracker->method('getProject')->willReturn($project);

        $kanban_factory = $this->createMock(KanbanFactory::class);
        $kanban_factory->method('getKanbanTrackerIds')->willReturn([98, 63]);
        $hierarchy->method('flatten')->willReturn([12, 45, 78, 68]);

        $hierarchy_checker = new HierarchyChecker($kanban_factory, \TrackerFactory::instance());
        $this->assertFalse($hierarchy_checker->isPartOfKanbanHierarchy($tracker));
    }
}
