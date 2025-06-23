<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsAnExceptionIfPostActionsAreNotEligible(): void
    {
        $this->expectException(PostActionNonEligibleForTrackerException::class);

        $tracker      = $this->createMock(Tracker::class);
        $post_actions = $this->createMock(PostActionCollection::class);

        $tracker_checker = new TrackerChecker(EventDispatcherStub::withCallback(
            static function (CheckPostActionsForTracker $event): CheckPostActionsForTracker {
                $event->setPostActionsNonEligible();

                return $event;
            }
        ));
        $tracker_checker->checkPostActionsAreEligibleForTracker($tracker, $post_actions);
    }

    public function testItDoesNotThrowAnExceptionIfPostActionsAreEligible(): void
    {
        $this->expectNotToPerformAssertions();

        $tracker      = $this->createMock(Tracker::class);
        $post_actions = $this->createMock(PostActionCollection::class);

        $tracker_checker = new TrackerChecker(EventDispatcherStub::withIdentityCallback());
        $tracker_checker->checkPostActionsAreEligibleForTracker($tracker, $post_actions);
    }
}
