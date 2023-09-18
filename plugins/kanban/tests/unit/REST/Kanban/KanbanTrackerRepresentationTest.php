<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class KanbanTrackerRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsRepresentation(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(789)
            ->withName('Bug')
            ->withShortName('bug')
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn($tracker);

        $kanban = $this->createMock(\Tuleap\Kanban\Kanban::class);
        $kanban->method('getTrackerId')->willReturn(789);

        $representation = KanbanTrackerRepresentation::fromKanban($tracker_factory, $kanban);
        self::assertEquals(789, $representation->id);
        self::assertEquals('Bug', $representation->label);
        self::assertEquals('bug', $representation->item_name);
    }

    public function testCannotBuildRepresentationWhenTheTrackerAssociatedWithTheKanbanCannotBeFound(): void
    {
        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn(null);
        $kanban = $this->createMock(\Tuleap\Kanban\Kanban::class);
        $kanban->method('getId')->willReturn(999);
        $kanban->method('getTrackerId')->willReturn(404);

        $this->expectException(\RuntimeException::class);
        KanbanTrackerRepresentation::fromKanban($tracker_factory, $kanban);
    }
}
