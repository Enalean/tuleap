<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class MediawikiSiteAdminResourceRestrictor
{

    public const RESOURCE_ID = 1;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var MediawikiSiteAdminResourceRestrictorDao
     */
    private $dao;

    public function __construct(MediawikiSiteAdminResourceRestrictorDao $dao, ProjectManager $project_manager)
    {
        $this->dao = $dao;
        $this->project_manager = $project_manager;
    }

    public function allowProject(Project $project)
    {
        return $this->dao->allowProjectOnResource(self::RESOURCE_ID, $project->getId());
    }

    public function searchAllowedProjects()
    {
        $project_manager = $this->project_manager;
        return $this->dao->searchAllowedProjectsOnResource(self::RESOURCE_ID)->instanciateWith(function ($row) use ($project_manager) {
            return $project_manager->getProjectFromDbRow($row);
        });
    }
}
