<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_TrackerPresenter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Planning_TrackerPresenterTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Planning_TrackerPresenter $presenter;
    private int $other_tracker_id;
    private int $tracker_id;
    private Tracker $tracker;
    private Planning&MockObject $planning;

    protected function setUp(): void
    {
        $this->planning         = $this->createMock(Planning::class);
        $this->tracker          = TrackerTestBuilder::aTracker()
            ->withId(10)
            ->withName('name')
            ->build();
        $this->tracker_id       = $this->tracker->getId();
        $this->other_tracker_id = $this->tracker->getId() + 1;
        $this->presenter        = new Planning_TrackerPresenter($this->planning, $this->tracker);
    }

    public function testItHasAnId(): void
    {
        self::assertEquals($this->presenter->getId(), $this->tracker_id);
    }

    public function testItHasAName(): void
    {
        self::assertEquals($this->presenter->getName(), $this->tracker->getName());
    }

    private function assertSelected($selected): void
    {
        self::assertTrue($selected);
    }

    private function assertNotSelected($selected): void
    {
        self::assertFalse($selected);
    }

    public function testItIsSelectedAsABacklogTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->other_tracker_id);
        $this->assertSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsNotSelectedAsAPlanningTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }

    public function testItIsNotSelectedAsABacklogTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->other_tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsSelectedAsPlanningTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->other_tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->tracker_id);
        $this->assertSelected($this->presenter->selectedIfPlanningTracker());
    }

    public function testItIsNotSelectedAsABacklogOrPlanningTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->other_tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsNotSelectedAsAPlanningOrBacklogTracker(): void
    {
        $this->planning->method('getBacklogTrackersIds')->willReturn([$this->other_tracker_id]);
        $this->planning->method('getPlanningTrackerId')->willReturn($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }
}
