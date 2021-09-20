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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RunProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatedHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $artifact_id     = 1;
    private int $current_user_id = 1001;
    private int $changeset_id    = 21;
    private ArtifactCreated $event;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&RunProgramIncrementCreation
     */
    private $asyncronous_runner;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RemovePlannedFeaturesFromTopBacklog
     */
    private $feature_remover;
    private VerifyIsProgram $program_verifier;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;

    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();

        $current_user = UserTestBuilder::aUser()->withId($this->current_user_id)->build();
        $artifact     = new Artifact($this->artifact_id, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset   = new \Tracker_Artifact_Changeset($this->changeset_id, $artifact, 36, 12345678, '');
        $this->event = new ArtifactCreated($artifact, $changeset, $current_user);

        $this->program_increment_verifier      = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->program_verifier                = VerifyIsProgramStub::withValidProgram();
        $this->pending_artifact_creation_store = $this->createMock(PendingArtifactCreationStore::class);
        $this->asyncronous_runner              = $this->createMock(RunProgramIncrementCreation::class);
        $this->feature_remover                 = $this->createMock(RemovePlannedFeaturesFromTopBacklog::class);
    }

    private function getHandler(): ArtifactCreatedHandler
    {
        return new ArtifactCreatedHandler(
            $this->program_verifier,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_store,
            $this->program_increment_verifier,
            $this->feature_remover,
            new NullLogger()
        );
    }

    public function testHandleCleansUpTopBacklogAndDelegatesToAsynchronousMirrorCreator(): void
    {
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');
        $this->pending_artifact_creation_store->expects(self::once())
            ->method('addArtifactToPendingCreation')
            ->with($this->artifact_id, $this->current_user_id, $this->changeset_id);

        $this->asyncronous_runner->expects(self::once())->method('executeProgramIncrementsCreation');

        $this->getHandler()->handle($this->event);
    }

    public function testHandleReactsOnlyToArtifactsFromProgramProjects(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');
        $this->asyncronous_runner->expects(self::never())->method('executeProgramIncrementsCreation');

        $this->getHandler()->handle($this->event);
    }

    public function testHandleReactsOnlyToTrackersThatAreProgramIncrements(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');
        $this->asyncronous_runner->expects(self::never())->method('executeProgramIncrementsCreation');

        $this->getHandler()->handle($this->event);
    }
}
