<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Kanban\REST\v1;

use AgileDashboard_KanbanItemDao;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;

final class TimeInfoFactory
{
    /**
     * @var AgileDashboard_KanbanItemDao
     */
    private $dao;

    public function __construct(AgileDashboard_KanbanItemDao $dao)
    {
        $this->dao = $dao;
    }

    /** @return array */
    public function getTimeInfo(Artifact $artifact)
    {
        $timeinfo = [];
        foreach ($this->dao->searchTimeInfoForItem($artifact->getTrackerId(), $artifact->getId()) as $row) {
            $timeinfo[$row['column_id']] = JsonCast::toDate($row['submitted_on']);
        }

        if (empty($timeinfo)) {
            $timeinfo['kanban'] = null;
        } else {
            $timeinfo['kanban'] = min($timeinfo);
        }

        $timeinfo['archive'] = JsonCast::toDate($this->dao->getTimeInfoForArchivedItem($artifact->getId()));

        return $timeinfo;
    }
}
