<?php
/*
 * Copyright Enalean (c) 2016. All rights reserved.
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

    public function create(Job $job)
    {
        $repository_id = $this->da->escapeInt($job->getRepository()->getId());
        $push_date     = $this->da->escapeInt($job->getPushDate());
        $job_url       = $this->da->quoteSmart($job->getJobUrl());

        $sql = "INSERT INTO plugin_hudson_git_job (repository_id, push_date, job_url)
                VALUES ($repository_id, $push_date, $job_url)";

        return $this->update($sql);
    }

    public function searchJobsByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT * FROM plugin_hudson_git_job WHERE repository_id=$repository_id
                ORDER BY push_date DESC LIMIT 30";

        return $this->retrieve($sql);
    }
}
