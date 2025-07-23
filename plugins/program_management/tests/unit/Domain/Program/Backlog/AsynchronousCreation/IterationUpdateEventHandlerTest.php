<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Tests\Stub\BuildIterationUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\IterationUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessIterationUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationUpdateEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IterationUpdateEventHandler $iteration_update_event_handler;
    private ProcessIterationUpdateStub $processor;

    #[\Override]
    protected function setUp(): void
    {
        $iteration_tracker_retriever = RetrieveIterationTrackerStub::withValidTracker(1);

        $this->processor                    = ProcessIterationUpdateStub::withCount();
        $iteration_update_processor_builder = BuildIterationUpdateProcessorStub::withProcessor(
            $this->processor
        );

        $this->iteration_update_event_handler = new IterationUpdateEventHandler(
            $iteration_tracker_retriever,
            $iteration_update_processor_builder
        );
    }

    public function testItDoesNothingIfThereIsNoEvent(): void
    {
        $this->iteration_update_event_handler->handle(null);

        self::assertSame(0, $this->processor->getCallCount());
    }

    public function testItProcessesTheUpdate(): void
    {
        $event = IterationUpdateEventStub::withDefaultValues();
        $this->iteration_update_event_handler->handle($event);

        self::assertSame(1, $this->processor->getCallCount());
    }
}
