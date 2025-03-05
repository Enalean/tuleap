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
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationCreationDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID  = 828;
    private const SECOND_ITERATION_ID = 251;
    private ProgramIncrementUpdate $program_increment_update;
    private SearchIterationsStub $iterations_searcher;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIterationHasBeenLinkedBeforeStub $iteration_link_verifier;

    protected function setUp(): void
    {
        $this->program_increment_update = ProgramIncrementUpdateBuilder::build();
        $this->iterations_searcher      = SearchIterationsStub::withIterations(
            [['id' => self::FIRST_ITERATION_ID, 'changeset_id' => 1],
                ['id' => self::SECOND_ITERATION_ID, 'changeset_id' => 2],
            ]
        );
        $this->visibility_verifier      = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->iteration_link_verifier  = VerifyIterationHasBeenLinkedBeforeStub::withNoIteration();
    }

    private function getDetector(): IterationCreationDetector
    {
        return new IterationCreationDetector(
            $this->iterations_searcher,
            $this->visibility_verifier,
            $this->iteration_link_verifier,
            MessageLog::buildFromLogger(new NullLogger()),
            RetrieveLastChangesetStub::withLastChangesetIds(4297, 7872),
            RetrieveIterationTrackerStub::withValidTracker(4)
        );
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
