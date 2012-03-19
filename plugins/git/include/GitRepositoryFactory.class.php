<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'GitDao.class.php';
require_once 'GitRepository.class.php';

class GitRepositoryFactory {
    /**
     * @var GitDao
     */
    private $dao;
    
    /**
     * @var ProjectManager 
     */
    private $projectManager;
    
    public function __construct(GitDao $dao, ProjectManager $projectManager) {
        $this->dao            = $dao;
        $this->projectManager = $projectManager;
    }
    
    /**
     * Get a project repository by its id
     *
     * @param int $id         The id of the repository to load
     *
     * @return GitRepository the repository or null if not found
     */
    public function getRepositoryById($id) {
        $dar = $this->dao->searchProjectRepositoryById($id);
        return $this->getRepositoryFromDar($dar);
    }

    /**
     * Get a project repository by its id
     *
     * @param int $id         The id of the repository to load
     *
     * @return GitRepository the repository or null if not found
     */
    public function getRepositoryByPath($project_id, $path) {
        $dar = $this->dao->searchProjectRepositoryByPath($project_id, $path);
        return $this->getRepositoryFromDar($dar);
    }
    
    /**
     * Return the repository given it's full path on the file system (from /)
     * 
     * @param String $full_path
     * 
     * @return GitRepository 
     */
    public function getFromFullPath($full_path) {
        $repo = $this->getByRepositoryRootMatch('gitolite/repositories', $full_path);
        if (!$repo) {
            $repo = $this->getByRepositoryRootMatch('gitroot', $full_path);
        }
        return $repo;
    }
    
    /**
     * Attempt to get repository if path match given base directory
     * 
     * @param type $base_dir A top level directory that can contains repo
     * @param type $path     Full repository path
     * 
     * @return GitRepository
     */
    private function getByRepositoryRootMatch($base_dir, $path) {
        $matches = array();
        if (preg_match('%'.$base_dir.'/([^/]+)/(.*)$%', $path, $matches)) {
            return $this->getByProjectNameAndPath($matches[1], $matches[2]);
        }
        return null;
    }
    
    /**
     *
     * @param String $projectName
     * @param String $path
     * 
     * @return GitRepository
     */
    private function getByProjectNameAndPath($projectName, $path) {
        $project = $this->projectManager->getProjectByUnixName($projectName);
        if ($project) {
            return $this->getRepositoryByPath($project->getID(), $projectName . '/' . $path);
        }
        return null;
    }

    /**
     * @param DataAccessResult $dar
     * @return GitRepository 
     */
    private function getRepositoryFromDar(DataAccessResult $dar) {
        $repository = null;
        if ($dar->rowCount() == 1) {
            $repository = new GitRepository();
            $this->dao->hydrateRepositoryObject($repository, $dar->getRow());
        }
        return $repository;
    }
    
    public function isRepositoryExistingByName(Project $project, $name) {
        $path = GitRepository::getPathFromProjectAndName($project, $name);
        return $this->dao->isRepositoryExisting($project->getID(), $path);
    }
}

?>
