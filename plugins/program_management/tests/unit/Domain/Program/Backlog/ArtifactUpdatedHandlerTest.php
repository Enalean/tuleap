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

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationDetector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementUpdateScheduler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreIterationCreations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\DispatchProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\RemovePlannedFeaturesFromTopBacklogStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\StoreProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ArtifactUpdatedHandlerTest extends TestCase
{
    private ArtifactUpdatedEvent $event;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PlanUserStoriesInMirroredProgramIncrements
     */
    private $user_stories_planner;
    private RemovePlannedFeaturesFromTopBacklogStub $feature_remover;
    private StoreProgramIncrementUpdateStub $update_store;
    private DispatchProgramIncrementUpdateStub $update_dispatcher;

    protected function setUp(): void
    {
        $this->event = ArtifactUpdatedEventStub::withIds(87, 93, 194, 4208);

        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->user_stories_planner       = $this->createMock(PlanUserStoriesInMirroredProgramIncrements::class);
        $this->feature_remover            = RemovePlannedFeaturesFromTopBacklogStub::withCount();
        $this->update_store               = StoreProgramIncrementUpdateStub::withCount();
        $this->update_dispatcher          = DispatchProgramIncrementUpdateStub::withCount();
    }

    private function getHandler(): ArtifactUpdatedHandler
    {
        return new ArtifactUpdatedHandler(
            $this->program_increment_verifier,
            $this->user_stories_planner,
            $this->feature_remover,
            new ProgramIncrementUpdateScheduler(
                $this->update_store,
                new IterationCreationDetector(
                    VerifyIterationsFeatureActiveStub::withActiveFeature(),
                    SearchIterationsStub::withIterationIds(101, 102),
                    VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                    VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
                    new NullLogger(),
                    RetrieveLastChangesetStub::withLastChangesetIds(457, 4915),
                ),
                new class implements StoreIterationCreations {
                    public function storeCreations(IterationCreation ...$creations): void
                    {
                        // Side effects
                    }
                },
                $this->update_dispatcher
            )
        );
    }

    public function testItCleansUpTopBacklogAndPlansUserStoriesAndSchedulesProgramIncrementUpdate(): void
    {
        $this->user_stories_planner->expects(self::once())->method('plan');

        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->feature_remover->getCallCount());
        self::assertSame(1, $this->update_store->getCallCount());
        self::assertSame(1, $this->update_dispatcher->getCallCount());
    }

    public function testItOnlyCleansUpTopBacklogWhenArtifactIsNotAProgramIncrement(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();
        $this->user_stories_planner->expects(self::never())->method('plan');

        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->feature_remover->getCallCount());
        self::assertSame(0, $this->update_store->getCallCount());
        self::assertSame(0, $this->update_dispatcher->getCallCount());
    }
}
