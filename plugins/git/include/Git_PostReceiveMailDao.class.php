<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Git_PostReceiveMail
 */
class Git_PostReceiveMailDao extends DataAccessObject
{

    public function __construct() {
        parent::__construct(CodendiDataAccess::instance());
    }

    /**
     * Searches Git_PostReceiveMailDao by repository_id
     * @param Integer $repositoryId
     *
     * @return DataAccessResult
     */
    public function searchByRepositoryId($repositoryId)
    {
        $repository_id = $this->da->escapeInt($repositoryId);
        $sql = "
            SELECT recipient_mail
            FROM plugin_git_post_receive_mail
            WHERE repository_id = $repository_id";

        return $this->retrieve($sql);
    }

    /**
     * Create new entry for email Git repository notification
     *
     * @param Integer $repositoryId Id of the watched Git repository
     * @param String  $recipient email adress to notify
     *
     * @return Boolean
     */
    function createNotification($repositoryId, $recipient) {
        $sql = sprintf(
            'INSERT INTO plugin_git_post_receive_mail'.
            ' (recipient_mail, repository_id)'.
            ' VALUES'.
            ' (%s, %d)',
        $this->da->quoteSmart($recipient),
        $repositoryId);

        return $this->update($sql);
    }

    /**
     * Remove Git repository email notification
     *
     * @param Integer $repositoryId Id of the watched Git repository
     * @param String  $recipient email adress to remove from notification
     *
     * @return Boolean
     */
    function removeNotification($repositoryId, $recipient) {
        $criteria = null;
        if ($recipient !== null) {
            $criteria = ' AND recipient_mail = '.$this->da->quoteSmart($recipient);
        }
        $sql = 'DELETE FROM plugin_git_post_receive_mail '.
            'WHERE repository_id = '.$repositoryId.$criteria;

        return $this->update($sql);
    }

}
