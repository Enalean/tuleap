<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class MassChangeTopBacklogActionProcessor
{
    private BuildProgram $build_program;
    private TopBacklogChangeProcessor $top_backlog_change_processor;

    public function __construct(BuildProgram $build_program, TopBacklogChangeProcessor $top_backlog_change_processor)
    {
        $this->build_program                = $build_program;
        $this->top_backlog_change_processor = $top_backlog_change_processor;
    }

    public function processMassChangeAction(
        MassChangeTopBacklogSourceInformation $source_information,
    ): void {
        switch ($source_information->action) {
            case 'add':
                $top_backlog_change = new TopBacklogChange($source_information->masschange_aids, [], false, null);
                break;
            case 'remove':
                $top_backlog_change = new TopBacklogChange([], $source_information->masschange_aids, false, null);
                break;
            default:
                return;
        }

        $user_identifier = UserProxy::buildFromPFUser($source_information->user);
        try {
            $program = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user_identifier, null);
        } catch (ProgramAccessException | ProjectIsNotAProgramException $e) {
            return;
        }

        $this->top_backlog_change_processor->processTopBacklogChangeForAProgram($program, $top_backlog_change, $user_identifier, null);
    }
}
