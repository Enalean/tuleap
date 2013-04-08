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
     * Return all git repositories of a project (gitshell, gitolite, personal forks)
     *
     * @param Project $project
     *
     * @return Array of GitRepository
     */
    public function getAllRepositories(Project $project) {
        $repositories = array();
        $repository_list = $this->dao->getProjectRepositoryList($project->getID(), false, false);
        foreach ($repository_list as $row) {
            $repository = new GitRepository();
            $this->dao->hydrateRepositoryObject($repository, $row);
            $repositories[] = $repository;
        }
        return $repositories;
    }

    /**
     * Get a deleted repository by its id
     *
     * @param int $id         The id of the repository to load
     *
     * @return GitRepository the repository or null if not found
     */
    public function getDeletedRepository($id) {
        $dar = $this->dao->searchDeletedRepositoryById($id);
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
     * Return all repositories with a remote server set
     *
     * @return Array of GitRepository
     */
    public function getActiveRepositoriesWithRemoteServersForAllProjects() {
        $repositories = array();
        foreach ($this->dao->getActiveRepositoryPathsWithRemoteServersForAllProjects() as $row) {
            $repository = new GitRepository();
            $this->dao->hydrateRepositoryObject($repository, $row);
            $repository->setProject($this->projectManager->getProject($row[GitDao::FK_PROJECT_ID]));
            $repositories[] = $repository;
        }
        return $repositories;
    }

    public function getGerritRepositoriesWithPermissionsForUGroup(Project $project, UGroup $ugroup, PFUser $user) {
        $repositories = array();
        $ugroups      = $user->getUgroups($project->getID(), null);
        $ugroups[]    = $ugroup->getId();
        $dar          = $this->dao->searchGerritRepositoriesWithPermissionsForUGroup($project->getID(), $ugroups);
        foreach ($dar as $row) {
            if (isset($repositories[$row['repository_id']])) {
                $repo_with_perms = $repositories[$row['repository_id']];
            } else {
                $repo_with_perms = new GitRepositoryWithPermissions($this->instanciateFromRow($row));
                $repositories[$row['repository_id']] = $repo_with_perms;
            }
            $repo_with_perms->addUGroupForPermissionType($row['permission_type'], $row['ugroup_id']);

        }
        return $repositories;
    }

    public function getAllGerritRepositoriesFromProject(Project $project, PFUser $user) {
        $all_repositories_dar = $this->dao->searchAllGerritRepositoriesOfProject($project->getId());
        $all_repositories     = array();

        if (count($all_repositories_dar) == 0) {
            return array();
        }

        foreach ($all_repositories_dar as $row) {
            $all_repositories[$row['repository_id']] = new GitRepositoryWithPermissions($this->instanciateFromRow($row));
        }
        $admin_ugroup = new UGroup(array('ugroup_id' => UGroup::PROJECT_ADMIN));
        $repositories_with_admin_permissions = $this->getGerritRepositoriesWithPermissionsForUGroup($project, $admin_ugroup, $user);

        foreach ($repositories_with_admin_permissions as $repository_id => $repository) {
            $all_repositories[$repository_id] = $repository;
        }

        foreach ($all_repositories as $repository) {
            $repository->addUGroupForPermissionType(Git::SPECIAL_PERM_ADMIN, UGroup::PROJECT_ADMIN);
        }

        return $all_repositories;

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
        if ($dar->rowCount() == 1) {
            return $this->instanciateFromRow($dar->getRow());
        }
        return null;
    }

    protected function instanciateFromRow(array $row) {
        $repository = new GitRepository();
        $this->dao->hydrateRepositoryObject($repository, $row);
        return $repository;
    }
}

?>
