<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

namespace Tuleap\HudsonGit\Job;

use Tuleap\DB\DataAccessObject;

class ProjectJobDao extends DataAccessObject
{
    public function create(int $jenkins_server_id, int $repository_id, int $push_date, string $job_url): void
    {
        $this->getDB()->insert(
            'plugin_hudson_git_project_server_job',
            [
                'project_server_id' => $jenkins_server_id,
                'repository_id'     => $repository_id,
                'push_date'         => $push_date,
                'job_url'           => $job_url
            ]
        );
    }

    public function deleteLogsOfServer(int $jenkins_server_id): void
    {
        $this->getDB()->delete(
            'plugin_hudson_git_project_server_job',
            [
                'project_server_id' => $jenkins_server_id
            ]
        );
    }
}
