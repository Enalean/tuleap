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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
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
    private VerifyIterationsFeatureActiveStub $feature_flag_verifier;
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

        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->user_stories_planner       = $this->createMock(PlanUserStoriesInMirroredProgramIncrements::class);
        $this->feature_remover            = $this->createMock(RemovePlannedFeaturesFromTopBacklog::class);
        $this->feature_flag_verifier      = VerifyIterationsFeatureActiveStub::withActiveFeature();
        $this->logger                     = new TestLogger();
    }

    private function getHandler(): ArtifactUpdatedHandler
    {
        return new ArtifactUpdatedHandler(
            $this->program_increment_verifier,
            $this->user_stories_planner,
            $this->feature_remover,
            $this->feature_flag_verifier,
            $this->logger,
        );
    }

    public function testItCleansUpTopBacklogAndPlansUserStoriesAndCreatesIterationMirrors(): void
    {
        $this->user_stories_planner->expects(self::once())->method('plan');
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->logger->hasDebug('Program increment artifact has been updated'));
    }

    public function testItOnlyCleansUpTopBacklogWhenArtifactIsNotAProgramIncrement(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();
        $this->user_stories_planner->expects(self::never())->method('plan');
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');

        $this->getHandler()->handle($this->event);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNotCreateIterationMirrorsWhenIterationsAreDisabled(): void
    {
        $this->feature_flag_verifier = VerifyIterationsFeatureActiveStub::withDisabledFeature();
        $this->feature_remover->expects(self::once())->method('removeFeaturesPlannedInAProgramIncrementFromTopBacklog');
        $this->user_stories_planner->method('plan');

        $this->getHandler()->handle($this->event);

        self::assertFalse($this->logger->hasDebugRecords());
    }
}
