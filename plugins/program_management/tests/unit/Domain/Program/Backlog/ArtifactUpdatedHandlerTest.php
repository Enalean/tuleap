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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationDetector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateScheduler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreIterationCreations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactUpdatedHandlerTest extends TestCase
{
    private ArtifactUpdatedEvent $event;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PlanUserStoriesInMirroredProgramIncrements
     */
    private $user_stories_planner;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RemovePlannedFeaturesFromTopBacklog
     */
    private $feature_remover;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $user      = UserTestBuilder::aUser()->withId(124)->build();
        $tracker   = TrackerTestBuilder::aTracker()->withId(93)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(87)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('4208')
            ->ofArtifact($artifact)
            ->submittedBy($user->getId())
            ->build();

        $this->event = ArtifactUpdatedProxy::fromArtifactUpdated(new ArtifactUpdated($artifact, $user, $changeset));

        $this->logger                     = new TestLogger();
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->user_stories_planner       = $this->createMock(PlanUserStoriesInMirroredProgramIncrements::class);
        $this->feature_remover            = $this->createMock(RemovePlannedFeaturesFromTopBacklog::class);
    }

    private function getHandler(): ArtifactUpdatedHandler
    {
        return new ArtifactUpdatedHandler(
            $this->program_increment_verifier,
            $this->user_stories_planner,
            $this->feature_remover,
            new ProgramIncrementUpdateScheduler(
                new class implements StoreProgramIncrementUpdate {
                    public function storeUpdate(ProgramIncrementUpdate $update): void
                    {
                        // Side effects
                    }
                },
                new IterationCreationDetector(
                    VerifyIterationsFeatureActiveStub::withActiveFeature(),
                    SearchIterationsStub::withIterationIds(101, 102),
                    VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                    VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
                    $this->logger,
                    RetrieveLastChangesetStub::withLastChangesetIds(457, 4915),
                ),
                new class implements StoreIterationCreations {
                    public function storeCreations(IterationCreation ...$creations): void
                    {
                        // Side effects
                    }
                },
                new class implements DispatchProgramIncrementUpdate {
                    public function dispatchUpdate(ProgramIncrementUpdate $update, IterationCreation ...$creations): void
                    {
                        // Side effects
                    }
                }
            )
        );
    }

    public function testItCleansUpTopBacklogAndPlansUserStoriesAndSchedulesProgramIncrementUpdate(): void
    {
        $this->user_stories_planner->expects(self::once())->method('plan');
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItOnlyCleansUpTopBacklogWhenArtifactIsNotAProgramIncrement(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();
        $this->user_stories_planner->expects(self::never())->method('plan');
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');

        $this->getHandler()->handle($this->event);

        self::assertFalse($this->logger->hasDebugRecords());
    }
}
