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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\ChangesetNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\Tracker\Artifact\Artifact;

final class ChangesetRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SUBMISSION_TIMESTAMP = 1631191239;
    private const ARTIFACT_ID          = 470;
    private const CHANGESET_ID         = 5180;
    private Stub|\Tracker_ArtifactFactory $artifact_factory;
    private DomainChangeset $changeset_identifier;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);

        $this->changeset_identifier = DomainChangeset::fromId(
            VerifyIsChangesetStub::withValidChangeset(),
            self::CHANGESET_ID
        );
    }

    private function getRetriever(): ChangesetRetriever
    {
        return new ChangesetRetriever($this->artifact_factory);
    }

    public function testItRetrievesTheChangesetsSubmissionDate(): void
    {
        $artifact  = $this->createStub(Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(
            self::CHANGESET_ID,
            $artifact,
            155,
            self::SUBMISSION_TIMESTAMP,
            null
        );
        $artifact->method('getChangeset')->willReturn($changeset);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $date = $this->getRetriever()->getSubmissionDate(self::ARTIFACT_ID, $this->changeset_identifier);
        self::assertSame(self::SUBMISSION_TIMESTAMP, $date->getValue());
    }

    public function testItThrowsWhenArtifactCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(ArtifactNotFoundException::class);
        $this->getRetriever()->getSubmissionDate(self::ARTIFACT_ID, $this->changeset_identifier);
    }

    public function testItThrowsWhenChangesetCantBeFound(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getChangeset')->willReturn(null);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $this->expectException(ChangesetNotFoundException::class);
        $this->getRetriever()->getSubmissionDate(self::ARTIFACT_ID, $this->changeset_identifier);
    }
}
