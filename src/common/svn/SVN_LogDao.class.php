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
require_once 'common/date/TimeInterval.class.php';

class SVN_LogDao extends DataAccessObject {
    
    public function searchCommiters($group_id, TimeInterval $interval) {
        $group_id  = $this->da->escapeInt($group_id);
        $date_stmt = $this->inIntervalStatement($interval);
        $sql = "SELECT whoid, count(1) as commit_count
                FROM svn_commits
                WHERE group_id = $group_id
                    $date_stmt
                GROUP BY whoid";
        return $this->retrieve($sql);
    }
    
    public function searchTopModifiedFiles($group_id, TimeInterval $interval, $limit, $where_forbidden) {
        $group_id   = $this->da->escapeInt($group_id);
        $limit      = $this->da->escapeInt($limit);
        $date_stmt  = $this->inIntervalStatement($interval);
        $sql = "SELECT CONCAT(dir,file) as path, count(1) as commit_count
                FROM svn_commits 
                    JOIN svn_checkins ON (svn_checkins.commitid = svn_commits.id)
                    JOIN svn_files ON (svn_files.id = svn_checkins.fileid)
                    JOIN svn_dirs ON (svn_dirs.id = svn_checkins.dirid)
                 WHERE group_id = $group_id 
                     $date_stmt
                     $where_forbidden
                 GROUP BY path
                 ORDER BY commit_count DESC
                 LIMIT $limit";
        return $this->retrieve($sql);
    }
    
    private function inIntervalStatement(TimeInterval $interval) {
        $start_date = $this->da->escapeInt($interval->getStartTimestamp());
        $end_date   = $this->da->escapeInt($interval->getEndTimestamp());
        $sql = "AND date >= $start_date
                AND date <= $end_date";
        return $sql;
    }
}

?>
