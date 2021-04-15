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

namespace Tuleap\ProgramManagement\Program;

final class ProgramSearcher
{
    /**
     * @var SearchProgram
     */
    private $search_program;

    public function __construct(SearchProgram $search_program)
    {
        $this->search_program = $search_program;
    }

    /**
     * @throws ProgramNotFoundException
     */
    public function getProgramOfProgramIncrement(int $program_increment_id): Program
    {
        $potential_program_id = $this->search_program->searchProgramOfProgramIncrement($program_increment_id);
        if ($potential_program_id === null) {
            throw new ProgramNotFoundException(
                "Could not find the program of program increment #$program_increment_id"
            );
        }
        return new Program($potential_program_id);
    }
}
