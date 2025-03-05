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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker;

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerOfArtifactRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 550;
    private const TRACKER_ID  = 908;

    private function getRetriever(): TrackerOfArtifactRetriever
    {
        $full_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->build();
        $artifact     = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->inTracker($full_tracker)
            ->build();

        return new TrackerOfArtifactRetriever(
            RetrieveFullArtifactStub::withArtifact($artifact)
        );
    }

    public function testItReturnsTrackerOfArtifact(): void
    {
        $tracker = $this->getRetriever()->getTrackerOfArtifact(ArtifactIdentifierStub::withId(self::ARTIFACT_ID));
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }

    public function testItReturnsTrackerOfUserStory(): void
    {
        $tracker = $this->getRetriever()->getUserStoryTracker(UserStoryIdentifierBuilder::withId(self::ARTIFACT_ID));
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }

    public function testItReturnsTrackerOfFeature(): void
    {
        $tracker = $this->getRetriever()->getFeatureTracker(FeatureIdentifierBuilder::withId(self::ARTIFACT_ID));
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }
}
