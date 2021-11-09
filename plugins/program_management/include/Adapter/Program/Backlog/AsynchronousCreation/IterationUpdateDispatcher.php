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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\DispatchIterationUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;

final class IterationUpdateDispatcher implements DispatchIterationUpdate
{

    public function __construct(
        private LoggerInterface $logger,
        private BuildIterationUpdateProcessor $processor_builder
    ) {
    }

    public function dispatchUpdate(IterationUpdate $update): void
    {
        $this->processUpdateSynchronously($update);
    }

    private function processUpdateSynchronously(
        IterationUpdate $update,
    ): void {
        $this->logger->info(
            sprintf(
                'Synchronous update for iteration #%d',
                $update->getIteration()->getId()
            ),
        );
        $processor = $this->processor_builder->getProcessor();
        $processor->processUpdate($update);
    }
}
