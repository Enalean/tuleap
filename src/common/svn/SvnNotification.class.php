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

require_once('common/dao/SvnNotificationDao.class.php');

class SvnNotification {

    /**
     * @return SvnNotificationDao
     */
    public function _getDao() {
        if (!isset($this->_dao)) {
            $this->_dao = new SvnNotificationDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }

    /**
     * Set mailing list notification per path
     *
     * @param Integer $projectId
     * @param String  $mailingList
     * @param String  $path
     *
     * @return Boolean
     */
    function setSVNMailingList($projectId, $mailingList, $path) {
        $dao = $this->_getDao();
        return $dao->setSVNMailingList($projectId, $mailingList, $path);
    }

    /**
     * Get mailing list notification per path
     *
     * @param Integer $projectId
     * @param String  $path
     *
     * @return String
     */
    function getSVNMailingList($projectId, $path) {
        $dao = $this->_getDao();
        if ($dar = $dao->getSVNMailingList($projectId, $path)) {
            $row = $dar->current();
            return $row['svn_events_mailing_list'];
        } else {
            return '';
        }
    }

}

?>
