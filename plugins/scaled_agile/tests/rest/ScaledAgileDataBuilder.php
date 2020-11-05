<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Test\REST;

use REST_TestDataBuilder;
use Tuleap\ScaledAgile\Program\ProgramDao;

class ScaledAgileDataBuilder extends REST_TestDataBuilder
{
    public function setUp(): void
    {
        $this->defineProjectAsProgram();
    }

    private function defineProjectAsProgram(): void
    {
        $dao             = new ProgramDao();
        $program_project = $this->project_manager->getProjectByUnixName('program');
        $team_project    = $this->project_manager->getProjectByUnixName('team');
        $dao->saveProgram((int) $program_project->getID(), (int) $team_project->getID());
    }
}
