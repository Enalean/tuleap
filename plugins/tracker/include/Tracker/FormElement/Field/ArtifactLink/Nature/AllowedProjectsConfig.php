<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Project;
use ProjectManager;
use Tracker_Hierarchy_Dao;

class AllowedProjectsConfig {

    /**
     * @var Tracker_Hierarchy_Dao
     */
    private $hierachy_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var AllowedProjectsDao
     */
    private $dao;

    public function __construct(
        ProjectManager $project_manager,
        AllowedProjectsDao $dao,
        Tracker_Hierarchy_Dao $hierachy_dao
    ) {
        $this->project_manager = $project_manager;
        $this->dao             = $dao;
        $this->hierachy_dao    = $hierachy_dao;
    }

    public function addProject(Project $project) {
        if ($this->hierachy_dao->isProjectUsingTrackerHierarchy($project->getId())) {
            throw new ProjectIsUsingHierarchyException();
        }

        return $this->dao->create($project);
    }

    public function removeProjectIds(array $project_ids) {
        return $this->dao->removeByProjectIds($project_ids);
    }

    /** @return Project[] */
    public function getAllProjects() {
        $projects = array();
        foreach ($this->dao->searchAll() as $row) {
            $project = $this->project_manager->getProject($row['project_id']);

            if ($project && ! $project->isError()) {
                $projects[] = $project;
            }
        }

        return $projects;
    }

    public function isProjectAllowedToUseNature(Project $project) {
        return $this->dao->hasProjectId($project->getId());
    }

}
