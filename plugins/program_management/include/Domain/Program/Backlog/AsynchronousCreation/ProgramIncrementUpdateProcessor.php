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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;

final class ProgramIncrementUpdateProcessor implements ProcessProgramIncrementUpdate
{
    public function __construct(
        private LoggerInterface $logger,
        private GatherSynchronizedFields $fields_gatherer
    ) {
    }

    public function processProgramIncrementUpdate(ProgramIncrementUpdate $update): void
    {
        $program_increment_id = $update->program_increment->getId();
        $user_id              = $update->user->getId();
        $this->logger->debug(
            "Processing program increment update with program increment #$program_increment_id for user #$user_id"
        );
        $source_fields = SynchronizedFieldReferences::fromProgramIncrementTracker(
            $this->fields_gatherer,
            $update->tracker
        );

        $this->logger->debug(sprintf('Start date field id #%d', $source_fields->start_date->getId()));
    }
}
