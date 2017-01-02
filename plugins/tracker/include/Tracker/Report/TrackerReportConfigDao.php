<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Report;

use DataAccessObject;

class TrackerReportConfigDao extends DataAccessObject
{
    public function searchExpertQueryConfig()
    {
        $sql = "SELECT * FROM tracker_report_config";

        return $this->retrieve($sql);
    }

    public function saveQueryLimit($limit)
    {
        $limit = $this->da->escapeInt($limit);

        $sql = "UPDATE tracker_report_config
                SET query_limit=$limit";

        return $this->update($sql);
    }
}
