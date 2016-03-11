<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use Tuleap\Svn\Dao;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RuleName;
use Project;
use Tuleap\Svn\Repository\CannotCreateRepositoryException;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use ProjectManager;
use Rule_ProjectName;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;
use SystemEvent;

class RepositoryManager {

    private $dao;
    private $project_manager;

    public function __construct(Dao $dao, ProjectManager $project_manager) {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @return Repository[]
     */
    public function getRepositoriesInProject(Project $project) {
        $repositories = array();
        foreach ($this->dao->searchByProject($project) as $row) {
            $repositories[] = $this->instantiateFromRow($row, $project);
        }

        return $repositories;
    }

    public function getRepositoryByName(Project $project, $name) {
        $row = $this->dao->searchRepositoryByName($project, $name);
        if ($row) {
            return $this->instantiateFromRow($row, $project);
        } else {
            throw new CannotFindRepositoryException();
        }
    }

    public function getById($id_repository, Project $project) {
        $row = $this->dao->searchByRepositoryIdAndProjectId($id_repository, $project);
        if (! $row) {
            throw new CannotFindRepositoryException();
        }

        return $this->instantiateFromRow($row, $project);
    }

    /**
     * @return SystemEvent or null
     */
    public function create(Repository $repositorysvn, \SystemEventManager $system_event_manager) {
        $id = $this->dao->create($repositorysvn);
        if (! $id) {
            throw new CannotCreateRepositoryException ($GLOBALS['Language']->getText('plugin_svn','update_error'));
        }
        $repositorysvn->setId($id);

        $repo_event['system_path'] = $repositorysvn->getSystemPath();
        $repo_event['project_id']  = $repositorysvn->getProject()->getId();
        $repo_event['name']        = $repositorysvn->getProject()->getUnixNameMixedCase()."/".$repositorysvn->getName();
        return $system_event_manager->createEvent(
            'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            implode(SystemEvent::PARAMETER_SEPARATOR, $repo_event),
            SystemEvent::PRIORITY_HIGH);
    }

    public function getRepositoryFromSystemPath($path) {
         if (! preg_match('/\/(\d+)\/('.RuleName::PATTERN_REPOSITORY_NAME.')$/', $path, $matches)) {
            throw new CannotFindRepositoryException($GLOBALS['Language']->getText('plugin_svn','find_error'));
        }

        $project = $this->project_manager->getProject($matches[1]);
        return $this->getRepositoryIfProjectIsValid($project, $matches[2]);
    }

    public function getRepositoryFromPublicPath($path) {
         if (! preg_match('/^('.Rule_ProjectName::PATTERN_PROJECT_NAME.')\/('.RuleName::PATTERN_REPOSITORY_NAME.')$/', $path, $matches)) {
            throw new CannotFindRepositoryException();
        }

        $project = $this->project_manager->getProjectByUnixName($matches[1]);

        return $this->getRepositoryIfProjectIsValid($project, $matches[2]);
    }

    private function getRepositoryIfProjectIsValid($project, $repository_name) {
        if (!$project instanceof Project || $project->getID() == null || $project->isError()) {
            throw new CannotFindRepositoryException($GLOBALS['Language']->getText('plugin_svn','find_error'));
        }

        return $this->getRepositoryByName($project, $repository_name);
    }

    /**
     * @return Repository
     */
    public function instantiateFromRow(array $row, Project $project) {
        return new Repository(
            $row['id'],
            $row['name'],
            $project
        );
    }
}
