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

use Project;

class ProjectQuotaChecker
{

    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * @return bool
     */
    public function hasEnoughSpaceForProject(Project $project, $wanted_size)
    {
        $project_quota_requester = new ProjectQuotaRequester($project);

        $this->event_manager->processEvent($project_quota_requester);
        $project_quota_information = $project_quota_requester->getProjectQuotainformation();

        if ($project_quota_information === null) {
            return true;
        }

        return $project_quota_requester->getProjectQuotainformation()->getDiskUsage() + $wanted_size <= $project_quota_requester->getProjectQuotainformation()->getQuota();
    }
}
