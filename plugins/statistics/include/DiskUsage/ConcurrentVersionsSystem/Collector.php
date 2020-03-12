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

namespace Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem;

use Project;
use DateTime;

class Collector
{
    /**
     * @var FullHistoryDao
     */
    private $dao;

    /**
     * @var Retriever
     */
    private $retriever;

    public function __construct(
        FullHistoryDao $cvs_log_dao,
        Retriever $retriever
    ) {
        $this->dao       = $cvs_log_dao;
        $this->retriever = $retriever;
    }

    public function collectForCVSRepositories(Project $project)
    {
        $yesterday           = new DateTime("yesterday midnight");
        $formatted_yesterday = $yesterday->format("Ymd");
        $project_id          = $project->getID();

        if (! $this->dao->hasRepositoriesUpdatedAfterGivenDate($project_id, $formatted_yesterday)) {
            return $this->getDiskUsageForYesterday($project);
        }

        return null;
    }

    private function getDiskUsageForYesterday(Project $project)
    {
        return $this->retriever->getLastSizeForProject($project);
    }
}
