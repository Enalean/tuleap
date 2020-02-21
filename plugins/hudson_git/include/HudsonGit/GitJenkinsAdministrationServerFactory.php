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

namespace Tuleap\HudsonGit;

use Project;
use ProjectManager;

class GitJenkinsAdministrationServerFactory
{
    /**
     * @var GitJenkinsAdministrationServerDao
     */
    private $git_jenkins_administration_server_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        GitJenkinsAdministrationServerDao $git_jenkins_administration_server_dao,
        ProjectManager $project_manager
    ) {
        $this->git_jenkins_administration_server_dao = $git_jenkins_administration_server_dao;
        $this->project_manager                       = $project_manager;
    }

    /**
     * @return GitJenkinsAdministrationServer[]
     */
    public function getJenkinsServerOfProject(Project $project): array
    {
        $servers = [];
        foreach ($this->git_jenkins_administration_server_dao->getJenkinsServerOfProject((int) $project->getID()) as $jenkins_server) {
            $servers[] = new GitJenkinsAdministrationServer(
                (int) $jenkins_server['id'],
                (string) $jenkins_server['jenkins_server_url'],
                $project
            );
        }

        return $servers;
    }

    public function getJenkinsServerById(int $jenkins_server_id): ?GitJenkinsAdministrationServer
    {
        $row = $this->git_jenkins_administration_server_dao->getJenkinsServerById($jenkins_server_id);
        if (empty($row)) {
            return null;
        }

        $jenkins_server_id  = $row['id'];
        $jenkins_server_url = $row['jenkins_server_url'];

        $project = $this->project_manager->getProject($row['project_id']);
        if (! $project || $project->isError()) {
            return null;
        }

        return new GitJenkinsAdministrationServer(
            $jenkins_server_id,
            $jenkins_server_url,
            $project
        );
    }
}
