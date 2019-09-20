<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

/**
 *  Data Access Object for Git_PostReceiveMail
 */
class Git_PostReceiveMailDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * Searches Git_PostReceiveMailDao by repository_id
     * @param int $repositoryId
     *
     * @return DataAccessResult
     */
    public function searchByRepositoryId($repositoryId)
    {
        $sql = '
            SELECT recipient_mail
            FROM plugin_git_post_receive_mail
            WHERE repository_id = ?';

        return $this->getDB()->run($sql, $repositoryId);
    }

    public function createNotification($repositoryId, $recipient)
    {
        $sql = 'INSERT INTO plugin_git_post_receive_mail (recipient_mail, repository_id) VALUES (?, ?)';

        $this->getDB()->run($sql, $recipient, $repositoryId);
    }

    /**
     * Remove Git repository email notification
     *
     * @param int $repositoryId Id of the watched Git repository
     * @param String  $recipient email adress to remove from notification
     *
     * @return bool
     */
    public function removeNotification($repositoryId, $recipient)
    {
        $where_statement = \ParagonIE\EasyDB\EasyStatement::open()->with('repository_id = ?', $repositoryId);
        if ($recipient !== null) {
            $where_statement->andWith('recipient_mail = ?', $recipient);
        }
        $sql = "DELETE FROM plugin_git_post_receive_mail WHERE $where_statement";

        try {
            $this->getDB()->safeQuery($sql, $where_statement->values());
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }
}
