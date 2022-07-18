<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization;

use Tuleap\ProgramManagement\Domain\Events\TeamSynchronizationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MissingMirroredMilestoneCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class MissingProgramIncrementCreator
{
    public function __construct(
        private SearchOpenProgramIncrements $search_open_program_increments,
        private SearchMirrorTimeboxesFromProgram $timebox_searcher,
    ) {
    }

    public function detectAndCreateMissingProgramIncrements(
        TeamSynchronizationEvent $event,
        UserIdentifier $user_identifier,
        ProjectReference $team,
        LogMessage $log_message,
    ): void {
        $open_program_increments = $this->search_open_program_increments->searchOpenProgramIncrements($event->getProgramId(), $user_identifier);
        $missing_milestones      = MissingMirroredMilestoneCollection::buildFromProgramIdentifierAndTeam($this->timebox_searcher, $open_program_increments, $team);
        if (! $missing_milestones) {
            return;
        }

        $log_message->debug("Missing milestones " . implode(',', $missing_milestones->missing_program_increments_ids));
    }
}
