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

class SvnNotificationDao extends DataAccessObject
{

    /**
     * Set mailing list to be notified on a given path
     *
     * @param int $groupId Project id
     * @param String  $mailingList List of mail addresses
     * @param String  $path        svn path
     *
     * @return bool
     */
    public function setSvnMailingList($groupId, $mailingList, $path)
    {
        $sql = ' REPLACE INTO svn_notification
                         ( group_id, svn_events_mailing_list, path)
                 VALUES (
                 ' . $this->da->escapeInt($groupId) . ',
                 ' . $this->da->quoteSmart($mailingList) . ',
                 ' . $this->da->quoteSmart($path) . '
                 )';
        return $this->update($sql);
    }

    /**
     * Set mailing list to be notified for a given path
     * or for the whole project if path is null
     *
     * @param int $groupId Project id
     * @param String  $path    svn path
     *
     * @return DataAccessResult
     */
    public function getSvnMailingList($groupId, $path = null)
    {
        $condition = '';
        if (!empty($path)) {
            $condition = 'AND path = ' . $this->da->quoteSmart($path);
        }
        $sql = ' SELECT svn_events_mailing_list, path
                 FROM svn_notification 
                 WHERE group_id = ' . $this->da->escapeInt($groupId) . '
                 ' . $condition;
        return $this->retrieve($sql);
    }

    /**
     * Removes mailing list set on a given path identified by $path.
     *
     * @param int $groupId Project id
     * @param String  $path    svn path
     *
     * @return bool
     */
    public function deleteSvnMailingList($groupId, $path)
    {
        $sql = 'DELETE FROM svn_notification
                WHERE path=' . $this->da->quoteSmart($path) . '
                AND group_id=' . $this->da->escapeInt($groupId);
        return $this->update($sql);
    }
}
