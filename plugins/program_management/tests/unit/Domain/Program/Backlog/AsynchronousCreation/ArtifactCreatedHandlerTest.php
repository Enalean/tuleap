<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatedHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProgramStore
     */
    private $program_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|RunProgramIncrementCreation
     */
    private $asyncronous_runner;

    protected function setUp(): void
    {
        $this->program_store                   = $this->createMock(ProgramStore::class);
        $this->pending_artifact_creation_store = $this->createMock(PendingArtifactCreationStore::class);
        $this->asyncronous_runner              = $this->createMock(RunProgramIncrementCreation::class);
    }

    private function getHandler(VerifyIsProgramIncrementTracker $verifier): ArtifactCreatedHandler
    {
        return new ArtifactCreatedHandler(
            $this->program_store,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_store,
            $verifier,
            new NullLogger()
        );
    }

    public function testHandleDelegatesToAsynchronousMirrorCreator(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_store->method('isProjectAProgramProject')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(1001)->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->pending_artifact_creation_store->expects(self::once())
            ->method('addArtifactToPendingCreation')
            ->with($artifact->getId(), $current_user->getId(), $changeset->getId());

        $this->asyncronous_runner->expects(self::once())->method('executeProgramIncrementsCreation');

        $handler = $this->getHandler(VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement());
        $handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }

    public function testHandleReactsOnlyToArtifactsFromProgramProjects(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_store->expects(self::once())
            ->method('isProjectAProgramProject')
            ->with(101)
            ->willReturn(false);

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->asyncronous_runner->expects(self::never())->method('executeProgramIncrementsCreation');

        $handler = $this->getHandler(VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement());
        $handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }

    public function testHandleReactsOnlyToTrackersThatAreProgramIncrements(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_store->method('isProjectAProgramProject')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->asyncronous_runner->expects(self::never())->method('executeProgramIncrementsCreation');

        $handler = $this->getHandler(VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement());
        $handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }
}
