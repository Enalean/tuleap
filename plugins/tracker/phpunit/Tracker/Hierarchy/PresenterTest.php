<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

final class Tracker_Hierarchy_PresenterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;

    public function testGetPossibleChildrenReturnsAttributesForSelect(): void
    {
        $story = Mockery::mock(Tracker::class);
        $story->shouldReceive('getId')->andReturn(1);
        $story->shouldReceive('getName')->andReturn('Stories');
        $task = Mockery::mock(Tracker::class);
        $task->shouldReceive('getId')->andReturn(2);
        $task->shouldReceive('getName')->andReturn('Tasks');

        $possible_children = [1 => $story, 2 => $task];

        $tracker = \Mockery::spy(\Tracker_Hierarchy_HierarchicalTracker::class);
        $tracker->shouldReceive('getUnhierarchizedTracker')->andReturns(Mockery::spy(Tracker::class));
        $tracker->shouldReceive('hasChild')->with($possible_children[1])->andReturnFalse();
        $tracker->shouldReceive('hasChild')->with($possible_children[2])->andReturnTrue();

        $presenter = new Tracker_Hierarchy_Presenter(
            $tracker,
            $possible_children,
            new TreeNode(),
            [],
            []
        );

        $attributes = $presenter->getPossibleChildren();
        $this->assertEquals('Stories', $attributes[0]['name']);
        $this->assertEquals(1, $attributes[0]['id']);
        $this->assertEquals(null, $attributes[0]['selected']);
        $this->assertEquals('Tasks', $attributes[1]['name']);
        $this->assertEquals(2, $attributes[1]['id']);
        $this->assertEquals('selected="selected"', $attributes[1]['selected']);
    }
}
