<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewEventProxyTest extends TestCase
{
    private CanSubmitNewArtifact $event;

    protected function setUp(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject($project)
            ->build();

        $this->event = new CanSubmitNewArtifact($user, $tracker);
    }

    public function testItBuildsProxy(): void
    {
        $proxy = CanSubmitNewArtifactEventProxy::buildFromEvent($this->event);

        self::assertSame($this->event->getTracker()->getId(), $proxy->getTrackerReference()->getId());
        self::assertTrue($this->event->canSubmitNewArtifact());
    }

    public function testItDisabledArtifactSubmission(): void
    {
        $proxy = CanSubmitNewArtifactEventProxy::buildFromEvent($this->event);
        $proxy->disableArtifactSubmission();

        self::assertFalse($this->event->canSubmitNewArtifact());
    }
}
