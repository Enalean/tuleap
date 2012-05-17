<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'common/dao/include/DataAccessObject.class.php';

class SVN_LogDao extends DataAccessObject {
    
    public function searchCommiters($group_id, $start_date, $end_date) {
        $group_id   = $this->da->escapeInt($group_id);
        $start_date = $this->da->escapeInt($start_date);
        $end_date   = $this->da->escapeInt($end_date);
        $sql = "SELECT whoid, count(1) as commit_count
                FROM svn_commits
                WHERE group_id = $group_id
                  AND date >= $start_date
                  AND date <= $end_date
                GROUP BY whoid";
        return $this->retrieve($sql);
    }
}

?>
