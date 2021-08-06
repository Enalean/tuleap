<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactUpdatedProxyTest extends TestCase
{
    public function testItBuildsFromArtifactUpdated(): void
    {
        $user     = UserTestBuilder::aUser()->withId(110)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(33)->build();
        $artifact = ArtifactTestBuilder::anArtifact(228)->inTracker($tracker)->build();
        $event    = new ArtifactUpdated(
            $artifact,
            $user,
            ProjectTestBuilder::aProject()->build()
        );

        $proxy = ArtifactUpdatedProxy::fromArtifactUpdated($event);
        self::assertSame(228, $proxy->artifact_id);
        self::assertSame(33, $proxy->tracker_id);
        self::assertSame(110, $proxy->user->id);
    }
}
