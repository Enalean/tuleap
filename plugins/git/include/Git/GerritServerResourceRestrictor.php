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

namespace Tuleap\Git;

use Git_RemoteServer_GerritServer;
use Project;

class GerritServerResourceRestrictor
{

    /**
     * @var RestrictedGerritServerDao
     */
    private $dao;

    public function __construct(RestrictedGerritServerDao $dao)
    {
        $this->dao = $dao;
    }

    public function unsetRestriction(Git_RemoteServer_GerritServer $gerrit_server)
    {
        return $this->dao->unsetResourceRestricted($gerrit_server->getId());
    }

    public function setRestricted(Git_RemoteServer_GerritServer $gerrit_server)
    {
        return $this->dao->setResourceRestricted($gerrit_server->getId());
    }

    public function isRestricted(Git_RemoteServer_GerritServer $gerrit_server)
    {
        return $this->dao->isResourceRestricted($gerrit_server->getId());
    }

    public function allowProject(Git_RemoteServer_GerritServer $gerrit_server, Project $project)
    {
        return $this->dao->allowProjectOnResource($gerrit_server->getId(), $project->getID());
    }

    public function revokeProject(Git_RemoteServer_GerritServer $gerrit_server, array $project_ids)
    {
        return $this->dao->revokeProjectsFromResource($gerrit_server->getId(), $project_ids);
    }

    /**
     * @return Project[]
     */
    public function searchAllowedProjects(Git_RemoteServer_GerritServer $gerrit_server)
    {
        $rows     = $this->dao->searchAllowedProjectsOnResource($gerrit_server->getId());
        $projects = [];

        foreach ($rows as $row) {
            $projects[] = new Project($row);
        }

        return $projects;
    }
}
