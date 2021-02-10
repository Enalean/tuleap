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
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class ProgramIncrementsRetrieverTest extends TestCase
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimeframeBuilder
     */
    private $timeframe_builder;
    /**
     * @var ProgramIncrementsRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao               = Mockery::mock(ProgramIncrementsDAO::class);
        $this->artifact_factory  = Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->timeframe_builder = Mockery::mock(TimeframeBuilder::class);

        $this->retriever = new ProgramIncrementsRetriever($this->dao, $this->artifact_factory, $this->timeframe_builder);
    }

    public function testCanRetrievesOpenProgramIncrements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 14], ['id' => 15]]);
        $artifact_14 = Mockery::mock(Artifact::class);
        $artifact_14->shouldReceive('getId')->andReturn(14);
        $artifact_15 = Mockery::mock(Artifact::class);
        $artifact_15->shouldReceive('getId')->andReturn(15);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 14)->andReturn($artifact_14);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 15)->andReturn($artifact_15);

        $artifact_14->shouldReceive('getTitle')->andReturn('Artifact 14');
        $artifact_15->shouldReceive('getTitle')->andReturn('Artifact 15');
        $tracker     = Mockery::mock(\Tracker::class);
        $time_period = \TimePeriodWithoutWeekEnd::buildFromDuration(1611067637, 10);
        foreach ([$artifact_14, $artifact_15] as $mock_artifact) {
            $mock_artifact->shouldReceive('getTracker')->andReturn($tracker);
            $mock_artifact->shouldReceive('getStatus')->andReturn('Open');
            $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
                ->with($mock_artifact, $user)
                ->andReturn($time_period);
        }
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $tracker->shouldReceive('getStatusField')->andReturn($status_field);

        $program_increments = $this->retriever->retrieveOpenProgramIncrements(self::buildProgram(), $user);

        self::assertEquals(
            [
                new ProgramIncrement($artifact_14->getId(), 'Artifact 14', 'Open', $time_period->getStartDate(), $time_period->getEndDate()),
                new ProgramIncrement($artifact_15->getId(), 'Artifact 15', 'Open', $time_period->getStartDate(), $time_period->getEndDate()),
            ],
            $program_increments
        );
    }

    public function testDoesNotRetrieveArtifactsTheUserCannotRead(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 403]]);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn(null);

        self::assertEmpty($this->retriever->retrieveOpenProgramIncrements(self::buildProgram(), $user));
    }

    public function testDoesNotRetrieveArtifactsWhereTheUserCannotReadTheTitle(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 16]]);
        $artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $artifact->shouldReceive('getTitle')->andReturn(null);

        self::assertEmpty($this->retriever->retrieveOpenProgramIncrements(self::buildProgram(), $user));
    }

    private static function buildProgram(): Program
    {
        return new Program(1);
    }
}
