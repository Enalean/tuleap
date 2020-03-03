<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\HudsonGit\Job;

use DataAccessObject;

class JobDao extends DataAccessObject
{
    public function create(Job $job): int
    {
        $repository_id = $this->da->escapeInt($job->getRepository()->getId());
        $push_date     = $this->da->escapeInt($job->getPushDate());

        $sql = "INSERT INTO plugin_hudson_git_job (repository_id, push_date)
                VALUES ($repository_id, $push_date)";

        return $this->updateAndGetLastId($sql);
    }

    public function logTriggeredJobs(int $job_id, string $job_urls): void
    {
        $job_id   = $this->da->escapeInt($job_id);
        $job_urls = $this->da->quoteSmart($job_urls);

        $sql = "INSERT INTO plugin_hudson_git_job_polling_url (job_id, job_url)
                VALUES ($job_id, $job_urls)";

        $this->update($sql);
    }

    public function searchJobsByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_hudson_git_job
                JOIN plugin_hudson_git_job_polling_url
                    ON(plugin_hudson_git_job.id = plugin_hudson_git_job_polling_url.job_id)
                WHERE plugin_hudson_git_job.repository_id=$repository_id
                ORDER BY plugin_hudson_git_job.push_date
                DESC
                LIMIT 30";

        return $this->retrieve($sql);
    }
}
