<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once('common/dao/include/DataAccessObject.class.php');
require_once('exceptions/GitDaoException.class.php');

/**
 *  Data Access Object for Git_PostReceiveMail
 */
class Git_PostReceiveMailDao extends DataAccessObject {
    /**
     * Constructs the Git_PostReceiveMailDao
     * @param $da instance of the DataAccess class
     */
    function Git_PostReceiveMailDao($da) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Searches Git_PostReceiveMailDao by repository_id
     *
     * @return DataAccessResult
     */
    function searchByRepositoryId($repositoryid) {
        $sql = sprintf("SELECT recipient_mail FROM plugin_git_post_receive_mail WHERE repository_id = %d",
        $this->da->quoteSmart($repositoryid));
        return $this->retrieve($sql);
    }

    function createNotification($repositoryId, $recipient) {
        $sql = sprintf('INSERT INTO plugin_git_post_receive_mail'.
                       ' (recipient_mail, repository_id)'.
                       ' VALUES'.
                       ' (%s, %d)',
        $this->da->quoteSmart($recipient),
        $repositorytId);
        if (!$this->update($sql)) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_error_create_notification') );
        }
        return true;
    }

    function removeNotification($repositoryId, $recipient) {
        $sql = sprintf('DELETE FROM plugin_git_post_receive_mail'.
                       ' WHERE recipient_mail = %s AND repository_id = %d ',
        $this->da->quoteSmart($recipient),
        $repositoryId);
        if (!$this->update($sql)) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_error_remove_notification') );
        }
        return true;
    }

}

?>