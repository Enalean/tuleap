<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/**
 * I return project and repository information from the request uri
 */
class Git_URL {

    /** @var string */
    private $friendly_url_pattern = '%^/plugins/git
        /(?P<project_name>[^/]*)
        /(?P<path>[^?]*)
        \?{0,1}(?P<parameters>.*)
        $%x';

    /** @var string */
    private $standard_url_pattern = '%^/plugins/git/index.php
        /(?P<project_id>\d+)
        /(?P<action>[^/][a-zA-Z]+)
        /(?P<repository_id>[a-zA-Z\-\_0-9]+)
        /\?{0,1}(?P<parameters>.*)
        %x';

    /** @var string */
    private $uri;

    /** @var bool **/
    private $is_friendly = false;

    /** @var bool **/
    private $is_standard = false;

    /** @var ProjectManager **/
    private $project_manager;

    /** @var GitRepositoryFactory **/
    private $repository_factory;

    /** @var array */
    private $matches;

    /** @var GitRepository */
    private $repository;

    public function __construct(
        ProjectManager $project_manager,
        GitRepositoryFactory $repository_factory,
        $uri
    ) {
        $this->project_manager    = $project_manager;
        $this->repository_factory = $repository_factory;
        $this->uri                = $uri;

        $this->setIsFriendly();
        if (! $this->is_friendly) {
            $this->setIsStandard();
        }
    }

    /**
     * @return bool
     */
    public function isFriendly() {
        return $this->is_friendly;
    }

    /**
     * @return bool
     */
    public function isStandard() {
        return $this->is_standard;
    }

    /**
     * @return Project|null
     */
    public function getProject() {
        if (! $this->repository) {
            return null;
        }

        return $this->repository->getProject();
    }

    /**
     * @return GitRepository|null
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getParameters() {
        return isset($this->matches['parameters']) ? $this->matches['parameters'] : '';
    }

    private function setIsFriendly() {
        if (! preg_match($this->friendly_url_pattern, $this->uri, $this->matches)) {
            return;
        }

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $this->matches['project_name'],
            $this->matches['path'].'.git'
        );
        if (! $this->repository) {
            return;
        }

        $this->is_friendly = true;
    }

    private function setIsStandard() {
        if (! preg_match($this->standard_url_pattern, $this->uri, $this->matches)) {
            return;
        }

        $this->repository = $this->getRepositoryFromStandardURL();
        if (! $this->repository) {
            return;
        }

        $this->is_standard = true;
    }

    /**
     * @return GitRepository|null
     */
    private function getRepositoryFromStandardURL() {
        $repository_id          = $this->matches['repository_id'];
        $repository_id_is_a_int = preg_match('/^([0-9]+)$/', $repository_id);

        if ($repository_id_is_a_int) {
            return $this->repository_factory->getRepositoryById($repository_id);
        } else {
            $project = $this->getProjectFromStandardURL();
            if (! $project->isError()) {
                return $this->repository_factory->getRepositoryByPath($project->getID(), GitRepository::getPathFromProjectAndName($project, $repository_id));
            }
        }
        return null;
    }

    /**
     * @return Project
     */
    private function getProjectFromStandardURL() {
        return $this->project_manager->getProject($this->matches['project_id']);
    }
}
