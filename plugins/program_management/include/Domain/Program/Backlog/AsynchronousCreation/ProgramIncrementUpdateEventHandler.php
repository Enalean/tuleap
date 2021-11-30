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

use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;

final class ProgramIncrementUpdateEventHandler
{
    public function __construct(
        private RetrieveProgramIncrementTracker $program_increment_tracker_retriever,
        private RetrieveIterationTracker $iteration_tracker_retriever,
        private BuildProgramIncrementUpdateProcessor $update_processor_builder,
        private BuildIterationCreationProcessor $iteration_processor_builder,
    ) {
    }

    public function handle(?ProgramIncrementUpdateEvent $event): void
    {
        if (! $event) {
            return;
        }
        $this->buildAndProcessProgramIncrementUpdate($event);
        $this->buildAndProcessIterationCreations($event);
    }

    private function buildAndProcessProgramIncrementUpdate(ProgramIncrementUpdateEvent $event): void
    {
        $update    = ProgramIncrementUpdate::fromProgramIncrementUpdateEvent(
            $this->program_increment_tracker_retriever,
            $event
        );
        $processor = $this->update_processor_builder->getProcessor();
        $processor->processUpdate($update);
    }

    private function buildAndProcessIterationCreations(ProgramIncrementUpdateEvent $event): void
    {
        $creations = IterationCreation::buildCollectionFromProgramIncrementUpdateEvent(
            $this->iteration_tracker_retriever,
            $event
        );
        if (empty($creations)) {
            return;
        }
        $processor = $this->iteration_processor_builder->getProcessor();
        foreach ($creations as $creation) {
            $processor->processCreation($creation);
        }
    }
}
