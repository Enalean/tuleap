<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Project;

class CountElementsModeUpdater
{
    /**
     * @var ProjectsCountModeDao
     */
    private $projects_count_mode_dao;

    public function __construct(ProjectsCountModeDao $projects_count_mode_dao)
    {
        $this->projects_count_mode_dao = $projects_count_mode_dao;
    }

    public function updateBurnupMode(Project $project, bool $use_count_mode): void
    {
        if ($use_count_mode) {
            $this->projects_count_mode_dao->enableBurnupCountMode((int) $project->getID());
        } else {
            $this->projects_count_mode_dao->disableBurnupCountMode((int) $project->getID());
        }
    }
}
