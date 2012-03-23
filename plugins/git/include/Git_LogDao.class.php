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

class Git_LogDao extends DataAccessObject {

    function searchLastPushForRepository($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql = "SELECT log.*
                FROM plugin_git_log log 
                WHERE repository_id = $repository_id
                ORDER BY push_date DESC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    /**
     * Obtain last git pushes performed by the given user
     *
     * @param Integer $userId Id of the user
     * @param Integer $repoId Id of the git repository
     * @param Integer $offset Offset of the search
     *
     * @return DataAccessResult
     */
    function getLastPushesByUser($userId, $repoId = 0, $offset = 10) {
        if ($repoId) {
            $condition = "AND l.repository_id = ".$this->da->escapeInt($repoId);
        } else {
            $condition = "";
        }
        if ($offset) {
            $limit = "LIMIT ".$this->da->escapeInt($offset);
        } else {
            $limit = "LIMIT 10";
        }
        $sql = "SELECT g.group_name, r.repository_name, l.push_date, l.commits_number
                FROM plugin_git_log l
                JOIN plugin_git r ON l.repository_id = r.repository_id
                JOIN groups g ON g.group_id = r.project_id
                WHERE l.user_id = ".$this->da->escapeInt($userId)."
                ".$condition."
                ORDER BY l.push_date DESC
                ".$limit;
        return $this->retrieve($sql);
    }

}

?>
