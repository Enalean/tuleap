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

namespace Tuleap\Timetracking\Admin;

use Project;
use ProjectUGroup;
use Tuleap\Tracker\Tracker;
use UGroupManager;

class TimetrackingUgroupRetriever
{
    /**
     * @var TimetrackingUgroupDao
     */
    private $dao;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(TimetrackingUgroupDao $dao, UGroupManager $ugroup_manager)
    {
        $this->dao            = $dao;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return array
     */
    public function getWriterIdsForTracker(Tracker $tracker)
    {
        $ugroup_rows = $this->dao->getWriters($tracker->getId());

        $ugroup_ids = [];
        foreach ($ugroup_rows as $ugroup_row) {
            $ugroup_ids[] = $ugroup_row['ugroup_id'];
        }

        return $ugroup_ids;
    }

    /**
     * @return array
     */
    public function getReaderIdsForTracker(Tracker $tracker)
    {
        $ugroup_rows = $this->dao->getReaders($tracker->getId());

        $ugroup_ids = [];
        foreach ($ugroup_rows as $ugroup_row) {
            $ugroup_ids[] = $ugroup_row['ugroup_id'];
        }

        return $ugroup_ids;
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getWriterUgroupsForTracker(Tracker $tracker): array
    {
        $ugroup_rows = $this->dao->getWriters($tracker->getId());

        return $this->buildUgroupsCollection($tracker->getProject(), $ugroup_rows);
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getReaderUgroupsForTracker(Tracker $tracker): array
    {
        $ugroup_rows = $this->dao->getReaders($tracker->getId());

        return $this->buildUgroupsCollection($tracker->getProject(), $ugroup_rows);
    }

    /**
     * @return ProjectUGroup[]
     */
    private function buildUgroupsCollection(Project $project, array $ugroup_rows): array
    {
        $ugroups = [];
        foreach ($ugroup_rows as $ugroup_row) {
            $ugroup_id = (int) $ugroup_row['ugroup_id'];
            $ugroup    = $this->ugroup_manager->getUGroup(
                $project,
                $ugroup_id
            );

            if ($ugroup === null) {
                continue;
            }

            $ugroups[] = $ugroup;
        }

        return $ugroups;
    }
}
