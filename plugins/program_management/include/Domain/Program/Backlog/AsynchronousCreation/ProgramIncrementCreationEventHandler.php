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

use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class ProgramIncrementCreationEventHandler
{
    public function __construct(
        private LogMessage $logger,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private VerifyIsChangeset $changeset_verifier,
        private RetrieveProgramIncrementTracker $tracker_retriever,
        private BuildProgramIncrementCreationProcessor $processor_retriever,
    ) {
    }

    public function handle(?ProgramIncrementCreationEvent $event): void
    {
        if (! $event) {
            return;
        }
        $creation = ProgramIncrementCreation::fromProgramIncrementCreationEvent(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $event
        );
        if (! $creation) {
            $this->logger->error(
                sprintf(
                    'Invalid data given in payload, skipping program increment creation for artifact #%d, user #%d and changeset #%d',
                    $event->getArtifactId(),
                    $event->getUser()->getId(),
                    $event->getChangesetId()
                )
            );
            return;
        }
        $processor = $this->processor_retriever->getProcessor();
        $processor->processCreation($creation);
    }
}
