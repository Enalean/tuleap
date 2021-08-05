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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class LastChangesetRetrieverTest extends TestCase
{
    private const ITERATION_ID      = 52;
    private const LAST_CHANGESET_ID = 3862;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;
    private IterationIdentifier $iteration;

    protected function setUp(): void
    {
        $this->artifact_factory  = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->changeset_factory = $this->createMock(\Tracker_Artifact_ChangesetFactory::class);

        $this->iteration = IterationIdentifier::fromId(self::ITERATION_ID);
    }

    private function getRetriever(): LastChangesetRetriever
    {
        return new LastChangesetRetriever($this->artifact_factory, $this->changeset_factory);
    }

    public function testItReturnsTheLastChangesetIDOfGivenIteration(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ITERATION_ID)->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $this->changeset_factory->method('getLastChangeset')->willReturn(
            new \Tracker_Artifact_Changeset(self::LAST_CHANGESET_ID, $artifact, 101, 1234567890, '')
        );

        $last_changeset_id = $this->getRetriever()->retrieveLastChangesetId($this->iteration);
        self::assertSame(self::LAST_CHANGESET_ID, $last_changeset_id);
    }

    public function testItReturnsNullWhenGivenIterationCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        self::assertNull($this->getRetriever()->retrieveLastChangesetId($this->iteration));
    }

    public function testItReturnsNullWhenGivenIterationHasNoLastChangeset(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ITERATION_ID)->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $this->changeset_factory->method('getLastChangeset')->willReturn(null);

        self::assertNull($this->getRetriever()->retrieveLastChangesetId($this->iteration));
    }
}
