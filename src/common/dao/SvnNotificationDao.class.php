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

require_once('include/DataAccessObject.class.php');

class SvnNotificationDao extends DataAccessObject {

    const TABLE_NAME       = 'svn_notification';

    public function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'svn_notification';
    }

    /**
     * Set mailing list to be notified on a given path
     *
     * @param Integer $groupId
     * @param String  $mailingList
     * @param String  $path
     *
     * @return Boolean
     */
    function setSVNMailingList($groupId, $mailingList, $path) {
        $sql = ' INSERT INTO svn_notification
                 VALUES (
                 '.$this->da->escapeInt($groupId).',
                 '.$this->da->quoteSmart($mailingList).',
                 '.$this->da->quoteSmart($path).'
                 )
                 ON DUPLICATE KEY UPDATE svn_events_mailing_list = '.$this->da->quoteSmart($mailingList);
        return $this->update($sql);
    }

    /**
     * Set mailing list to be notified on a given path
     *
     * @param Integer $groupId
     * @param String  $path
     *
     * @return DataAccessResult
     */
    function getSVNMailingList($groupId, $path) {
        $sql = ' SELECT svn_events_mailing_list
                 FROM svn_notification 
                 WHERE group_id = '.$this->da->escapeInt($groupId).'
                 AND path = '.$this->da->quoteSmart($path);
        return $this->retrieve($sql);
    }

}

?>
