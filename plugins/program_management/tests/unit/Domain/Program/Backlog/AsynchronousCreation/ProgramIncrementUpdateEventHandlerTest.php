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

use Tuleap\ProgramManagement\Tests\Builder\PendingIterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildIterationCreationProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessIterationCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementUpdateEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID                            = 108;
    private const PROGRAM_INCREMENT_ID               = 58;
    private const PROGRAM_INCREMENT_CHANGESET_ID     = 7383;
    private const PROGRAM_INCREMENT_OLD_CHANGESET_ID = 7382;
    private ProcessProgramIncrementUpdateStub $update_processor;
    private ProcessIterationCreationStub $iteration_processor;
    private ProgramIncrementUpdateEventStub $event;

    protected function setUp(): void
    {
        $first_iteration  = PendingIterationCreationBuilder::buildWithIds(196, 5457);
        $second_iteration = PendingIterationCreationBuilder::buildWithIds(532, 3325);
        $this->event      = ProgramIncrementUpdateEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::PROGRAM_INCREMENT_CHANGESET_ID,
            self::PROGRAM_INCREMENT_OLD_CHANGESET_ID,
            $first_iteration,
            $second_iteration
        );

        $this->update_processor    = ProcessProgramIncrementUpdateStub::withCount();
        $this->iteration_processor = ProcessIterationCreationStub::withCount();
    }

    private function getHandler(): ProgramIncrementUpdateEventHandler
    {
        return new ProgramIncrementUpdateEventHandler(
            RetrieveProgramIncrementTrackerStub::withValidTracker(36),
            RetrieveIterationTrackerStub::withValidTracker(9),
            BuildProgramIncrementUpdateProcessorStub::withProcessor($this->update_processor),
            BuildIterationCreationProcessorStub::withProcessor($this->iteration_processor)
        );
    }

    public function testItProcessesValidEvent(): void
    {
        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertSame(2, $this->iteration_processor->getCallCount());
    }

    public function testItDoesNothingWhenEventIsNull(): void
    {
        $this->getHandler()->handle(null);

        self::assertSame(0, $this->update_processor->getCallCount());
        self::assertSame(0, $this->iteration_processor->getCallCount());
    }

    public function testItOnlyProcessesUpdateWhenIterationsAreEmpty(): void
    {
        $event = ProgramIncrementUpdateEventStub::withNoIterations(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::PROGRAM_INCREMENT_CHANGESET_ID,
            self::PROGRAM_INCREMENT_OLD_CHANGESET_ID
        );

        $this->getHandler()->handle($event);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertSame(0, $this->iteration_processor->getCallCount());
    }
}
