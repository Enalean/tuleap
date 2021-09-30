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

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;

final class IterationCreationDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID  = 828;
    private const SECOND_ITERATION_ID = 251;
    private ProgramIncrementUpdate $program_increment_update;
    private VerifyIterationsFeatureActiveStub $feature_flag_verifier;
    private SearchIterationsStub $iterations_searcher;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIterationHasBeenLinkedBeforeStub $iteration_link_verifier;
    private RetrieveLastChangesetStub $changeset_retriever;

    protected function setUp(): void
    {
        $this->program_increment_update = ProgramIncrementUpdateBuilder::build();
        $this->feature_flag_verifier    = VerifyIterationsFeatureActiveStub::withActiveFeature();
        $this->iterations_searcher      = SearchIterationsStub::withIterationIds(
            self::FIRST_ITERATION_ID,
            self::SECOND_ITERATION_ID
        );
        $this->visibility_verifier      = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->iteration_link_verifier  = VerifyIterationHasBeenLinkedBeforeStub::withNoIteration();
        $this->changeset_retriever      = RetrieveLastChangesetStub::withLastChangesetIds(4297, 7872);
    }

    private function getDetector(): IterationCreationDetector
    {
        return new IterationCreationDetector(
            $this->feature_flag_verifier,
            $this->iterations_searcher,
            $this->visibility_verifier,
            $this->iteration_link_verifier,
            new NullLogger(),
            $this->changeset_retriever,
        );
    }

    public function testItReturnsEmptyArrayWhenFeatureFlagIsDisabled(): void
    {
        $this->feature_flag_verifier = VerifyIterationsFeatureActiveStub::withDisabledFeature();

        $creations = $this->getDetector()->detectNewIterationCreations($this->program_increment_update);
        self::assertEmpty($creations);
    }

    public function testItReturnsEmptyArrayWhenProgramIncrementHasNoIteration(): void
    {
        $this->iterations_searcher = SearchIterationsStub::withNoIteration();
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withNoVisibleArtifact();

        $creations = $this->getDetector()->detectNewIterationCreations($this->program_increment_update);
        self::assertEmpty($creations);
    }

    public function testItReturnsEmptyArrayWhenAllIterationsHadBeenLinkedPreviously(): void
    {
        $this->iteration_link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_ITERATION_ID,
            self::SECOND_ITERATION_ID
        );

        $creations = $this->getDetector()->detectNewIterationCreations($this->program_increment_update);
        self::assertEmpty($creations);
    }

    public function testItReturnsIterationCreations(): void
    {
        $creations     = $this->getDetector()->detectNewIterationCreations($this->program_increment_update);
        $iteration_ids = array_map(static fn(IterationCreation $creation): int => $creation->getIteration()->getId(), $creations);

        self::assertContains(self::FIRST_ITERATION_ID, $iteration_ids);
        self::assertContains(self::SECOND_ITERATION_ID, $iteration_ids);
    }
}
