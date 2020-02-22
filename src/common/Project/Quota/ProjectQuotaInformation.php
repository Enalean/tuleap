<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Quota;

class ProjectQuotaInformation
{
    /**
     * @var int
     */
    private $project_quota;

    /**
     * @var int
     */
    private $project_disk_usage;

    public function __construct($project_quota, $project_disk_usage)
    {
        $this->project_quota      = $project_quota;
        $this->project_disk_usage = $project_disk_usage;
    }

    /**
     * @return int
     */
    public function getDiskUsage()
    {
        return $this->project_disk_usage;
    }

    /**
     * @return int
     */
    public function getQuota()
    {
        return $this->project_quota;
    }
}
