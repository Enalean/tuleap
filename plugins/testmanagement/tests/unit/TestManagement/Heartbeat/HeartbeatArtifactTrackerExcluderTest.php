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

namespace Tuleap\TestManagement\Heartbeat;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Heartbeat\ExcludeTrackersFromArtifactHeartbeats;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HeartbeatArtifactTrackerExcluderTest extends TestCase
{
    public function testTestCampaignAndDefinitionsTrackersAreExcludedFromTheArtifactHeartbeats(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getCampaignTrackerId')->willReturn(123);
        $config->method('getTestExecutionTrackerId')->willReturn(321);

        $event = new ExcludeTrackersFromArtifactHeartbeats(ProjectTestBuilder::aProject()->build());

        $excluder = new HeartbeatArtifactTrackerExcluder();
        $excluder->excludeTrackers($config, $event);

        self::assertEqualsCanonicalizing([123, 321], $event->getExcludedTrackerIDs());
    }

    public function testDoesNotAddUnconfiguredTracker(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getCampaignTrackerId')->willReturn(false);
        $config->method('getTestExecutionTrackerId')->willReturn(false);

        $event = new ExcludeTrackersFromArtifactHeartbeats(ProjectTestBuilder::aProject()->build());

        $excluder = new HeartbeatArtifactTrackerExcluder();
        $excluder->excludeTrackers($config, $event);

        self::assertEquals([], $event->getExcludedTrackerIDs());
    }
}
