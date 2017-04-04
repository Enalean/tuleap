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

namespace Tuleap\AgileDashboard\MonoMilestone;

use DataAccessObject;

class ScrumForMonoMilestoneDao extends DataAccessObject
{
    public function enableScrumForMonoMilestones($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO plugin_agiledashboard_scrum_mono_milestones (project_id) VALUES ($project_id)";

        return $this->update($sql);
    }

    public function isMonoMilestoneActivatedForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM plugin_agiledashboard_scrum_mono_milestones WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function disableScrumForMonoMilestones($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_agiledashboard_scrum_mono_milestones WHERE project_id = $project_id";

        return $this->update($sql);
    }
}
