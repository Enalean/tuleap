<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
use Tuleap\Dashboard\DashboardDoesNotExistException;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;

class ProjectDashboardSaver
{
    public function __construct(
        private readonly ProjectDashboardDao $dao,
        private readonly DeleteVisitByDashboardId $delete_visit_by_dashboard_id,
    ) {
    }

    /**
     * @param $name
     * @return int
     */
    public function save(PFUser $user, Project $project, $name)
    {
        $this->checkUserCanSaveByDashboardName($user, $project, $name);
        return $this->dao->save($project->getID(), $name);
    }

    /**
     * @param $id
     * @param $name
     */
    public function update(PFUser $user, Project $project, $id, $name): void
    {
        $this->checkUserCanSaveByDashboardName($user, $project, $name);
        $this->dao->edit($id, $name);
    }

    /**
     * @param $dashboard_id
     */
    public function delete(PFUser $user, Project $project, $dashboard_id)
    {
        $this->checkUserCanDeleteByDashboardId($user, $project, $dashboard_id);
        $this->dao->delete($project->getId(), $dashboard_id);
        $this->delete_visit_by_dashboard_id->deleteVisitByDashboardId($dashboard_id);
    }

    private function checkUserCanSaveByDashboardName(PFUser $user, Project $project, $name)
    {
        if (! $user->isAdmin($project->getID())) {
            throw new UserCanNotUpdateProjectDashboardException();
        }

        if (! $name) {
            throw new NameDashboardDoesNotExistException();
        }

        if (count($this->dao->searchByProjectIdAndName($project->getID(), $name)) > 0) {
            throw new NameDashboardAlreadyExistsException();
        }
    }

    private function checkUserCanDeleteByDashboardId(PFUser $user, Project $project, $dashboard_id)
    {
        if (! $user->isAdmin($project->getID())) {
            throw new UserCanNotUpdateProjectDashboardException();
        }

        if ($this->dao->searchById($dashboard_id) === null) {
            throw new DashboardDoesNotExistException();
        }
    }
}
