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
}

?>
