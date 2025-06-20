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

use Tuleap\Tracker\Tracker;

class TimetrackingUgroupSaver
{
    /**
     * @var TimetrackingUgroupDao
     */
    private $dao;

    public function __construct(TimetrackingUgroupDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveWriters(Tracker $tracker, array $ugroup_ids)
    {
        return $this->dao->saveWriters($tracker->getId(), $ugroup_ids);
    }

    public function saveReaders(Tracker $tracker, array $ugroup_ids)
    {
        return $this->dao->saveReaders($tracker->getId(), $ugroup_ids);
    }

    public function deleteWriters(Tracker $tracker)
    {
        $this->dao->deleteWritersForTracker($tracker->getId());
    }

    public function deleteReaders(Tracker $tracker)
    {
        $this->dao->deleteReadersForTracker($tracker->getId());
    }
}
