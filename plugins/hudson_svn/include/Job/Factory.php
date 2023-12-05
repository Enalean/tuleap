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

namespace Tuleap\HudsonSvn\Job;

use Tuleap\SVNCore\Repository;

class Factory
{
    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    public function getJobById(int $job_id): ?Job
    {
        $row = $this->dao->getJob($job_id);

        if (! $row) {
            return null;
        }

        return new Job(
            $row['job_id'],
            $row['repository_id'],
            $row['path'],
            '',
            ''
        );
    }

    /**
     * @return array
     */
    public function getJobsByRepository(Repository $repository)
    {
        $rows = $this->dao->getJobByRepositoryId($repository->getId());
        $jobs = [];

        foreach ($rows as $row) {
            $jobs[] = new Job(
                $row['job_id'],
                $row['repository_id'],
                $row['path'],
                $row['job_url'],
                $row['token']
            );
        }

        return $jobs;
    }
}
