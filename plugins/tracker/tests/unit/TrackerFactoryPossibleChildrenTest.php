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

declare(strict_types=1);

use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFactoryPossibleChildrenTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use GlobalResponseMock;

    public function testGetPossibleChildrenShouldNotContainSelf(): void
    {
        $current_tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $bugs_tracker    = TrackerTestBuilder::aTracker()->withId(2)->build();
        $tasks_tracker   = TrackerTestBuilder::aTracker()->withId(3)->build();

        $expected_children = [
            '2' => $bugs_tracker,
            '3' => $tasks_tracker,
        ];

        $all_project_trackers      = $expected_children;
        $all_project_trackers['1'] = $current_tracker;

        $tracker_factory = $this->createPartialMock(\TrackerFactory::class, ['getTrackersByGroupId']);
        $tracker_factory->method('getTrackersByGroupId')->willReturn($all_project_trackers);

        $possible_children = $tracker_factory->getPossibleChildren($current_tracker);

        $this->assertEquals($expected_children, $possible_children);
    }
}
