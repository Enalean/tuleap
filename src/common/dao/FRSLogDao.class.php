<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

require_once('include/DataAccessObject.class.php');

class FRSLogDao extends DataAccessObject
{
    /**
     * Add the action to FRS log
     *
     * @param int $userID
     * @param int $projectID
     * @param int $itemID
     * @param int $actionID
     *
     * @return bool
     */
    public function addLog($userID, $projectID, $itemID, $actionID)
    {
        $sql = ' INSERT INTO frs_log ' .
               ' (time, user_id, group_id, item_id, action_id) ' .
               ' VALUES ( ' . $this->da->escapeInt($_SERVER['REQUEST_TIME']) . ', ' .
               $this->da->escapeInt($userID) . ', ' .
               $this->da->escapeInt($projectID) . ', ' .
               $this->da->escapeInt($itemID) . ', ' .
               $this->da->escapeInt($actionID) . ')';
        return $this->update($sql);
    }
}
