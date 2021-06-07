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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewArtifactHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramIncrementCreatorChecker
     */
    private $program_increment_creator_checker;

    protected function setUp(): void
    {
        $this->program_increment_creator_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
    }

    public function testItDisablesArtifactSubmission(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->program_increment_creator_checker->method('canCreateAProgramIncrement')->willReturn(false);

        $handler = new CanSubmitNewArtifactHandler(
            BuildProgramStub::stubValidProgram(),
            $this->program_increment_creator_checker
        );
        $handler->handle($event);
        self::assertFalse($event->canSubmitNewArtifact());
    }

    public function testItAllowsArtifactSubmissionWhenChecksAreValid(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $this->program_increment_creator_checker->method('canCreateAProgramIncrement')->willReturn(true);

        $handler = new CanSubmitNewArtifactHandler(
            BuildProgramStub::stubValidProgram(),
            $this->program_increment_creator_checker
        );
        $handler->handle($event);
        self::assertTrue($event->canSubmitNewArtifact());
    }

    public function testItAllowsArtifactSubmissionWhenProjectIsNotAProgram(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $event   = new CanSubmitNewArtifact($user, $tracker);

        $handler = new CanSubmitNewArtifactHandler(
            BuildProgramStub::stubInvalidProgram(),
            $this->program_increment_creator_checker
        );
        $handler->handle($event);
        self::assertTrue($event->canSubmitNewArtifact());
    }
}
