<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PHPWikiAdminMigrator {

    /**
     * @var PHPWikiAdminMigratorDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(PHPWikiAdminMigratorDao $dao, ProjectManager $project_manager) {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @return bool
     */
    public function canMigrate(Project $project) {
        return $this->dao->canMigrate($project->getID());
    }

    /**
     * @return Project[]|false
     */
    public function searchProjectsUsingPlugin() {
        $project_manager            = $this->project_manager;
        $result_projects_to_migrate = $this->dao->searchProjectsUsingPlugin();
        if ($result_projects_to_migrate) {
            return $result_projects_to_migrate->instanciateWith(function ($row) use ($project_manager) {
                return $project_manager->getProjectFromDbRow($row);
            });
        }
        return false;
    }

}