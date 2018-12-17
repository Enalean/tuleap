<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

class Collector
{
    /**
     * @var \Statistics_DiskUsageDao
     */
    private $disk_usage_dao;
    /**
     * @var LFSStatisticsDAO
     */
    private $statistics_dao;

    public function __construct(
        \Statistics_DiskUsageDao $disk_usage_dao,
        LFSStatisticsDAO $lfs_statistics_dao
    ) {
        $this->disk_usage_dao = $disk_usage_dao;
        $this->statistics_dao = $lfs_statistics_dao;
    }

    public function proceedToDiskUsageCollection(array &$params, \DateTimeImmutable $current_time)
    {
        $start = microtime(true);

        $project = $params['project'];

        $this->disk_usage_dao->addGroup(
            $project->getID(),
            \gitlfsPlugin::SERVICE_SHORTNAME,
            $this->statistics_dao->getOccupiedSizeByProjectIDAndExpiration($project->getID(), $current_time->getTimestamp()),
            $current_time->getTimestamp()
        );

        $end = microtime(true);
        $this->registerCollectionTime($params, $end - $start);
    }

    private function registerCollectionTime(array &$params, $time)
    {
        if (!isset($params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME] += $time;
    }
}
