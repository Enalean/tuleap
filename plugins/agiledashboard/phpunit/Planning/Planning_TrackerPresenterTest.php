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

final class Planning_TrackerPresenterTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Planning_TrackerPresenter
     */
    private $presenter;
    /**
     * @var int
     */
    private $other_tracker_id;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning
     */
    private $planning;

    protected function setUp(): void
    {
        $this->planning = \Mockery::spy(\Planning::class);
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(10);
        $this->tracker->shouldReceive('getName')->andReturn("name");
        $this->tracker_id       = $this->tracker->getId();
        $this->other_tracker_id = $this->tracker->getId() + 1;
        $this->presenter        = new Planning_TrackerPresenter($this->planning, $this->tracker);
    }

    public function testItHasAnId(): void
    {
        $this->assertEquals($this->presenter->getId(), $this->tracker_id);
    }

    public function testItHasAName(): void
    {
        $this->assertEquals($this->presenter->getName(), $this->tracker->getName());
    }

    private function assertSelected($selected): void
    {
        $this->assertTrue($selected);
    }

    private function assertNotSelected($selected): void
    {
        $this->assertFalse($selected);
    }

    public function testItIsSelectedAsABacklogTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns([$this->tracker_id]);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->other_tracker_id);
        $this->assertSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsNotSelectedAsAPlanningTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns([$this->tracker_id]);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }

    public function testItIsNotSelectedAsABacklogTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns([$this->other_tracker_id]);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsSelectedAsPlanningTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns([$this->other_tracker_id]);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->tracker_id);
        $this->assertSelected($this->presenter->selectedIfPlanningTracker());
    }

    public function testItIsNotSelectedAsABacklogOrPlanningTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns([$this->other_tracker_id]);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfBacklogTracker());
    }

    public function testItIsNotSelectedAsAPlanningOrBacklogTracker(): void
    {
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturns(array($this->other_tracker_id));
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturns($this->other_tracker_id);
        $this->assertNotSelected($this->presenter->selectedIfPlanningTracker());
    }
}
