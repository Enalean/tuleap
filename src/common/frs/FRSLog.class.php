<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/FRSLogDao.class.php');

class FRSLog {

    /**
     * Process the event add_log
     */
    function addLog($event, $params) {
        $userID = $params['user_id'];
        $projectID = $params['project_id'];
        $itemID = $params['item_id'];
        $actionID = $params['action_id'];
        $dao = new FRSLogDao(CodendiDataAccess::instance());
        $dao->addLog($userID, $projectID, $itemID, $actionID);
    }

}

?>