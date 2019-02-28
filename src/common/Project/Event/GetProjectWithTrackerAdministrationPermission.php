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

namespace Tuleap\Project\Event;

use PFUser;
use Tuleap\Event\Dispatchable;
use Tuleap\Project\PaginatedProjects;

class GetProjectWithTrackerAdministrationPermission implements Dispatchable
{
    const NAME = 'getProjectWithTrackerAdministrationPermission';
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var PaginatedProjects
     */
    private $paginated_projects;

    public function __construct(PFUser $user, $limit, $offset)
    {
        $this->user   = $user;
        $this->offset = $offset;
        $this->limit  = $limit;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return PaginatedProjects
     */
    public function getPaginatedProjects()
    {
        return $this->paginated_projects;
    }

    public function setPaginatedProjects(PaginatedProjects $paginated_projects)
    {
        $this->paginated_projects = $paginated_projects;
    }
}
