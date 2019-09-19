<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use DataAccessObject;
use Project;

class Dao extends DataAccessObject
{

    /**
     * @return bool
     */
    public function saveTrigger($job_id, $repository_id, $path)
    {
        $job_id        = $this->da->escapeInt($job_id);
        $repository_id = $this->da->escapeInt($repository_id);
        $path          = $this->da->quoteSmart($path);

        $sql = "REPLACE INTO plugin_hudson_svn_job (job_id, repository_id, path)
                VALUES ($job_id, $repository_id, $path)";

        return $this->update($sql);
    }

    public function deleteTrigger($job_id)
    {
        $job_id = $this->da->escapeInt($job_id);

        $sql = "DELETE FROM plugin_hudson_svn_job
                WHERE job_id = $job_id";

        return $this->update($sql);
    }

    public function getJobIds(Project $project)
    {
        $project_id = $this->da->escapeInt($project->getID());

        $sql = "SELECT plugin_hudson_svn_job.job_id AS id
                FROM plugin_hudson_svn_job
                    INNER JOIN plugin_svn_repositories AS repo
                    ON (repo.id = plugin_hudson_svn_job.repository_id)
                WHERE repo.project_id = $project_id";

        return $this->retrieveIds($sql);
    }

    public function getJob($job_id)
    {
        $job_id = $this->da->escapeInt($job_id);

        $sql = "SELECT *
                FROM plugin_hudson_svn_job
                WHERE job_id = $job_id";

        return $this->retrieveFirstRow($sql);
    }

    public function getJobByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT job.*, hudson.job_url, hudson.token
                FROM plugin_hudson_svn_job AS job
                    INNER JOIN plugin_hudson_job AS hudson
                    ON (job.job_id = hudson.job_id)
                WHERE job.repository_id = $repository_id";

        return $this->retrieve($sql);
    }
}
