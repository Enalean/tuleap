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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Events\IterationCreationEventProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;

final class IterationCreationEventHandler implements ProcessIterationCreation
{
    private LoggerInterface $logger;
    private SearchPendingIteration $iteration_searcher;
    private CheckProgramIncrement $program_increment_checker;
    private RetrieveUser $user_retriever;

    public function __construct(
        LoggerInterface $logger,
        SearchPendingIteration $iteration_searcher,
        CheckProgramIncrement $program_increment_checker,
        RetrieveUser $user_retriever
    ) {
        $this->logger                    = $logger;
        $this->iteration_searcher        = $iteration_searcher;
        $this->program_increment_checker = $program_increment_checker;
        $this->user_retriever            = $user_retriever;
    }

    public function handle(?IterationCreationEventProxy $event): void
    {
        if (! $event) {
            return;
        }
        $iteration_creation = IterationCreation::fromStorage(
            $this->iteration_searcher,
            $this->program_increment_checker,
            $this->user_retriever,
            $event->artifact_id,
            $event->user_id
        );
        if (! $iteration_creation) {
            return;
        }
        $this->processIterationCreation($iteration_creation);
    }

    public function processIterationCreation(IterationCreation $iteration_creation): void
    {
        $iteration_id = $iteration_creation->iteration->id;
        $user_id      = $iteration_creation->user->id;
        $this->logger->debug("Processing iteration creation with iteration #$iteration_id for user #$user_id");
    }
}
