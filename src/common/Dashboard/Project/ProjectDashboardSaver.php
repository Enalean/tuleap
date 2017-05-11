<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use PFUser;
use Project;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;

class ProjectDashboardSaver
{
    /**
     * @var ProjectDashboardDao
     */
    private $dao;

    public function __construct(ProjectDashboardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param PFUser $user
     * @param Project $project
     * @param $name
     * @return int
     * @throws NameDashboardAlreadyExistsException
     * @throws NameDashboardDoesNotExistException
     * @throws UserCanNotUpdateProjectDashboardException
     */
    public function save(PFUser $user, Project $project, $name)
    {
        if (! $user->isAdmin($project->getID())) {
            throw new UserCanNotUpdateProjectDashboardException();
        }

        if (! $name) {
            throw new NameDashboardDoesNotExistException();
        }

        if ($this->dao->searchByProjectIdAndName($project->getID(), $name)->count() > 0) {
            throw new NameDashboardAlreadyExistsException();
        }
        return $this->dao->save($project->getID(), $name);
    }
}
