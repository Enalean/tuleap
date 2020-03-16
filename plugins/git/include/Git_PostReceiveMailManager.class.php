<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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


class Git_PostReceiveMailManager
{

    public $dao;

    /**
     * Constructor of the class
     *
     * @return void
     */
    public function __construct()
    {
        $this->dao = $this->_getDao();
    }

    /**
     * Add a mail address to a repository to be notified
     *
     * @param int $repositoryId
     * @param String  $mail
     *
     * @return bool
     */
    public function addMail($repositoryId, $mail)
    {
        try {
            $this->dao->createNotification($repositoryId, $mail);
        } catch (PDOException $e) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Mail to notify not added'));
            return false;
        }
        return true;
    }

    /**
     * Remove from a repository the specified mail address if given
     * else remove all notified mails
     *
     * @param GitRepository  $repository
     * @param String  $mail
     *
     *  @return bool
     */
    public function removeMailByRepository($repository, $mail)
    {
        if ($this->dao->removeNotification($repository->getId(), $mail)) {
            $repository->loadNotifiedMails();
            return $repository->getBackend()->changeRepositoryMailingList($repository);
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Mail not removed'));
            return false;
        }
    }

    /**
     * On repository deletion, remove notified people in DB.
     *
     * As repository is meant to be deleted, there is no need to propagate
     * change to backend.
     *
     *
     * @return bool
     */
    public function markRepositoryAsDeleted(GitRepository $repository)
    {
        return $this->dao->removeNotification($repository->getId(), null);
    }

    /**
     * Returns the list of notified mails for post commit
     *
     * @param int $repositoryId Id of the repository to retrieve itsnotification mails
     *
     * @return array
     */
    public function getNotificationMailsByRepositoryId($repositoryId)
    {
        $dar = $this->dao->searchByRepositoryId($repositoryId);

        $mailList = array();
        foreach ($dar as $row) {
            $mailList[] = $row['recipient_mail'];
        }
        return $mailList;
    }

    /**
     * Obtain an instance of Git_PostReceiveMailDao
     *
     * @return Git_PostReceiveMailDao
     */
    public function _getDao()
    {
        if (!$this->dao) {
            $this->dao = new Git_PostReceiveMailDao();
        }
        return  $this->dao;
    }

    /**
     * Wrapper used for tests to get a new GitDao
     */
    public function _getGitDao()
    {
        return new GitDao();
    }

    /**
     * Wrapper used for tests to get a new GitRepository
     */
    public function _getGitRepository()
    {
        return new GitRepository();
    }
}
