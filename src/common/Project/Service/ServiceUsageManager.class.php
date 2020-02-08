<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'ServiceUsage.class.php';
require_once 'ServiceUsageDao.class.php';

/**
 * Manage Project_Service_ServiceUsage objects
 */
class Project_Service_ServiceUsageManager
{

    /** @var Project_Service_ServiceUsageDao */
    private $dao;

    public function __construct(Project_Service_ServiceUsageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     *
     * @return bool
     */
    public function activateService(Project $project, Project_Service_ServiceUsage $service)
    {
        return $this->dao->activateService($project->getID(), $service->getId());
    }

    /**
     *
     * @return bool
     */
    public function deactivateService(Project $project, Project_Service_ServiceUsage $service)
    {
        return $this->dao->deactivateService($project->getID(), $service->getId());
    }
}
