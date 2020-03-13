<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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
class Git_URL implements \Tuleap\Git\HTTP\GitHTTPOperation
{

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
    private $smart_http_url_pattern = '%^/plugins/git
        /(?P<project_name>[^/]*)
        /(?P<path>[^?]*(\.git){0,1})
        /(?P<smart_http>
            HEAD |
            info/refs |
            git-(upload|receive)-pack |
            objects/(info/[^/]+ |
                     [0-9a-f]{2}/[0-9a-f]{38} |
                     pack/pack-[0-9a-f]{40}\.(pack|idx)
                    )
          )
          $%x';

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

    /** @var string */
    private $path_info = '';

    /** @var string */
    private $query_string = '';

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
        if (! $this->is_friendly && ! $this->is_standard) {
            $this->setIsSmartHTTP();
        }
    }

    /**
     * @return GitRepository|null
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return isset($this->matches['parameters']) ? $this->matches['parameters'] : '';
    }

    private function setIsFriendly()
    {
        if (! preg_match($this->friendly_url_pattern, $this->uri, $this->matches)) {
            return;
        }

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $this->matches['project_name'],
            $this->matches['path'] . '.git'
        );
        if (! $this->repository) {
            return;
        }

        $this->is_friendly = true;
    }

    private function setIsStandard()
    {
        if (! preg_match($this->standard_url_pattern, $this->uri, $this->matches)) {
            return;
        }

        $this->repository = $this->getRepositoryFromStandardURL();
        if (! $this->repository) {
            return;
        }

        $this->is_standard = true;
    }

    private function setIsSmartHTTP()
    {
        $uri = $this->uri;
        $params_position = strpos($uri, '?');
        if ($params_position !== false) {
            $uri = substr($uri, 0, $params_position);
        }
        if (! preg_match($this->smart_http_url_pattern, $uri, $this->matches)) {
            return;
        }

        $repository_path = $this->matches['path'];
        if (strpos($this->matches['path'], '.git') === false) {
            $repository_path .= '.git';
        }

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $this->matches['project_name'],
            $repository_path
        );

        if (! $this->repository) {
            return;
        }

        $this->path_info    = '/' . $this->matches['project_name'] . '/' . $repository_path . '/' . $this->matches['smart_http'];
        if ($params_position !== false) {
            $this->query_string = substr($this->uri, $params_position + 1);
        }
    }

    public function getPathInfo()
    {
        return $this->path_info;
    }

    public function getQueryString()
    {
        return $this->query_string;
    }

    public function isWrite()
    {
        return preg_match('%(/|\?service=)git-receive-pack$%', $this->uri) === 1;
    }

    public function isRead()
    {
        return ! $this->isWrite();
    }

    /**
     * @return GitRepository|null
     */
    private function getRepositoryFromStandardURL()
    {
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
    private function getProjectFromStandardURL()
    {
        return $this->project_manager->getProject($this->matches['project_id']);
    }

    /**
     * @return bool
     */
    public function isADownload(Codendi_Request $request)
    {
        $action_type = $request->get('a');
        return $request->get('noheader') == 1 ||
            $action_type === 'snapshot' ||
            $action_type === 'commitdiff_plain' ||
            $action_type === 'blob_plain';
    }
}
