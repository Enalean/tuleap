<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\DiskUsage;

use Project;
use Statistics_DiskUsageDao;
use SvnPlugin;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever;

class DiskUsageCollector
{
    /**
     * @var Retriever
     */
    private $retriever;
    /**
     * @var Statistics_DiskUsageDao
     */
    private $dao;

    public function __construct(DiskUsageRetriever $retriever, Statistics_DiskUsageDao $dao)
    {
        $this->retriever = $retriever;
        $this->dao       = $dao;
    }

    public function collectDiskUsageForProject(Project $project, \DateTimeImmutable $collect_date)
    {
        $svn_disk_size = $this->retriever->getDiskUsageForProject($project);
        $this->dao->addGroup(
            $project->getID(),
            SvnPlugin::SERVICE_SHORTNAME,
            $svn_disk_size,
            $collect_date->getTimestamp()
        );
    }
}
