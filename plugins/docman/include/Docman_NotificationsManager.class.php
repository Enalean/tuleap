<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2015 - 2017. All rights reserved
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

use Tuleap\Docman\Notifications\Dao;
use Tuleap\Docman\Notifications\UsersRetriever;

require_once('common/mail/Mail.class.php');

class Docman_NotificationsManager
{

    const MESSAGE_MODIFIED        = 'modified';
    const MESSAGE_NEWVERSION      = 'new_version';
    const MESSAGE_WIKI_NEWVERSION = 'new_wiki_version';

    var $_listeners;
    var $_feedback;
    var $_item_factory;
    /** @var array */
    private $notifications;
    var $_url;

    /**
     * @var Project
     */
    var $project;

    var $_group_name;

    /**
     * @var MailBuilder
     */
    private $mail_builder;
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var UsersRetriever
     */
    protected $users_retriever;

    public function __construct(
        Project $project,
        $url,
        $feedback,
        MailBuilder $mail_builder,
        Dao $dao,
        UsersRetriever $users_retriever
    ) {
        $this->project       = $project;
        $this->_url          = $url;
        $this->_listeners    = array();
        $this->_feedback     = $feedback;
        $this->_item_factory = $this->_getItemFactory();
        $this->notifications = array();
        $this->mail_builder  = $mail_builder;
        if ($project && !$project->isError()) {
            $this->_group_name = $project->getPublicName();
        }
        $this->dao             = $dao;
        $this->users_retriever = $users_retriever;
    }
    function _getItemFactory() {
        return new Docman_ItemFactory();
    }
    function _getUserManager() {
        return UserManager::instance();
    }
    function _getPermissionsManager() {
        return Docman_PermissionsManager::instance($this->project->getID());
    }
    function _getDocmanPath() {
        return new Docman_Path();
    }
    function somethingHappen($event, $params) {
        $um             = $this->_getUserManager();
        $params['path'] = $this->_getDocmanPath();
        $users          = $this->users_retriever->getNotifiedUsers(
            $this->project,
            $this->_getListeningUsersItemId($params)
        );
        if ($users) {
            while($users->valid()) {
                $u    = $users->current();
                $user = $um->getUserById($u['user_id']);
                if ($user->isActive() || $user->isRestricted()) {
                    $dpm = $this->_getPermissionsManager();
                    if ($dpm->userCanAccess($user, $params['item']->getId()) && $dpm->userCanAccess($user, $u['item_id'])) {
                        $this->_buildMessage($event, $params, $user);
                    }
                }
                $users->next();
            }
        }
    }
    function _getListeningUsersItemId($params) {
        return $params['item']->getId();
    }

    function sendNotifications($event, $params) {
        $success = true;
        foreach($this->notifications as $notification) {
            $success &= $this->mail_builder->buildAndSendEmail($this->project, $notification, new MailEnhancer());
        }
        if (!$success) {
            $this->_feedback->log('warning', 'Error when sending some notifications.');
        }
    }

    /* protected */ function _getType() {
        return PLUGIN_DOCMAN_NOTIFICATION;
    }

   /**
    * Returns the list of users monitoring the given item with an array associated to the item the user actually monitors:
    * getListeningUsers(item(10))
    * =>
    *  array(101 => item(10) // The user is monitoring the item(10) directly
    *        102 => item(20) // The user is monitoring item(10) through item(20) "sub-hierarchy"
    *  )
    *
    * @param Docman_Item $item  Item which listenners will be retrieved
    * @param Array       $users Array where listeners are inserted.
    * @param String      $type  Type of listener, in order to retrieve listeners that monitor this item on a sub-hierarchy or not.
    *
    * @return Array
    */
    public function getListeningUsers(Docman_Item $item, $users = array(), $type = PLUGIN_DOCMAN_NOTIFICATION) {
        $dar = $this->dao->searchUserIdByObjectIdAndType($item->getId(), $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        if ($dar) {
            foreach ($dar as $user) {
                if (!array_key_exists($user['user_id'], $users)) {
                    $users[$user['user_id']] = $item;
                }
            }
        }
        if ($id = $item->getParentId()) {
            $item = $this->_item_factory->getItemFromDb($id);
            $users = $this->getListeningUsers($item, $users, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        }
        return $users;
    }

    function _buildMessage($event, $params, $user) {
        $type = '';
        switch($event) {
            case 'plugin_docman_event_edit':
            case 'plugin_docman_event_metadata_update':
                $type = self::MESSAGE_MODIFIED;
                break;
            case 'plugin_docman_event_new_version':
                $type = self::MESSAGE_NEWVERSION;
                break;
            case 'plugin_docman_event_wikipage_update':
                $type = self::MESSAGE_WIKI_NEWVERSION;
                break;
            default:
                break;
        }
        $this->_addMessage(
            $user,
            $params['item']->getTitle(),
            $this->_getMessageForUser(
                $params['user'],
                $type,
                $params
            ),
            $this->getMessageLink($type, $params)
        );
    }

    protected function _addMessage(PFUser $to, $subject, $msg, $link) {
        if (!isset($this->notifications[$msg])) {
            $subject = '['. util_unconvert_htmlspecialchars($this->_group_name) .' - Documents] '. $subject;

            $this->notifications[$msg] = new Notification(
                array(),
                $subject,
                nl2br($msg),
                $msg,
                $link,
                'Documents'
            );
        }
        $this->notifications[$msg]->addEmail($to->getEmail());
    }

    protected function getMessageLink($type, $params) {
        if($this->project->getTruncatedEmailsUsage()) {
            return $this->_url.'&action=details&id='. $params['item']->getId().'&section=history';
        }

        switch($type) {
            case self::MESSAGE_MODIFIED:
            case self::MESSAGE_NEWVERSION:
                $link = $this->_url .'&action=details&id='. $params['item']->getId();
                break;
            case self::MESSAGE_WIKI_NEWVERSION:
                $link = $params['url'];
                break;
            default:
                $link = $this->_url;
                break;
        }
        return $link;
    }

    /**
     * Given an item monitored by user through "sub-hierarchy", retrieve the monitored parent.
     * @see getListeningUsers
     *
     * @param $user User monitoring the item
     * @param $item Item which parent is monitored
     *
     * @return Docman_Item
     */
    function _getMonitoredItemForUser($user, $item) {
        $listeners = $this->getListeningUsers($item);
        foreach ($listeners as $userId => $item) {
            if ($user->getId() == $userId) {
                return $item;
            }
        }
        return $item;
    }

    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        switch($message_type) {
            case self::MESSAGE_MODIFIED:
            case self::MESSAGE_NEWVERSION:
                $msg .= $params['path']->get($params['item']) .' '.$GLOBALS['Language']->getText('plugin_docman', 'notif_modified_by').' '. $user->getRealName() .".\n";
                $msg .= $this->getMessageLink($message_type, $params) ."\n";
                break;
            case self::MESSAGE_WIKI_NEWVERSION:
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_wiki_new_version', $params['wiki_page']).' ' . $user->getRealName() . ".\n";
                $msg .= $this->getMessageLink($message_type, $params) . "\n";
                break;
            default:
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_something_happen');
                break;
        }
        $msg .= "\n\n--------------------------------------------------------------------\n";
        $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_footer_message')."\n";
        $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_footer_message_link')."\n";
        $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
        $msg .= $this->_url .'&action=details&section=notifications&id='. $monitoredItem->getId();
        return $msg;
    }

    /**
     * Retrieve all monitored Items (folders & documents) of a given project
     * and if provided by user
     *
     * @param $groupId
     * @param $userId
     *
     * @return Boolean
     */
    function listAllMonitoredItems($groupId, $userId = null) {
        return $this->dao->searchDocmanMonitoredItems($groupId, $userId);
    }

    public function add($user_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->dao->create($user_id, $item_id, $type);
    }

    public function remove($user_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->dao->delete($user_id, $item_id, $type);
    }

    public function exist($user_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        $dar = $this->dao->search($user_id, $item_id, $type);
        return $dar->valid();
    }

}
