<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerFactoryPossibleChildrenTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock, GlobalResponseMock;

    public function testGetPossibleChildrenShouldNotContainSelf()
    {
        $current_tracker = Mockery::mock(Tracker::class);
        $current_tracker->shouldReceive('getId')->andReturn(1);
        $current_tracker->shouldReceive('getName')->andReturn('Stories');
        $current_tracker->shouldReceive('getGroupId')->andReturn(101);

        $bugs_tracker = Mockery::mock(Tracker::class);
        $bugs_tracker->shouldReceive('getId')->andReturn(2);
        $bugs_tracker->shouldReceive('getName')->andReturn('Bugs');

        $tasks_tracker = Mockery::mock(Tracker::class);
        $tasks_tracker->shouldReceive('getId')->andReturn(3);
        $tasks_tracker->shouldReceive('getName')->andReturn('Tasks');

        $expected_children = array(
            '2' => $bugs_tracker,
            '3' => $tasks_tracker,
        );

        $all_project_trackers      = $expected_children;
        $all_project_trackers['1'] = $current_tracker;

        $tracker_factory   = \Mockery::mock(\TrackerFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker_factory->shouldReceive('getTrackersByGroupId')->andReturns($all_project_trackers);

        $possible_children = $tracker_factory->getPossibleChildren($current_tracker);

        $this->assertEquals($expected_children, $possible_children);
    }
}
