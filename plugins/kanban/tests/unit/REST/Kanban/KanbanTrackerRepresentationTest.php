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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class KanbanTrackerRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildsRepresentation(): void
    {
        $tracker         = \Mockery::mock(\Tracker::class);
        $tracker_factory = \Mockery::mock(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);
        $tracker->shouldReceive('getId')->andReturn(789);
        $tracker->shouldReceive('getName')->andReturn('Bug');
        $tracker->shouldReceive('getItemName')->andReturn('bug');
        $tracker->shouldReceive('getProject')->andReturn(ProjectTestBuilder::aProject()->build());

        $kanban = \Mockery::mock(\Tuleap\Kanban\Kanban::class);
        $kanban->shouldReceive('getTrackerId')->andReturn(789);

        $representation = KanbanTrackerRepresentation::fromKanban($tracker_factory, $kanban);
        $this->assertEquals(789, $representation->id);
        $this->assertEquals('Bug', $representation->label);
        $this->assertEquals('bug', $representation->item_name);
    }

    public function testCannotBuildRepresentationWhenTheTrackerAssociatedWithTheKanbanCannotBeFound(): void
    {
        $tracker_factory = \Mockery::mock(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->andReturn(null);
        $kanban = \Mockery::mock(\Tuleap\Kanban\Kanban::class);
        $kanban->shouldReceive('getId')->andReturn(999);
        $kanban->shouldReceive('getTrackerId')->andReturn(404);

        $this->expectException(\RuntimeException::class);
        KanbanTrackerRepresentation::fromKanban($tracker_factory, $kanban);
    }
}
