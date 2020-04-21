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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class TrackerCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event_manager   = Mockery::mock(\EventManager::class);
        $this->tracker_checker = new TrackerChecker($this->event_manager);
    }

    public function testItThrowsAnExceptionIfPostActionsAreNotEligible()
    {
        $this->expectException(PostActionNonEligibleForTrackerException::class);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (CheckPostActionsForTracker $event) {
            $event->setPostActionsNonEligible();
            return true;
        }));

        $tracker      = Mockery::mock(Tracker::class);
        $post_actions = Mockery::mock(PostActionCollection::class);
        $this->tracker_checker->checkPostActionsAreEligibleForTracker($tracker, $post_actions);
    }

    public function testItDoesNotThrowAnExceptionIfPostActionsAreEligible()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (CheckPostActionsForTracker $event) {
            return true;
        }));

        $tracker      = Mockery::mock(Tracker::class);
        $post_actions = Mockery::mock(PostActionCollection::class);
        $this->tracker_checker->checkPostActionsAreEligibleForTracker($tracker, $post_actions);

        $this->addToAssertionCount(1);
    }
}
