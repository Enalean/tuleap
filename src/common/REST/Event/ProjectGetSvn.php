<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\REST\Event;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\REST\v1\SvnRepositoryRepresentationBase;

class ProjectGetSvn implements Dispatchable
{
    public const NAME = 'rest_project_get_svn';

    /**
     * @var SvnRepositoryRepresentationBase[]
     */
    private $repositories_representations = [];

    /**
     * @var int
     */
    private $total_repositories = 0;

    /**
     * @var Project
     */
    private $project;
    private $limit;
    private $offset;
    private $version;
    private $is_plugin_activated = false;
    private $filter;

    public function __construct(Project $project, $filter, $version, $limit, $offset)
    {
        $this->project = $project;
        $this->limit   = $limit;
        $this->offset  = $offset;
        $this->version = $version;
        $this->filter  = $filter;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return SvnRepositoryRepresentationBase[]
     */
    public function getRepositoriesRepresentations()
    {
        return $this->repositories_representations;
    }

    /**
     * @return int
     */
    public function getTotalRepositories()
    {
        return $this->total_repositories;
    }

    /**
     * @param array $repositories_representations
     */
    public function addRepositoriesRepresentations($repositories_representations)
    {
        $this->repositories_representations = $repositories_representations;
    }

    /**
     * @param int $total_repositories
     */
    public function addTotalRepositories($total_repositories)
    {
        $this->total_repositories = $total_repositories;
    }

    public function setPluginActivated()
    {
        $this->is_plugin_activated = true;
    }

    public function isPluginActivated()
    {
        return $this->is_plugin_activated;
    }
}
