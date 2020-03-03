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
    public function create(int $jenkins_server_id, int $repository_id, int $push_date): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_hudson_git_project_server_job',
            [
                'project_server_id' => $jenkins_server_id,
                'repository_id'     => $repository_id,
                'push_date'         => $push_date,
            ]
        );
    }

    public function logTriggeredJobs(int $job_id, string $job_urls): void
    {
        $this->getDB()->insert(
            'plugin_hudson_git_project_server_job_polling_url',
            [
                'job_id'  => $job_id,
                'job_url' => $job_urls,
            ]
        );
    }

    public function logBranchSource(int $job_id, int $status_code): void
    {
        $this->getDB()->insert(
            'plugin_hudson_git_project_server_job_branch_source',
            [
                'job_id'  => $job_id,
                'status_code' => $status_code,
            ]
        );
    }

    public function deleteLogsOfServer(int $jenkins_server_id): void
    {
        $sql = "DELETE plugin_hudson_git_project_server_job.*, plugin_hudson_git_project_server_job_polling_url.*
                FROM plugin_hudson_git_project_server_job
                    INNER JOIN plugin_hudson_git_project_server_job_polling_url
                    ON plugin_hudson_git_project_server_job.id = plugin_hudson_git_project_server_job_polling_url.job_id
                WHERE plugin_hudson_git_project_server_job.project_server_id=?
                ";

        $this->getDB()->run($sql, $jenkins_server_id);
    }

    public function searchJobsByJenkinsServer(int $jenkins_server_id): array
    {
        $sql = "SELECT plugin_hudson_git_project_server_job.*,
                       plugin_hudson_git_project_server_job_polling_url.job_url,
                       plugin_hudson_git_project_server_job_branch_source.status_code
                FROM plugin_hudson_git_project_server_job
                         LEFT JOIN plugin_hudson_git_project_server_job_polling_url
                              ON plugin_hudson_git_project_server_job.id = plugin_hudson_git_project_server_job_polling_url.job_id
                         LEFT JOIN plugin_hudson_git_project_server_job_branch_source
                              ON plugin_hudson_git_project_server_job_branch_source.job_id = plugin_hudson_git_project_server_job.id
                WHERE plugin_hudson_git_project_server_job.project_server_id=?
                ORDER BY plugin_hudson_git_project_server_job.push_date
                    DESC
                LIMIT 30";

        return $this->getDB()->run($sql, $jenkins_server_id);
    }
}
