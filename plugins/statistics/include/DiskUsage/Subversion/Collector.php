<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Statistics\DiskUsage\Subversion;

use SVN_LogDao;
use Project;
use DateTime;

class Collector
{
    /**
     * @var SVN_LogDao
     */
    private $svn_log_dao;

    /**
     * @var Retriever
     */
    private $retriever;

    public function __construct(
        SVN_LogDao $svn_log_dao,
        Retriever $retriever
    ) {
        $this->svn_log_dao = $svn_log_dao;
        $this->retriever   = $retriever;
    }

    public function collectForSubversionRepositories(Project $project)
    {
        $yesterday           = new DateTime("yesterday midnight");
        $yesterday_timestamp = $yesterday->getTimestamp();
        $project_id          = $project->getID();

        if (! $this->svn_log_dao->hasRepositoriesUpdatedAfterGivenDate($project_id, $yesterday_timestamp)) {
            return $this->getDiskUsageForYesterday($project);
        }

        return null;
    }

    private function getDiskUsageForYesterday(Project $project)
    {
        return $this->retriever->getLastSizeForProject($project);
    }
}
