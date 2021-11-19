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

use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;

final class IterationUpdateEventHandler
{
    public function __construct(
        private RetrieveIterationTracker $iteration_tracker_retriever,
        private BuildIterationUpdateProcessor $iteration_update_processor_builder,
    ) {
    }

    public function handle(?IterationUpdateEvent $event): void
    {
        if (! $event) {
            return;
        }

        $update           = IterationUpdate::fromIterationUpdateEvent($this->iteration_tracker_retriever, $event);
        $update_processor = $this->iteration_update_processor_builder->getProcessor();
        $update_processor->processUpdate($update);
    }
}
