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

use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    protected function setUp(): void
    {
        $this->tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
    }

    public function testItThrowAnExceptionWhenIncrementIsNotFound(): void
    {
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn(null);
        $user = UserTestBuilder::aUser()->build();

        $checker = new ProgramIncrementChecker(
            $this->tracker_artifact_factory,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement()
        );
        $this->expectException(ProgramIncrementNotFoundException::class);
        $checker->checkIsAProgramIncrement(300, $user);
    }

    public function testItThrowAnExceptionWhenUserCanNotSeeTheIncrement(): void
    {
        $program_increment = $this->createMock(Artifact::class);
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($program_increment);

        $program_increment->method('userCanView')->willReturn(false);
        $user = UserTestBuilder::aUser()->build();

        $checker = new ProgramIncrementChecker(
            $this->tracker_artifact_factory,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement()
        );
        $this->expectException(ProgramIncrementNotFoundException::class);
        $checker->checkIsAProgramIncrement(300, $user);
    }

    public function testItDoesNotThrowWhenTrackerIsAProgramIncrement(): void
    {
        $program_increment = $this->createMock(Artifact::class);
        $program_increment->method('getId')->willReturn(101);
        $program_increment->method('getTrackerId')->willReturn(1);

        $tracker = TrackerTestBuilder::aTracker()->withProject(new \Project(['group_id' => 100]))->build();
        $program_increment->method('getTracker')->willReturn($tracker);
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($program_increment);

        $program_increment->method('userCanView')->willReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $checker = new ProgramIncrementChecker(
            $this->tracker_artifact_factory,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement()
        );
        $checker->checkIsAProgramIncrement(300, $user);
        $this->addToAssertionCount(1);
    }

    public function testItThrowsIfIsNotProgramIncrementTracker(): void
    {
        $program_increment = $this->createMock(Artifact::class);
        $program_increment->method('getTrackerId')->willReturn(1);
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($program_increment);

        $program_increment->method('userCanView')->willReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $checker = new ProgramIncrementChecker(
            $this->tracker_artifact_factory,
            VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement()
        );
        $this->expectException(ProgramIncrementNotFoundException::class);
        $checker->checkIsAProgramIncrement(300, $user);
    }
}
