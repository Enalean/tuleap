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
 * Factory to instanciate Project_Service_ServiceUsage
 */
class Project_Service_ServiceUsageFactory
{

    /** @var Project_Service_ServiceUsageDao */
    private $dao;

    public function __construct(Project_Service_ServiceUsageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     *
     * @return Project_Service_ServiceUsage[]
     */
    public function getAllServicesUsage(Project $project)
    {
        $services_usages = array();
        $res = $this->dao->getAllServicesUsage($project->getID());
        while ($row = $res->getRow()) {
            $services_usages[] = $this->getInstanceFromRow($row);
        }
        return $services_usages;
    }

    /**
     * @param int     $service_id
     *
     * @return Project_Service_ServiceUsage
     */
    public function getServiceUsage(Project $project, $service_id)
    {
        $query_result = $this->dao->getServiceUsage($project->getID(), $service_id);
        $row          = $query_result->getRow();
        if ($row) {
            return $this->getInstanceFromRow($row);
        }

        return null;
    }

    /**
     * Build an instance of ServiceUsage
     *
     * @param array $row the value of the ServiceUsage form the db
     *
     * @return ServiceUsage
     */
    public function getInstanceFromRow(array $row)
    {
        return new Project_Service_ServiceUsage(
            $row['service_id'],
            $row['short_name'],
            $row['is_used']
        );
    }
}
