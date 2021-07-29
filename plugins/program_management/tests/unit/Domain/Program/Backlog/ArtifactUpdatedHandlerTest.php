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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationReplicationScheduler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationsFeatureActiveStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactUpdatedHandlerTest extends TestCase
{
    private ArtifactUpdatedProxy $event;
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
        $tracker     = TrackerTestBuilder::aTracker()->withId(93)->build();
        $artifact    = ArtifactTestBuilder::anArtifact(87)->inTracker($tracker)->build();
        $this->event = ArtifactUpdatedProxy::fromArtifactUpdated(
            new ArtifactUpdated(
                $artifact,
                UserTestBuilder::aUser()->build(),
                ProjectTestBuilder::aProject()->build(),
            )
        );

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
            new IterationReplicationScheduler(
                VerifyIterationsFeatureActiveStub::withActiveFeature(),
                SearchIterationsStub::withIterationIds(101, 102),
                VerifyIsVisibleArtifactStub::withVisibleIds(101, 102),
                VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
                $this->logger
            )
        );
    }

    public function testItCleansUpTopBacklogAndPlansUserStoriesAndCreatesIterationMirrors(): void
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
