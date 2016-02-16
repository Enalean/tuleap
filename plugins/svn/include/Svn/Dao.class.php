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
use Tuleap\Svn\Repository\Repository;
use Project;
use SVN_Apache_SvnrootConf;
use ForgeConfig;

class Dao extends DataAccessObject {
    public function searchByProject(Project $project) {
        $project_id = $this->da->escapeInt($project->getId());
        $sql = "SELECT *
                FROM plugin_svn_repositories
                WHERE project_id=$project_id";

        return $this->retrieve($sql);
    }

    public function searchByRepositoryIdAndProjectId($id, Project $project) {
        $id         = $this->da->escapeInt($id);
        $project_id = $this->da->escapeInt($project->getId());
        $sql = "SELECT *
                FROM plugin_svn_repositories
                WHERE id=$id AND project_id=$project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function doesRepositoryAlreadyExist($name, Project $project) {
        $name       = $this->da->quoteSmart($name);
        $project_id = $this->da->escapeInt($project->getId());
        $sql = "SELECT *
                FROM plugin_svn_repositories
                WHERE name=$name AND project_id=$project_id
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    public function getListRepositoriesSqlFragment() {
        $auth_mod = $this->da->quoteSmart(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);
        $sys_dir  = $this->da->quoteSmart(ForgeConfig::get('sys_data_dir'));

        $sql = "SELECT groups.*, service.*,
                CONCAT('/svnroot/', unix_group_name, '/', name) AS public_path,
                CONCAT($sys_dir,'/svn_plugin/', groups.group_id, '/', name) AS system_path,
                $auth_mod AS auth_mod
                FROM groups, service, plugin_svn_repositories
                WHERE groups.group_id = service.group_id
                  AND service.is_used = '1'
                  AND groups.status = 'A'
                  AND plugin_svn_repositories.project_id = groups.group_id
                  AND service.short_name = 'plugin_svn'";

        return $sql;
    }

    public function searchRepositoryByName(Project $project, $name) {
        $project_name = $this->da->quoteSmart($project->getUnixNameMixedCase());
        $name         = $this->da->quoteSmart($name);

        $sql = "SELECT groups.*, id, name, CONCAT(unix_group_name, '/', name) AS repository_name
                FROM groups, plugin_svn_repositories
                WHERE groups.status = 'A' AND project_id = groups.group_id
                AND groups.unix_group_name = $project_name
                AND plugin_svn_repositories.name = $name";

        return $this->retrieveFirstRow($sql);
    }

     public function create(Repository $repository) {
        $name       = $this->da->quoteSmart($repository->getName());
        $project_id = $this->da->escapeInt($repository->getProject()->getId());

        $query = "INSERT INTO plugin_svn_repositories
            (name,  project_id ) values ($name, $project_id)";

        return $this->updateAndGetLastId($query);
    }
}
