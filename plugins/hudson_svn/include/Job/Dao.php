<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use PDOException;
use Project;
use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{
    public function saveTrigger(int $job_id, int $repository_id, string $path): bool
    {
        $sql = 'REPLACE INTO plugin_hudson_svn_job (job_id, repository_id, path)
                VALUES (?, ?, ?)';

        return is_array($this->getDB()->run($sql, $job_id, $repository_id, $path));
    }

    public function deleteTrigger(int $job_id): bool
    {
        try {
            $this->getDB()->delete('plugin_hudson_svn_job', ['job_id' => $job_id]);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function getJobIds(Project $project): array
    {
        $project_id = $project->getID();

        $sql = 'SELECT plugin_hudson_svn_job.job_id AS id
                FROM plugin_hudson_svn_job
                    INNER JOIN plugin_svn_repositories AS repo
                    ON (repo.id = plugin_hudson_svn_job.repository_id)
                WHERE repo.project_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    public function getJob(int $job_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_hudson_svn_job
                WHERE job_id = ?
                LIMIT 1';

        return $this->getDB()->row($sql, $job_id);
    }

    public function getJobByRepositoryId(int $repository_id): array
    {
        $sql = 'SELECT job.*, hudson.job_url, hudson.token
                FROM plugin_hudson_svn_job AS job
                    INNER JOIN plugin_hudson_job AS hudson
                    ON (job.job_id = hudson.job_id)
                WHERE job.repository_id = ?';

        return $this->getDB()->run($sql, $repository_id);
    }
}
