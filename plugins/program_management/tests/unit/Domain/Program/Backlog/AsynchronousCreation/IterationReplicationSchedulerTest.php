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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationsFeatureActiveStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class IterationReplicationSchedulerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID  = 828;
    private const SECOND_ITERATION_ID = 251;
    private UserIdentifier $user;
    private ProgramIncrementIdentifier $program_increment;
    private TestLogger $logger;
    private VerifyIterationsFeatureActiveStub $feature_flag_verifier;
    private SearchIterationsStub $iterations_searcher;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIterationHasBeenLinkedBeforeStub $iteration_link_verifier;

    protected function setUp(): void
    {
        $pfuser                        = UserTestBuilder::aUser()->build();
        $this->user                    = UserIdentifier::fromPFUser($pfuser);
        $this->program_increment       = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            902,
            $pfuser
        );
        $this->logger                  = new TestLogger();
        $this->feature_flag_verifier   = VerifyIterationsFeatureActiveStub::withActiveFeature();
        $this->iterations_searcher     = SearchIterationsStub::withIterationIds(
            self::FIRST_ITERATION_ID,
            self::SECOND_ITERATION_ID
        );
        $this->visibility_verifier     = VerifyIsVisibleArtifactStub::withVisibleIds(
            self::FIRST_ITERATION_ID,
            self::SECOND_ITERATION_ID
        );
        $this->iteration_link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withNoIteration();
    }

    private function getScheduler(): IterationReplicationScheduler
    {
        return new IterationReplicationScheduler(
            $this->feature_flag_verifier,
            $this->iterations_searcher,
            $this->visibility_verifier,
            $this->iteration_link_verifier,
            $this->logger
        );
    }

    public function testItDoesNotScheduleAReplicationWhenFeatureFlagIsDisabled(): void
    {
        $this->feature_flag_verifier = VerifyIterationsFeatureActiveStub::withDisabledFeature();
        $this->getScheduler()->replicateIterationsIfNeeded($this->program_increment, $this->user);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNotScheduleAReplicationWhenProgramIncrementHasNoIteration(): void
    {
        $this->iterations_searcher = SearchIterationsStub::withNoIteration();
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withNoVisibleArtifact();
        $this->getScheduler()->replicateIterationsIfNeeded($this->program_increment, $this->user);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNotScheduleAReplicationWhenAllIterationsHadBeenLinkedPreviously(): void
    {
        $this->iteration_link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_ITERATION_ID,
            self::SECOND_ITERATION_ID
        );
        $this->getScheduler()->replicateIterationsIfNeeded($this->program_increment, $this->user);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItSchedulesAReplication(): void
    {
        $this->getScheduler()->replicateIterationsIfNeeded($this->program_increment, $this->user);

        self::assertTrue($this->logger->hasDebug('Program increment has new iterations: [828,251]'));
    }
}
