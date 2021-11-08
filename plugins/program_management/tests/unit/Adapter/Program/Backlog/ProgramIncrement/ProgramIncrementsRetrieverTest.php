<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchOpenProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanPlanInProgramIncrementStub;
use Tuleap\Tracker\Artifact\Artifact;

final class ProgramIncrementsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserIdentifier $user_identifier;
    private SearchOpenProgramIncrement $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->dao              = SearchOpenProgramIncrementStub::withoutOpenIncrement();
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->user             = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(101);
        $this->user->method('getName')->willReturn("John");
        $this->user_identifier = UserReferenceStub::withDefaults();
    }

    public function testCanRetrievesOpenProgramIncrements(): void
    {
        $this->dao   = SearchOpenProgramIncrementStub::with([['id' => 14], ['id' => 15]]);
        $artifact_14 = $this->createMock(Artifact::class);
        $artifact_14->method('getId')->willReturn(14);

        $artifact_15 = $this->createMock(Artifact::class);
        $artifact_15->method('getId')->willReturn(15);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnOnConsecutiveCalls($artifact_15, $artifact_14);

        $this->artifact_factory->expects(self::atLeast(2))->method('getArtifactByIdUserCanView');

        $program_increments = $this->buildProgramIncrementsRetriever(
            RetrieveTitleValueUserCanSeeStub::withValue('Artifact 15')
        )->retrieveOpenProgramIncrements(self::buildProgram($this->user_identifier), $this->user_identifier);

        self::assertCount(2, $program_increments);
    }

    public function testDoesNotRetrieveArtifactsTheUserCannotRead(): void
    {
        $this->dao = SearchOpenProgramIncrementStub::with([['id' => 403]]);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        self::assertEmpty(
            $this->buildProgramIncrementsRetriever(
                RetrieveTitleValueUserCanSeeStub::withValue('Artifact 15')
            )->retrieveOpenProgramIncrements(
                self::buildProgram($this->user_identifier),
                $this->user_identifier
            )
        );
    }

    public function testDoesNotRetrieveArtifactsWhereTheUserCannotReadTheTitle(): void
    {
        $this->dao = SearchOpenProgramIncrementStub::with([['id' => 16]]);
        $artifact  = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(16);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertEmpty(
            $this->buildProgramIncrementsRetriever(
                RetrieveTitleValueUserCanSeeStub::withoutValue()
            )->retrieveOpenProgramIncrements(
                self::buildProgram($this->user_identifier),
                $this->user_identifier
            )
        );
    }

    public function testItRetrievesProgramIncrementsById(): void
    {
        $this->dao = SearchOpenProgramIncrementStub::with([['id' => 16]]);
        $artifact  = $this->createMock(Artifact::class);

        $artifact->method('getId')->willReturn(16);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $program_increment = $this->buildProgramIncrementsRetriever(
            RetrieveTitleValueUserCanSeeStub::withValue("Increment 16")
        )->retrieveProgramIncrementById(
            $this->user_identifier,
            ProgramIncrementIdentifierBuilder::buildWithIdAndUser(16, $this->user_identifier)
        );

        self::assertNotNull($program_increment);
    }

    public function testItDoesRetrievesProgramIncrementsByIdWhenUserCannotSeeIt(): void
    {
        $this->dao = SearchOpenProgramIncrementStub::with([['id' => 16]]);
        $artifact  = $this->createMock(Artifact::class);

        $artifact->method('getId')->willReturn(16);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        $program_increment = $this->buildProgramIncrementsRetriever(
            RetrieveTitleValueUserCanSeeStub::withValue("Increment 16")
        )->retrieveProgramIncrementById(
            $this->user_identifier,
            ProgramIncrementIdentifierBuilder::buildWithIdAndUser(16, $this->user_identifier)
        );

        self::assertNull($program_increment);
    }

    public function testItDoesRetrievesProgramIncrementsByIdWhenUserCannotSeeItsTitle(): void
    {
        $this->dao = SearchOpenProgramIncrementStub::with([['id' => 16]]);
        $artifact  = $this->createMock(Artifact::class);

        $artifact->method('getId')->willReturn(16);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $program_increment = $this->buildProgramIncrementsRetriever(
            RetrieveTitleValueUserCanSeeStub::withoutValue()
        )->retrieveProgramIncrementById(
            $this->user_identifier,
            ProgramIncrementIdentifierBuilder::buildWithIdAndUser(16, $this->user_identifier)
        );

        self::assertNull($program_increment);
    }

    private static function buildProgram(UserIdentifier $user): ProgramIdentifier
    {
        return ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 1, $user, null);
    }

    private function buildProgramIncrementsRetriever(RetrieveTitleValueUserCanSee $retrieve_title): ProgramIncrementsRetriever
    {
        return new ProgramIncrementsRetriever(
            $this->dao,
            $this->artifact_factory,
            RetrieveUserStub::buildMockedRegularUser($this->user),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            $retrieve_title,
            RetrieveTimeframeValueUserCanSeeStub::withValues(1633189968, 1635868368),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withUpdatePermission(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );
    }
}
