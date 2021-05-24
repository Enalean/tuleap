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

use Mockery;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_List;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;

final class ProgramIncrementsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramIncrementsDAO
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var ProgramIncrementsRetriever
     */
    private $retriever;
    /**
     * @var NullLogger
     */
    private $logger;

    protected function setUp(): void
    {
        $this->dao                        = Mockery::mock(ProgramIncrementsDAO::class);
        $this->artifact_factory           = Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);

        $this->logger    = new NullLogger();
        $this->retriever = new ProgramIncrementsRetriever(
            $this->dao,
            $this->artifact_factory,
            $this->semantic_timeframe_builder,
            $this->logger
        );
    }

    public function testCanRetrievesOpenProgramIncrements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 14], ['id' => 15]]);
        $artifact_14 = Mockery::mock(Artifact::class);
        $artifact_14->shouldReceive('getId')->andReturn(14);
        $artifact_14->shouldReceive('userCanUpdate')->andReturnTrue();
        $artifact_14->shouldReceive('getUri')->andReturn("/plugins/tracker/?aid=14");
        $artifact_14->shouldReceive('getXref')->andReturn("art #14");
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('userCanUpdate')->andReturnFalse();
        $artifact_14->shouldReceive('getAnArtifactLinkField')->andReturn($field);
        $artifact_15 = Mockery::mock(Artifact::class);
        $artifact_15->shouldReceive('getId')->andReturn(15);
        $artifact_15->shouldReceive('userCanUpdate')->andReturnFalse();
        $artifact_15->shouldReceive('getUri')->andReturn("/plugins/tracker/?aid=15");
        $artifact_15->shouldReceive('getXref')->andReturn("art #15");
        $artifact_15->shouldReceive('getAnArtifactLinkField')->andReturnNull();
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 14)->andReturn($artifact_14);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 15)->andReturn($artifact_15);

        $artifact_14->shouldReceive('getTitle')->andReturn('Artifact 14');
        $artifact_15->shouldReceive('getTitle')->andReturn('Artifact 15');
        $tracker        = Mockery::mock(\Tracker::class);
        $time_period_14 = \TimePeriodWithoutWeekEnd::buildFromDuration(1611067637, 10);
        $time_period_15 = \TimePeriodWithoutWeekEnd::buildFromDuration(1631067637, 10);
        foreach ([$artifact_14, $artifact_15] as $mock_artifact) {
            $mock_artifact->shouldReceive('getTracker')->andReturn($tracker);
            $mock_artifact->shouldReceive('getStatus')->andReturn('Open');
        }

        $timeframe_calculator = Mockery::mock(TimeframeWithDuration::class);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(new SemanticTimeframe($tracker, $timeframe_calculator));


        $timeframe_calculator->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($artifact_14, $user, $this->logger)
            ->andReturn($time_period_14);
        $timeframe_calculator->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($artifact_15, $user, $this->logger)
            ->andReturn($time_period_15);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $tracker->shouldReceive('getStatusField')->andReturn($status_field);

        $program_increments = $this->retriever->retrieveOpenProgramIncrements(self::buildProgram($user), $user);

        self::assertEquals(
            [
                new ProgramIncrement($artifact_15->getId(), 'Artifact 15', $artifact_15->getUri(), $artifact_15->getXRef(), false, false, 'Open', $time_period_15->getStartDate(), $time_period_15->getEndDate()),
                new ProgramIncrement($artifact_14->getId(), 'Artifact 14', $artifact_14->getUri(), $artifact_14->getXRef(), true, false, 'Open', $time_period_14->getStartDate(), $time_period_14->getEndDate()),
            ],
            $program_increments
        );
    }

    public function testDoesNotRetrieveArtifactsTheUserCannotRead(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 403]]);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn(null);

        self::assertEmpty($this->retriever->retrieveOpenProgramIncrements(self::buildProgram($user), $user));
    }

    public function testDoesNotRetrieveArtifactsWhereTheUserCannotReadTheTitle(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 16]]);
        $artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $artifact->shouldReceive('getTitle')->andReturn(null);

        self::assertEmpty($this->retriever->retrieveOpenProgramIncrements(self::buildProgram($user), $user));
    }

    private static function buildProgram(\PFUser $user): ProgramIdentifier
    {
        return ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 1, $user);
    }
}
