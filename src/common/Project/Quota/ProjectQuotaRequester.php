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

namespace Tuleap\Project\Quota;

use Project;
use Tuleap\Event\Dispatchable;

class ProjectQuotaRequester implements Dispatchable
{
    const NAME = 'get_project_quota';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var ProjectQuotaInformation
     */
    private $project_quota_information;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function setProjectQuotaInformation(ProjectQuotaInformation $project_quota_information)
    {
        $this->project_quota_information = $project_quota_information;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return ProjectQuotaInformation
     */
    public function getProjectQuotainformation()
    {
        return $this->project_quota_information;
    }
}
