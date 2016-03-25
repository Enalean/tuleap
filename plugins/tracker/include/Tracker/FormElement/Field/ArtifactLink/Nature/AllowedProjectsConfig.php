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
use EventManager;

class AllowedProjectsConfig {

    /**
     * @var EventManager
     */
    private $event_manager;

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
        Tracker_Hierarchy_Dao $hierachy_dao,
        EventManager $event_manager
    ) {
        $this->project_manager = $project_manager;
        $this->dao             = $dao;
        $this->hierachy_dao    = $hierachy_dao;
        $this->event_manager   = $event_manager;
    }

    public function addProject(Project $project) {
        $this->checkIfProjectCanUseNatures($project);

        return $this->dao->create($project);
    }

    private function checkIfProjectCanUseNatures(Project $project) {
        if ($this->hierachy_dao->isProjectUsingTrackerHierarchy($project->getId())) {
            throw new ProjectIsUsingHierarchyException($project);
        }

        $service_name = '';

        $params = array(
            'project'      => $project,
            'service_name' => &$service_name
        );

        $this->event_manager->processEvent(
            TRACKER_EVENT_ARTIFACT_LINK_NATURES_BLOCKED_BY_SERVICE,
            $params
        );

        if ($service_name) {
            throw new AnotherServiceBlocksNatureUsageException($project, $service_name);
        }
    }

    public function removeProject(Project $project) {
        $project_id = $project->getId();
        return $this->removeProjectIds(array($project_id));
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
