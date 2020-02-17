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

class GitJenkinsAdministrationServerAdder
{
    /**
     * @var GitJenkinsAdministrationServerDao
     */
    private $git_jenkins_administration_server_dao;

    public function __construct(GitJenkinsAdministrationServerDao $git_jenkins_administration_server_dao)
    {
        $this->git_jenkins_administration_server_dao = $git_jenkins_administration_server_dao;
    }

    /**
     * @throws GitJenkinsAdministrationServerAlreadyDefinedException
     */
    public function addServerInProject(Project $project, string $jenkins_server_url): void
    {
        $project_id = (int) $project->getID();

        if ($this->git_jenkins_administration_server_dao->isJenkinsServerAlreadyDefinedInProject(
            $project_id,
            $jenkins_server_url
        )) {
            throw new GitJenkinsAdministrationServerAlreadyDefinedException();
        }

        $this->git_jenkins_administration_server_dao->addJenkinsServer(
            $project_id,
            $jenkins_server_url
        );
    }
}
