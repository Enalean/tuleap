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

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;

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
     * @var ProgramIncrementsRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao              = Mockery::mock(ProgramIncrementsDAO::class);
        $this->artifact_factory = Mockery::mock(\Tracker_ArtifactFactory::class);

        $this->retriever = new ProgramIncrementsRetriever($this->dao, $this->artifact_factory);
    }

    public function testCanRetrievesOpenProgramIncrements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 14], ['id' => 15]]);
        $artifact_14 = Mockery::mock(Artifact::class);
        $artifact_15 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 14)->andReturn($artifact_14);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 15)->andReturn($artifact_15);

        $artifact_14->shouldReceive('getTitle')->andReturn('Artifact 14');
        $artifact_15->shouldReceive('getTitle')->andReturn('Artifact 15');
        $tracker = Mockery::mock(\Tracker::class);
        foreach ([$artifact_14, $artifact_15] as $mock_artifact) {
            $mock_artifact->shouldReceive('getTracker')->andReturn($tracker);
        }
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('userCanRead')->andReturn(true);
        $tracker->shouldReceive('getStatusField')->andReturn($status_field);

        $program_increments = $this->retriever->retrieveOpenProgramIncrements(self::buildProgram(), $user);

        self::assertEquals(
            [new ProgramIncrement('Artifact 14'), new ProgramIncrement('Artifact 15')],
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

    public function testDoesNotRetrieveArtifactsWhereTheUserCannotReadTheStatus(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->dao->shouldReceive('searchOpenProgramIncrements')->andReturn([['id' => 16]]);
        $artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $artifact->shouldReceive('getTitle')->andReturn('Title');
        $tracker = Mockery::mock(\Tracker::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('userCanRead')->andReturn(false);
        $tracker->shouldReceive('getStatusField')->andReturn($status_field);

        self::assertEmpty($this->retriever->retrieveOpenProgramIncrements(self::buildProgram(), $user));
    }

    private static function buildProgram(): Program
    {
        return new Program(1);
    }
}
