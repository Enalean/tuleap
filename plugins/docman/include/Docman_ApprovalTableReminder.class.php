<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('common/date/DateHelper.class.php');
require_once('Docman_ApprovalTableDao.class.php');
require_once('Docman_ApprovalTable.class.php');

/**
 * Remind users that didn't review documents yet
 */
class Docman_ApprovalTableReminder {

    /**
     * Remind approval table approvers
     *
     * @return Void
     */
    function remindApprovers() {
        $dao = new Docman_ApprovalTableDao();
        $dar = $dao->getTablesForReminder();
        $tables = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $table = new Docman_ApprovalTable();
                $table->initFromRow($row);
                $distance = DateHelper::dateDiffInDays($table->getDate(), $_SERVER['REQUEST_TIME']);
                var_dump($distance);
                if ($distance > 0 && DateHelper::isPeriodicallyDistant($distance, $table->getNotificationOccurence())) {
                    $this->sendNotificationToPendingApprovers($table);
                }
            }
        }
    }

    /**
     * Send notification to pending approvers of the given table
     *
     * @param Docman_ApprovalTable $table Approval table
     *
     * @return Void
     */
     function sendNotificationToPendingApprovers($table) {
         // @TODO: Send reminders to pending approvers
     }

}

?>