<?php
/**
  * Copyright (c) Enalean, 2016. All rights reserved
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

namespace Tuleap\Svn;

use DataAccessObject;
use \Tuleap\Svn\Repository\Repository;
use Project;

class Dao extends DataAccessObject {
    public function searchByProject(Project $project) {
        $project = $this->da->escapeInt($project->getId());
        $sql = 'SELECT *
                FROM plugin_svn_repositories
                WHERE project_id='.$project;
        return $this->retrieve($sql);
    }

    public function searchByRepositoryIdAndProjectId($id, Project $project) {
        $id = $this->da->escapeInt($id);
        $project = $this->da->escapeInt($project->getId());
        $sql = "SELECT *
                FROM plugin_svn_repositories
                WHERE id=$id AND project_id=$project";
        return $this->retrieveFirstRow($sql);
    }

     public function create(Repository $repository) {
        $name    = $this->da->quoteSmart($repository->getName());
        $project = $this->da->escapeInt($repository->getProject()->getId());

        $query = "INSERT INTO plugin_svn_repositories
            (name,  project_id ) values ($name, $project)";

        return $this->updateAndGetLastId($query);
    }

}