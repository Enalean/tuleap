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

require_once 'Git_PostReceiveMailDao.class.php';
require_once 'GitDao.class.php';

class Git_PostReceiveMailManager {

    var $dao;

    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        $this->dao = $this->_getDao();
    }

    /**
     * Add a mail address to a repository to be notified
     *
     * @param Integer $repositoryId
     * @param String  $mail
     *
     * @return Boolean
     */
    function addMail($repositoryId, $mail) {
        try {
            $this->dao->createNotification($repositoryId, $mail);
        } catch (GitDaoException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'dao_error_create_notification'));
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
     *  @return Boolean
     */
    function removeMailByRepository($repository, $mail) {
        if ($this->dao->removeNotification($repository->getId(), $mail)) {
            $repository->loadNotifiedMails();
            return $repository->getBackend()->changeRepositoryMailingList($repository);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'dao_error_remove_notification'));
            return false;
        }
    }

    /**
     * On repository deletion, remove notified people in DB.
     *
     * As repository is meant to be deleted, there is no need to propagate
     * change to backend.
     *
     * @param GitRepository $repository
     *
     * @return Boolean
     */
    public function markRepositoryAsDeleted(GitRepository $repository) {
        return $this->dao->removeNotification($repository->getId(), null);
    }

    /**
     * Remove a notified mail address from all private repositories of a project
     *
     * @param Integer $groupId Porject ID to remove its repositories notification
     * @param PFUser    $user    User to exclude from notification
     *
     * @return void
     */
    function removeMailByProjectPrivateRepository($groupId, $user) {
        if (!$user->isMember($groupId)) {
            $gitDao = $this->_getGitDao();
            $repositoryList = $gitDao->getProjectRepositoryList($groupId);

            if ($repositoryList) {
                foreach ($repositoryList as $row) {
                    $repository   = $this->_getGitRepository();
                    $repository->setId($row['repository_id']);
                    $repository->load();
                    if (!$repository->userCanRead($user)) {
                        if (!$this->removeMailByRepository($repository, $user->getEmail())) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'dao_error_remove_notification'));
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns the list of notified mails for post commit
     *
     * @param Integer $repositoryId Id of the repository to retrieve itsnotification mails
     *
     * @return array
     */
    public function getNotificationMailsByRepositoryId($repositoryId) {
        $dar = $this->dao->searchByRepositoryId($repositoryId);

        $mailList = array();
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            foreach ($dar as $row ) {
                $mailList [] = $row['recipient_mail'];
            }
        }
        return $mailList;
    }

    /**
     * Obtain an instance of Git_PostReceiveMailDao
     *
     * @return Git_PostReceiveMailDao
     */
    function _getDao() {
        if (!$this->dao) {
            $this->dao = new Git_PostReceiveMailDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }

    /**
     * Wrapper used for tests to get a new GitDao
     */
    function _getGitDao() {
        return new GitDao();
    }

    /**
     * Wrapper used for tests to get a new GitRepository
     */
    function _getGitRepository() {
        return new GitRepository();
    }

}

?>
