<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var ProgramIncrementChecker
     */
    private $checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementsDAO
     */
    private $dao;

    protected function setUp(): void
    {
        $this->tracker_artifact_factory = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->dao                      = \Mockery::mock(ProgramIncrementsDAO::class);
        $this->checker                  = new ProgramIncrementChecker(
            $this->tracker_artifact_factory,
            $this->dao
        );
    }

    public function testItThrowAnExceptionWhenIncrementIsNotFound(): void
    {
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->andReturnNull();

        $this->expectException(ProgramIncrementNotFoundException::class);

        $user = UserTestBuilder::aUser()->build();

        $this->checker->checkIsAProgramIncrement(300, $user);
    }

    public function testItThrowAnExceptionWhenUserCanNotSeeTheIncrement(): void
    {
        $program_increment = \Mockery::mock(Artifact::class);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->andReturn($program_increment);

        $program_increment->shouldReceive('userCanView')->once()->andReturnFalse();

        $this->expectException(ProgramIncrementNotFoundException::class);

        $user = UserTestBuilder::aUser()->build();

        $this->checker->checkIsAProgramIncrement(300, $user);
    }

    public function testItDoesNotThrowWhenTrackerIsAProgramIncrement(): void
    {
        $program_increment = \Mockery::mock(Artifact::class);
        $program_increment->shouldReceive('getId')->andReturn(101);
        $program_increment->shouldReceive('getTrackerId')->andReturn(1);

        $tracker = TrackerTestBuilder::aTracker()->withProject(new \Project(['group_id' => 100]))->build();
        $program_increment->shouldReceive('getTracker')->andReturn($tracker);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->andReturn($program_increment);

        $program_increment->shouldReceive('userCanView')->once()->andReturnTrue();

        $this->dao->shouldReceive("isProgramIncrementTracker")->once()->with(1)->andReturnTrue();

        $user = UserTestBuilder::aUser()->build();

        $this->checker->checkIsAProgramIncrement(300, $user);
        $this->addToAssertionCount(1);
    }

    public function testItThrowsIfIsNotProgramIncrementTracker(): void
    {
        $program_increment = \Mockery::mock(Artifact::class);
        $program_increment->shouldReceive('getTrackerId')->andReturn(1);

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->andReturn($program_increment);

        $program_increment->shouldReceive('userCanView')->once()->andReturnTrue();

        $this->dao->shouldReceive("isProgramIncrementTracker")->once()->with(1)->andReturnFalse();

        $this->expectException(ProgramIncrementNotFoundException::class);

        $user = UserTestBuilder::aUser()->build();

        $this->checker->checkIsAProgramIncrement(300, $user);
    }
}
