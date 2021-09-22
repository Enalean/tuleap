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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanPlanInProgramIncrementStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;

final class ProgramIncrementsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private NullLogger $logger;
    private UserIdentifier $user_identifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProgramIncrementsDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->dao                        = $this->createMock(ProgramIncrementsDAO::class);
        $this->artifact_factory           = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $this->logger                     = new NullLogger();
        $this->user                       = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(101);
        $this->user->method('getName')->willReturn("John");
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
    }

    public function testCanRetrievesOpenProgramIncrements(): void
    {
        $this->dao->method('searchOpenProgramIncrements')->willReturn([['id' => 14], ['id' => 15]]);
        $artifact_14 = $this->createMock(Artifact::class);
        $artifact_14->method('getId')->willReturn(14);
        $artifact_14->method('userCanUpdate')->willReturn(true);
        $artifact_14->method('getUri')->willReturn("/plugins/tracker/?aid=14");
        $artifact_14->method('getXref')->willReturn("art #14");
        $field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->method('userCanUpdate')->willReturn(false);
        $artifact_14->method('getAnArtifactLinkField')->willReturn($field);
        $artifact_15 = $this->createMock(Artifact::class);
        $artifact_15->method('getId')->willReturn(15);
        $artifact_15->method('userCanUpdate')->willReturn(false);
        $artifact_15->method('getUri')->willReturn("/plugins/tracker/?aid=15");
        $artifact_15->method('getXref')->willReturn("art #15");
        $artifact_15->method('getAnArtifactLinkField')->willReturn(null);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnOnConsecutiveCalls($artifact_15, $artifact_14);

        $artifact_14->method('getTitle')->willReturn('Artifact 14');
        $artifact_15->method('getTitle')->willReturn('Artifact 15');
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getGroupId')->willReturn(101);
        $time_period_14 = \TimePeriodWithoutWeekEnd::buildFromDuration(1611067637, 10);
        $time_period_15 = \TimePeriodWithoutWeekEnd::buildFromDuration(1631067637, 10);
        foreach ([$artifact_14, $artifact_15] as $mock_artifact) {
            $mock_artifact->method('getTracker')->willReturn($tracker);
            $mock_artifact->method('getStatus')->willReturn('Open');
        }

        $timeframe_calculator = $this->createMock(TimeframeWithDuration::class);

        $this->semantic_timeframe_builder->method('getSemantic')
            ->with($tracker)
            ->willReturn(new SemanticTimeframe($tracker, $timeframe_calculator));


        $timeframe_calculator->method('buildTimePeriodWithoutWeekendForArtifactForREST')->willReturnOnConsecutiveCalls($time_period_15, $time_period_14);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanRead')->willReturn(true);
        $tracker->method('getStatusField')->willReturn($status_field);

        $program_increments = $this->buildProgramIncrementsRetriever(
            VerifyUserCanPlanInProgramIncrementStub::buildCanNotPlan()
        )->retrieveOpenProgramIncrements(self::buildProgram($this->user_identifier), $this->user_identifier);

        self::assertEquals(
            [
                new ProgramIncrement(
                    $artifact_15->getId(),
                    'Artifact 15',
                    $artifact_15->getUri(),
                    $artifact_15->getXRef(),
                    false,
                    false,
                    'Open',
                    $time_period_15->getStartDate(),
                    $time_period_15->getEndDate()
                ),
                new ProgramIncrement(
                    $artifact_14->getId(),
                    'Artifact 14',
                    $artifact_14->getUri(),
                    $artifact_14->getXRef(),
                    true,
                    false,
                    'Open',
                    $time_period_14->getStartDate(),
                    $time_period_14->getEndDate()
                ),
            ],
            $program_increments
        );
    }

    public function testDoesNotRetrieveArtifactsTheUserCannotRead(): void
    {
        $this->dao->method('searchOpenProgramIncrements')->willReturn([['id' => 403]]);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        self::assertEmpty(
            $this->buildProgramIncrementsRetriever(VerifyUserCanPlanInProgramIncrementStub::buildCanPlan())->retrieveOpenProgramIncrements(
                self::buildProgram($this->user_identifier),
                $this->user_identifier
            )
        );
    }

    public function testDoesNotRetrieveArtifactsWhereTheUserCannotReadTheTitle(): void
    {
        $this->dao->method('searchOpenProgramIncrements')->willReturn([['id' => 16]]);
        $artifact = $this->createMock(Artifact::class);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $artifact->method('getTitle')->willReturn(null);

        self::assertEmpty(
            $this->buildProgramIncrementsRetriever(VerifyUserCanPlanInProgramIncrementStub::buildCanPlan())->retrieveOpenProgramIncrements(
                self::buildProgram($this->user_identifier),
                $this->user_identifier
            )
        );
    }

    private static function buildProgram(UserIdentifier $user): ProgramIdentifier
    {
        return ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 1, $user, null);
    }

    private function buildProgramIncrementsRetriever(VerifyUserCanPlanInProgramIncrement $user_can_plan): ProgramIncrementsRetriever
    {
        return new ProgramIncrementsRetriever(
            $this->dao,
            $this->artifact_factory,
            $this->semantic_timeframe_builder,
            $this->logger,
            RetrieveUserStub::buildMockedRegularUser($this->user),
            $user_can_plan,
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts()
        );
    }
}
