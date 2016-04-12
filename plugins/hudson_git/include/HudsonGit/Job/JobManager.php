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

use GitRepository;

class JobManager
{
    private $dao;

    public function __construct(JobDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(Job $job)
    {
        $id = $this->dao->create($job);
        if (! $id) {
            throw new CannotCreateJobException($GLOBALS['Language']->getText('plugin_hudson_git', 'job_error'));
        }
    }

    public function getJobByRepository(GitRepository $repository)
    {
        $jobs = array();
        foreach ($this->dao->searchJobsByRepositoryId($repository->getId()) as $row) {
            $jobs[] = $this->instantiateFromRow($row, $repository);
        }

        return $jobs;
    }

    public function instantiateFromRow(array $row, GitRepository $repository)
    {
        return new Job(
            $repository,
            $row['push_date'],
            $row['job_url']
        );
    }
}
