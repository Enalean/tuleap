<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

class ScrumForMonoMilestoneChecker
{
    /**
     * @var ScrumForMonoMilestoneDao
     */
    private $scrum_mono_milestaone_dao;

    public function __construct(ScrumForMonoMilestoneDao $scrum_mono_milestaone_dao)
    {
        $this->scrum_mono_milestaone_dao = $scrum_mono_milestaone_dao;
    }

    public function isMonoMilestoneActivated($project_id)
    {
        $row = $this->scrum_mono_milestaone_dao->isMonoMilestoneActivatedForProject($project_id);
        if (! $row) {
            return false;
        }

        return true;
    }
}
