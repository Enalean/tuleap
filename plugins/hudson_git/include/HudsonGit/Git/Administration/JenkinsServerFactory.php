<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit\Git\Administration;

use Project;
use ProjectManager;

class JenkinsServerFactory
{
    /**
     * @var JenkinsServerDao
     */
    private $jenkins_server_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        JenkinsServerDao $jenkins_server_dao,
        ProjectManager $project_manager,
    ) {
        $this->jenkins_server_dao = $jenkins_server_dao;
        $this->project_manager    = $project_manager;
    }

    /**
     * @return JenkinsServer[]
     */
    public function getJenkinsServerOfProject(Project $project): array
    {
        $servers = [];
        foreach ($this->jenkins_server_dao->getJenkinsServerOfProject((int) $project->getID()) as $jenkins_server) {
            $servers[] = new JenkinsServer(
                $jenkins_server['id'],
                $jenkins_server['jenkins_server_url'],
                $jenkins_server['encrypted_token'],
                $project
            );
        }

        return $servers;
    }

    public function getProjectByJenkinsServerID(string $jenkins_server_uuid_hex): ?Project
    {
        $row = $this->jenkins_server_dao->getProjectIDByJenkinsServerID($jenkins_server_uuid_hex);
        if (empty($row)) {
            return null;
        }

        $project = $this->project_manager->getProject($row['project_id']);
        if (! $project || $project->isError()) {
            return null;
        }

        return $project;
    }
}
