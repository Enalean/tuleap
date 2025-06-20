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

class TimetrackingEnabler
{
    public function __construct(AdminDao $dao)
    {
        $this->dao = $dao;
    }

    public function enableTimetrackingForTracker(Tracker $tracker)
    {
        $this->dao->enableTimetrackingForTracker($tracker->getId());
    }

    public function disableTimetrackingForTracker(Tracker $tracker)
    {
        $this->dao->disableTimetrackingForTracker($tracker->getId());
    }

    public function isTimetrackingEnabledForTracker(Tracker $tracker)
    {
        return $this->dao->isTimetrackingEnabledForTracker($tracker->getId());
    }
}
