<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

class Project_HierarchyManager
{
    /**
     * @var ProjectHierarchyDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ProjectManager $project_manager, ProjectHierarchyDao $dao)
    {
        $this->project_manager = $project_manager;
        $this->dao             = $dao;
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return bool
     * @throws Project_HierarchyManagerAlreadyAncestorException
     * @throws Project_HierarchyManagerAncestorIsSelfException
     */
    public function setParentProject($project_id, $parent_project_id)
    {
        $current_parent = $this->getParentProject($project_id);

        $this->validateParent($project_id, $parent_project_id);

        if (! $parent_project_id) {
            return $this->removeParentProject($project_id);
        }

        if ($current_parent) {
            return $this->updateParentProject($project_id, $parent_project_id);
        }

        return $this->addParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @param Project|null $current_parent
     * @return bool
     */
    private function validateParent($project_id, $parent_project_id)
    {
        $parents = $this->getAllParents($parent_project_id);
        if (in_array($project_id, $parents)) {
            throw new Project_HierarchyManagerAlreadyAncestorException();
        }

        if ($project_id == $parent_project_id) {
            throw new Project_HierarchyManagerAncestorIsSelfException();
        }
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return bool
     */
    private function addParentProject($project_id, $parent_project_id)
    {
        return $this->getDao()->addParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return bool
     */
    private function updateParentProject($project_id, $parent_project_id)
    {
        return $this->getDao()->updateParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $project_id
     * @return bool
     */
    public function removeParentProject($project_id)
    {
        return $this->getDao()->removeParentProject($project_id);
    }

    /**
     * @param int $project_id
     * @return Project[]
     */
    public function getChildProjects($project_id)
    {
        $children = array();
        foreach ($this->getDao()->getChildProjects($project_id) as $child) {
            $children[] = $this->project_manager->getProjectFromDbRow($child);
        }

        return $children;
    }

    /**
     * @param int $project_id
     * @return Project | null
     */
    public function getParentProject($project_id)
    {
        $data = $this->getDao()->getParentProject($project_id);

        if ($data->count() > 0) {
            return $this->project_manager->getProjectFromDbRow($data->getRow());
        }

        return null;
    }

    /**
     * Project Generale
     * `-- Project Lieutenant
     *     `-- Project Corporale
     * getAllParents(Project Corporale) -> ['Project Generale'->ID, 'Project Lieutenant'->ID]
     *
     * @param int $project_id
     * @return array Project IDs
     */
    public function getAllParents($project_id)
    {
        $parent_ids = array();

        while ($parent_project = $this->getParentProject($project_id)) {
            $parent_ids[] = $parent_project->getID();
            $project_id = $parent_project->getID();
        }

        return $parent_ids;
    }

    /**
     * @return ProjectDao
     */
    private function getDao()
    {
        return $this->dao;
    }
}
