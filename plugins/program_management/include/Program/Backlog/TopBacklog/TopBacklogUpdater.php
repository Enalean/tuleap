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

namespace Tuleap\ProgramManagement\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Program\Plan\BuildProgram;

class TopBacklogUpdater
{
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    public function __construct(BuildProgram $build_program, TopBacklogChangeProcessor $top_backlog_change_processor)
    {
        $this->build_program                = $build_program;
        $this->top_backlog_change_processor = $top_backlog_change_processor;
    }

    /**
     * @throws CannotManipulateTopBacklog
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function updateTopBacklog(int $potential_program_id, TopBacklogChange $top_backlog_change, \PFUser $user): void
    {
        $program = $this->build_program->buildExistingProgramProjectForManagement($potential_program_id, $user);

        $this->top_backlog_change_processor->processTopBacklogChangeForAProgram($program, $top_backlog_change, $user);
    }
}
