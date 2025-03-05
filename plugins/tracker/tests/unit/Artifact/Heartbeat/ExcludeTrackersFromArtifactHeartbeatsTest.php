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

namespace Tuleap\Tracker\Artifact\Heartbeat;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExcludeTrackersFromArtifactHeartbeatsTest extends TestCase
{
    public function testDefaultsToNoExclusion(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $event   = new ExcludeTrackersFromArtifactHeartbeats($project);

        self::assertSame($project, $event->getProject());
        self::assertSame([], $event->getExcludedTrackerIDs());
    }

    public function testTrackersCanBeAddedToTheExclusionList(): void
    {
        $event = new ExcludeTrackersFromArtifactHeartbeats(ProjectTestBuilder::aProject()->build());
        $event->excludeTrackerIDs(3);
        $event->excludeTrackerIDs(2, 1);

        self::assertEqualsCanonicalizing([1, 2, 3], $event->getExcludedTrackerIDs());
    }
}
