<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class Docman_ApprovalTableReviewerFactory
{
    var $table;
    var $item;
    var $reviewerCache;
    var $err;
    var $warn;
    private $notificationManager = null;

    function __construct($table, $item, $notificationManager = null)
    {
        $this->table = $table;
        $this->item  = $item;
        $this->reviewerCache = null;

        // Cache of error messages
        $this->err = array();
        $this->err['db'] = array();
        $this->err['perm'] = array();
        $this->err['notreg'] = array();
        $this->warn = array();
        $this->warn['double'] = array();

        $this->notificationManager = $notificationManager;
    }

    /**
     * @return Docman_ApprovalTable
     */
    public function getTable()
    {
        return $this->table;
    }

    function createReviewerFromRow($row)
    {
        $reviewer = new Docman_ApprovalReviewer();
        $reviewer->initFromRow($row);
        return $reviewer;
    }

    /**
     * Handle table notification.
     *
     * Trigger notification if all the parameters allow it:
     * - table exist.
     * - table enabled.
     * - notification not disabled.
     */
    function notifyReviewers()
    {
        $res = false;

        if ($this->table !== null
           && $this->table->isEnabled()
           && $this->table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
            $atsm = $this->_getApprovalTableNotificationCycle();
            switch ($this->table->getNotification()) {
                case PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE:
                    $res = $atsm->notifyAllAtOnce();
                    break;
                case PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL:
                    $res = $atsm->notifyNextReviewer();
                    break;
                default:
            }
        }
        return $res;
    }

    function appendReviewerList()
    {
        if ($this->table !== null) {
            $dao = $this->_getDao();
            $dar = $dao->getReviewerList($this->table->getId());
            $dar->rewind();
            while ($dar->valid()) {
                $row = $dar->current();
                $reviewer = $this->createReviewerFromRow($row);
                $this->table->addReviewer($reviewer);
                unset($reviewer);
                $dar->next();
            }
        }
    }

    /**
     * Return the list of ugroup selectable to fill the notification table.
     *
     * It contains: all dynamic ugroups plus project members and admins.
     */
    function getUgroupsAllowedForTable($groupId)
    {
        /** @psalm-suppress DeprecatedFunction */
        $res = ugroup_db_get_existing_ugroups($groupId, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $ugroups = array();
        while ($row = db_fetch_array($res)) {
            $r = array();
            $r['value'] = $row['ugroup_id'];
            $r['text'] = util_translate_name_ugroup($row['name']);
            $ugroups[] = $r;
        }

        return $ugroups;
    }

    /**
     * Create reviewer from database.
     * This method update 'isReviewer' cache (see corresponding method)
     *
     * @return Docman_ApprovalReviewer
     */
    function getReviewer($userId)
    {
        $reviewer = null;
        $dao = $this->_getDao();
        $dar = $dao->getReviewerById($this->table->getId(), $userId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $reviewer = $this->createReviewerFromRow($row);
            $this->reviewerCache[$row['reviewer_id']] = true;
        }
        return $reviewer;
    }

    /**
     * @return Docman_ApprovalReviewer[]
     */
    public function getReviewerListForLatestVersion()
    {
        $list = array();
        if ($this->reviewerCache === null) {
            $this->reviewerCache = array();

            $dao = $this->_getDao();
            foreach ($dao->getReviewerList($this->table->getId()) as $row) {
                $this->reviewerCache[$row['reviewer_id']] = true;

                $list[] = $this->createReviewerFromRow($row);
            }
        }

        return $list;
    }

    /**
     * Return true if given userid is member of the current table or not.
     * There is a cache for this information (the membership of users).
     */
    function isReviewer($userId)
    {
        if ($this->reviewerCache === null) {
            $dao = $this->_getDao();
            $dar = $dao->getReviewerList($this->table->getId());
            $dar->rewind();
            while ($dar->valid()) {
                $row = $dar->current();
                $this->reviewerCache[$row['reviewer_id']] = true;
                $dar->next();
            }
        }
        if (isset($this->reviewerCache[$userId])) {
            return true;
        }
        return false;
    }

    /**
     * Add given user into the reviewer list if she's not already member.
     *
     * @access: private
     */
    function _addUser($userId)
    {
        $dPm = Docman_PermissionsManager::instance($this->item->getGroupId());
        $um = $this->_getUserManager();
        $user = $um->getUserById($userId);
        if ($dPm->userCanRead($user, $this->item->getId())) {
            if (!$this->isReviewer($user->getId())) {
                $dao = $this->_getDao();
                $added = $dao->addUser($this->table->getId(), $user->getId());
                if ($added) {
                    $this->reviewerCache[$user->getId()] = true;
                    return true;
                } else {
                    $this->err['db'][] = $user->getRealName();
                }
            } else {
                $this->warn['double'][] = $user->getRealName();
            }
        } else {
            $this->err['perm'][] = $user->getRealName();
        }
        return false;
    }

    /**
     * Add a list of user in the reviewer list.
     *
     * Note: we don't test if the user can read the document she has to review.
     * @todo: Report users not found in the Codendi user list.
     *
     * @param Array of string $userArray this method try to find a matching
     *   user in the given list. A matching user is a Active or Restricted
     *   Codendi user.
     * @return int number of users added.
     */
    function addUsers($userArray)
    {
        $nbUserAdded = 0;
        foreach ($userArray as $user) {
            $added = false;
            $u = UserManager::instance()->findUser($user);
            if ($u) {
                $added = $this->_addUser($u->getId());
                if ($added) {
                    $nbUserAdded++;
                }
            } else {
                $this->err['notreg'][] = $user;
            }
        }
        return $nbUserAdded;
    }

    /**
     * Add members of the given ugroup to the reviewer list.
     *
     * @return true if at least one user was added to the list.
     */
    function addUgroup($ugroupId)
    {
        $nbUserAdded = 0;
        $nbMembers = 0;

        $dao = $this->_getDao();
        $dar = $dao->getUgroupMembers($ugroupId, $this->item->getGroupId());
        if ($dar && !$dar->isError()) {
            $dar->rewind();
            while ($dar->valid()) {
                $nbMembers++;
                $row = $dar->current();
                $added = $this->_addUser($row['user_id']);
                if ($added) {
                    $nbUserAdded++;
                }
                $dar->next();
            }
        }
        if ($nbUserAdded == $nbMembers) {
            return true;
        }
        return false;
    }

    /**
     * Update user rank in the reviewer list.
     */
    function updateUser($userId, $rank)
    {
        $dao = $this->_getDao();
        return $dao->updateUser($this->table->getId(), $userId, $rank);
    }

    /**
     * Delete user from reviewer list.
     */
    function delUser($userId)
    {
        $dao = $this->_getDao();
        $deleted = $dao->delUser($this->table->getId(), $userId);
        if ($deleted) {
            if (isset($this->reviewerCache[$userId])) {
                unset($this->reviewerCache[$userId]);
            }
            return true;
        }
        return false;
    }

    /**
     * Delete all the member of the table
     */
    function deleteTable()
    {
        $dao = $this->_getDao();
        return $dao->truncateTable($this->table->getId());
    }

    /**
     * Update user review.
     */
    function updateReview($review)
    {
        $dao = $this->_getDao();
        $updated = $dao->updateReview(
            $this->table->getId(),
            $review->getId(),
            $review->getReviewDate(),
            $review->getState(),
            $review->getComment(),
            $review->getVersion()
        );
        $this->table->addReviewer($review);
        if ($updated) {
            $atsm = $this->_getApprovalTableNotificationCycle();
            $atsm->reviewUpdated($review);
            return true;
        }
        return false;
    }

    function newTableCopy($newTableId)
    {
        $dao = $this->_getDao();
        return $dao->copyReviews($this->table->getId(), $newTableId);
    }

    function newTableReset($newTableId)
    {
        $dao = $this->_getDao();
        return $dao->copyReviewers($this->table->getId(), $newTableId);
    }

    /**
     * Return all the review where the user doesn't commit himself yet.
     */
    /*static*/ function getAllPendingReviewsForUser($userId)
    {
        $reviewsArray = array();
        $dao = Docman_ApprovalTableReviewerFactory::_getDao();
        $dar = $dao->getAllReviewsForUserByState($userId, PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
        $docmanUrl = HTTPRequest::instance()->getServerUrl() .'/plugins/docman';
        while ($dar->valid()) {
            $row = $dar->current();
            $baseUrl = $docmanUrl.'/?group_id='.$row['group_id'];
            $url = $baseUrl.'&action=details&section=approval&id='.$row['item_id'].'&review=1';
            $reviewsArray[] = array('group' => $row['group_name'],
                                    'group_id' => $row['group_id'],
                                    'title' => $row['title'],
                                    'date'  => $row['date'],
                                    'url'   => $url);
            $dar->next();
        }
        return $reviewsArray;
    }

    /**
     * Return all the approval table not deleted and not closed where the user
     * is the table owner.
     */
    /*static*/ function getAllApprovalTableForUser($userId)
    {
        $reviewsArray = array();
        $dao = Docman_ApprovalTableReviewerFactory::_getDao();
        $dar = $dao->getAllApprovalTableForUser($userId);
        $docmanUrl = HTTPRequest::instance()->getServerUrl().'/plugins/docman';
        while ($dar->valid()) {
            $row = $dar->current();

            // Review URL
            $baseUrl = $docmanUrl.'/?group_id='.$row['group_id'];
            $url = $baseUrl.'&action=details&section=approval&id='.$row['item_id'];

            // Status
            $status = '';
            if ($row['status'] == PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED) {
                $approvalState = Docman_ApprovalTable::computeApprovalState($row);
                if ($approvalState !== null) {
                    $status = $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.$approvalState);
                }
            }
            if ($status == '') {
                $status = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.$row['status']);
            }

            $reviewsArray[] = array('group' => $row['group_name'],
                                    'group_id' => $row['group_id'],
                                    'title' => $row['title'],
                                    'date'  => $row['date'],
                                    'url'   => $url,
                                    'status' => $status);
            $dar->next();
        }
        return $reviewsArray;
    }

    // Class accessor
    function _getDao()
    {
        $dao = new Docman_ApprovalTableReviewerDao(CodendiDataAccess::instance());
        return $dao;
    }

    function _getMail()
    {
        return new Codendi_Mail();
    }

    function _getUserManager()
    {
        $um = UserManager::instance();
        return $um;
    }

    function _getApprovalTableNotificationCycle()
    {
        $atsm = new Docman_ApprovalTableNotificationCycle(
            new MailNotificationBuilder(
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        UserManager::instance(),
                        new ProjectAccessChecker(
                            PermissionsOverrider_PermissionsOverriderManager::instance(),
                            new RestrictedUserCanAccessProjectVerifier(),
                            EventManager::instance()
                        ),
                        new MailLogger()
                    )
                )
            )
        );

        $atsm->setTable($this->table);
        $atsm->setItem($this->item);

        $um = $this->_getUserManager();
        $owner = $um->getUserById($this->table->getOwner());
        $atsm->setOwner($owner);

        if ($this->notificationManager !== null) {
            $atsm->setNotificationManager($this->notificationManager);
        }

        return $atsm;
    }

    function setNotificationManager($notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }
}
