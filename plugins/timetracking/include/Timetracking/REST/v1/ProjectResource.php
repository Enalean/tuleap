<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use Luracast\Restler\RestException;
use Project;
use Tuleap\REST\UserManager as RestUserManager;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;

class ProjectResource
{
    /** @var \Tuleap\REST\UserManager */
    private $rest_user_manager;

    public function __construct()
    {
        $this->rest_user_manager = RestUserManager::build();
    }

    /**
     * @param       $limit
     * @param       $offset
     * @param array $query
     * @return Project[]
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function getProjects($limit, $offset, array $query)
    {
        $this->checkQuery($query);
        $current_user = $this->rest_user_manager->getCurrentUser();

        $time_retriever = new TimeRetriever(
            new TimeDao(),
            new PermissionsRetriever(
                new TimetrackingUgroupRetriever(
                    new TimetrackingUgroupDao()
                )
            ),
            new AdminDao(),
            \ProjectManager::instance()
        );

        return $time_retriever->getProjectsWithTimetracking($current_user, $limit, $offset);
    }

    /**
     * @throws RestException
     */
    private function checkQuery(array $query)
    {
        if ($query['with_time_tracking'] === false) {
            throw new RestException(
                400,
                "Searching projects where timetracking is not enabled is not supported. Use 'with_timetracking': true"
            );
        }
    }
}
