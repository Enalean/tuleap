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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerFromUserStoryRetrieverTest extends TestCase
{
    private TrackerFromUserStoryRetriever $tracker_id_retriever;
    private UserStoryIdentifier $user_story_identifier;
    private Artifact $artifact;
    private RetrieveFullArtifactStub $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact              = ArtifactTestBuilder::anArtifact(1)
                                                          ->inTracker(TrackerTestBuilder::aTracker()->withId(10)->build())->build();
        $this->artifact_factory      = RetrieveFullArtifactStub::withArtifact($this->artifact);
        $this->tracker_id_retriever  = new TrackerFromUserStoryRetriever($this->artifact_factory);
        $this->user_story_identifier = UserStoryIdentifierBuilder::withId(2);
    }

    public function testItReturnsValue(): void
    {
        self::assertEquals(10, $this->tracker_id_retriever->getTracker($this->user_story_identifier)->getId());
    }
}
