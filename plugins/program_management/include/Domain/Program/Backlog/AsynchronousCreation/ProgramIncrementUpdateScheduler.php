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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;

final class ProgramIncrementUpdateScheduler
{
    public function __construct(
        private IterationCreationDetector $iteration_creation_detector,
        private StoreIterationCreations $iteration_store,
        private DispatchProgramIncrementUpdate $update_dispatcher
    ) {
    }

    public function replicateProgramIncrementUpdate(ProgramIncrementUpdate $update): void
    {
        $creations = $this->iteration_creation_detector->detectNewIterationCreations($update);
        $this->iteration_store->storeCreations(...$creations);
        $this->update_dispatcher->dispatchUpdate($update, ...$creations);
    }
}
