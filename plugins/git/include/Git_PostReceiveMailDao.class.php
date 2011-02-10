<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 *  Data Access Object for Git_PostReceiveMail
 */
class Git_PostReceiveMailDao extends DataAccessObject {
    /**
     * Constructs the Git_PostReceiveMailDao
     * @param $da instance of the DataAccess class
     */
    function Docman_LogDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Gets all tables of the db
     * @return DataAccessResult
     */
    function searchAll() {
        $sql = "SELECT * FROM plugin_git_post_receive_mail";
        return $this->retrieve($sql);
    }

    /**
     * Searches Git_PostReceiveMailDao by repository_id
     * @return DataAccessResult
     */
    function searchByRepositoryId($repositoryid) {
        $sql = sprintf("SELECT recipient_mail FROM plugin_git_post_receive_mail WHERE repository_id = %d",
        $this->da->quoteSmart($repositoryid));
        return $this->retrieve($sql);
    }

    function createUserNotification($recipient, $repositorytId) {
        $sql = sprintf('INSERT INTO plugin_git_post_receive_mail'.
                       ' (recipient_mail, repository_id)'.
                       ' VALUES'.
                       ' (%s, %d)',
        $this->da->quoteSmart($recipient),
        $repositorytId);
        return $this->update($sql);
    }

    function updateUserNotification($recipient, $repositoryId) {
        $sql = sprintf('UPDATE plugin_git_post_receive_mail'.
                       ' SET recipient_mail = %s'.
                       ' WHERE repository_id = %d',
        $this->da->quoteSmart($recipient),
        $repositoryId);
        return $this->update($sql);
    }

    function removeUserNotification($recipient, $repositoryId) {
        $sql = sprintf('DELETE FROM plugin_git_post_receive_mail'.
                       ' WHERE recipient_mail = %s AND repository_id = %d ',
        $this->da->quoteSmart($recipient),
        $repositoryId);
        return $this->update($sql);
    }

}

?>