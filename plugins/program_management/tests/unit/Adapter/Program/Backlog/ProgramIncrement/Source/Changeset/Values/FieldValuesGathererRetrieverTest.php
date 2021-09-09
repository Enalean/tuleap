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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElementFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\Tracker\Artifact\Artifact;

final class FieldValuesGathererRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Stub|\Tracker_ArtifactFactory $artifact_factory;
    private Stub|\Tracker_FormElementFactory $factory;
    private ReplicationData $replication;
    private ProgramIncrementUpdate $update;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->factory          = $this->createStub(Tracker_FormElementFactory::class);

        $this->replication = ReplicationDataBuilder::build();
        $this->update      = ProgramIncrementUpdateBuilder::build();
    }

    private function getRetriever(): FieldValuesGathererRetriever
    {
        return new FieldValuesGathererRetriever($this->artifact_factory, $this->factory);
    }

    public function testItReturnsAFieldValuesGatherer(): void
    {
        $changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $artifact  = $this->createStub(Artifact::class);
        $artifact->method('getChangeset')->willReturn($changeset);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $gatherer = $this->getRetriever()->getFieldValuesGatherer($this->replication);
        self::assertNotNull($gatherer);
    }

    public function testItThrowsWhenFullArtifactCannotBeRetrieved(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(PendingArtifactNotFoundException::class);
        $this->getRetriever()->getFieldValuesGatherer($this->replication);
    }

    public function testItThrowsWhenChangesetCannotBeRetrieved(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getChangeset')->willReturn(null);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $this->expectException(PendingArtifactChangesetNotFoundException::class);
        $this->getRetriever()->getFieldValuesGatherer($this->replication);
    }

    public function testItReturnsAFieldValuesGathererForUpdate(): void
    {
        $changeset = $this->createStub(\Tracker_Artifact_Changeset::class);
        $artifact  = $this->createStub(Artifact::class);
        $artifact->method('getChangeset')->willReturn($changeset);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $gatherer = $this->getRetriever()->getGathererFromUpdate($this->update);
        self::assertNotNull($gatherer);
    }

    public function testItThrowsWhenFullArtifactCannotBeRetrievedForUpdate(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(PendingArtifactNotFoundException::class);
        $this->getRetriever()->getGathererFromUpdate($this->update);
    }

    public function testItThrowsWhenChangesetCannotBeRetrievedForUpdate(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getChangeset')->willReturn(null);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $this->expectException(PendingArtifactChangesetNotFoundException::class);
        $this->getRetriever()->getGathererFromUpdate($this->update);
    }
}
