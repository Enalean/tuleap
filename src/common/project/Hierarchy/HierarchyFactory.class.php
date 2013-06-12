<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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


class Project_HierarchyFactory {

    /**
     * @var ProjectDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @param ProjectManager $project_manager
     */
    public function __construct(ProjectManager $project_manager) {
        $this->project_manager = $project_manager;
        $this->dao             = $project_manager->_getDao();
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return Boolean
     */
    public function setParentProject($project_id, $parent_project_id) {
        $parent = $this->getParentProject($project_id);

        if ($this->doesParentMatch($parent, $parent_project_id) || $this->isProjectInParents($project_id, $parent_project_id)) {
            return false;
        }

        if (! $parent_project_id) {
            return $this->removeParentProject($project_id);
        }

        if ($parent) {
            return $this->updateParentProject($project_id, $parent_project_id);
        }

        return $this->addParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $parent
     * @param int $parent_project_id
     * @return boolean
     */
    private function doesParentMatch($parent, $parent_project_id) {
        if ($parent && $parent->getID() === $parent_project_id) {
            return true;
        }

        if (! $parent && ! $parent_project_id) {
            return true;
        }

        return false;
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return Boolean
     */
    private function addParentProject($project_id, $parent_project_id) {
        return $this->getDao()->addParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return Boolean
     */
    private function updateParentProject($project_id, $parent_project_id) {
        return $this->getDao()->updateParentProject($project_id, $parent_project_id);
    }

    /**
     * @param int $project_id
     * @return Boolean
     */
    private function removeParentProject($project_id) {
        return $this->getDao()->removeParentProject($project_id);
    }

    /**
     * @param int $project_id
     * @return Project[]
     */
    public function getChildProjects($project_id) {
        $children = array();
        foreach ($this->getDao()->getChildProjects($project_id) as $child) {
            $children[] = $this->project_manager->getProjectFromDbRow($child->getRow());
        }

        return $children;
    }

    /**
     * @param int $project_id
     * @return Project | null
     */
    public function getParentProject($project_id) {
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
    public function getAllParents($project_id) {
        $parent_ids = array();

        while ($parent_project = $this->getParentProject($project_id)) {
            $parent_ids[] = $parent_project->getID();
            $project_id = $parent_project->getID();
        }

        return $parent_ids;
    }

    /**
     * @param int $project_id
     * @param int $parent_project_id
     * @return boolean
     */
    private function isProjectInParents($project_id, $parent_project_id) {
        $parents = $this->getAllParents($parent_project_id);

        return in_array($project_id, $parents);
    }

    /**
     * @return ProjectDao
     */
    private function getDao() {
        return $this->dao;
    }
}

?>
