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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\ChangesetNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmissionDateRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SUBMISSION_TIMESTAMP = 1631191239;
    private const ARTIFACT_ID          = 470;
    private const CHANGESET_ID         = 5180;
    private ArtifactIdentifierStub $artifact_identifier;
    private DomainChangeset $changeset_identifier;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact_identifier = ArtifactIdentifierStub::withId(self::ARTIFACT_ID);
        $changeset_identifier      = DomainChangeset::fromId(
            VerifyIsChangesetStub::withValidChangeset(),
            self::CHANGESET_ID
        );
        assert($changeset_identifier instanceof DomainChangeset);
        $this->changeset_identifier = $changeset_identifier;
        $this->artifact             = $this->createStub(Artifact::class);
    }

    private function getRetriever(): SubmissionDateRetriever
    {
        return new SubmissionDateRetriever(RetrieveFullArtifactStub::withArtifact($this->artifact));
    }

    public function testItRetrievesTheChangesetsSubmissionDate(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(self::CHANGESET_ID)
            ->ofArtifact($this->artifact)
            ->submittedOn(self::SUBMISSION_TIMESTAMP)
            ->build();
        $this->artifact->method('getChangeset')->willReturn($changeset);

        $date = $this->getRetriever()->getSubmissionDate($this->artifact_identifier, $this->changeset_identifier);
        self::assertSame(self::SUBMISSION_TIMESTAMP, $date->getValue());
    }

    public function testItThrowsWhenChangesetCantBeFound(): void
    {
        $this->artifact->method('getChangeset')->willReturn(null);

        $this->expectException(ChangesetNotFoundException::class);
        $this->getRetriever()->getSubmissionDate($this->artifact_identifier, $this->changeset_identifier);
    }
}
