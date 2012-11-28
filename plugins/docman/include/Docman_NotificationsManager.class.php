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

require_once('common/event/NotificationsManager.class.php');
require_once('common/mail/Mail.class.php');
require_once('Docman_ItemFactory.class.php');
require_once('Docman_Path.class.php');
require_once('DocmanConstants.class.php');
require_once('Docman_NotificationsDao.class.php');

class Docman_NotificationsManager extends NotificationsManager { 

    const MESSAGE_MODIFIED        = 'modified';
    const MESSAGE_NEWVERSION      = 'new_version';
    const MESSAGE_WIKI_NEWVERSION = 'new_wiki_version';
    
    var $_listeners;
    var $_feedback;
    var $_item_factory;
    var $_messages;
    var $_url;
    var $_group_id;
    var $_group_name;

    function __construct($group_id, $url, $feedback) {
        parent::__construct();

        $this->_group_id     =  $group_id;
        $this->_url          =  $url;
        $this->_listeners    =  array();
        $this->_feedback     = $feedback;
        $this->_item_factory =  $this->_getItemFactory();
        $this->_messages     =  array();
        if (($g = $this->_groupGetObject($group_id)) && !$g->isError()) {
            $this->_group_name = $g->getPublicName();
        }
    }
    function _getItemFactory() {
        return new Docman_ItemFactory();
    }
    function _groupGetObject($group_id) {
        return ProjectManager::instance()->getProject($group_id);
    }
    function _getUserManager() {
        return UserManager::instance();
    }
    function _getPermissionsManager() {
        return Docman_PermissionsManager::instance($this->_group_id);
    }
    function _getDocmanPath() {
        return new Docman_Path();
    }
    function _getMail() {
        return new Mail();
    }
    function somethingHappen($event, $params) {
        $um             = $this->_getUserManager();
        $params['path'] = $this->_getDocmanPath();
        $users          = $this->_getListeningUsers($this->_getListeningUsersItemId($params));
        if ($users) {
            while($users->valid()) {
                $u    = $users->current();
                $user = $um->getUserById($u['user_id']);
                if ($user->isActive() || $user->isRestricted()) {
                    $dpm = $this->_getPermissionsManager();
                    if ($dpm->userCanAccess($user, $params['item']->getId()) && $dpm->userCanAccess($user, $u['object_id'])) {
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
        foreach($this->_messages as $message) {
            $m = $this->_getMail();
            $m->setFrom($GLOBALS['sys_noreply']);
            $m->setSubject($message['title']);
            $m->setBody($message['content']);
            $to = array_chunk($message['to'], 50); //We send 50 bcc at once
            foreach($to as $sub_to) {
                $cc = '';
                foreach($sub_to as $recipient) {
                    $cc .= ','. $recipient->getEmail();
                }
                $m->setBcc($cc);
                $success &= $m->send();
            }
        }
        if (!$success) {
            $this->_feedback->log('warning', 'Error when sending some notifications.');
        }
    }

    /* protected */ function _getType() {
        return PLUGIN_DOCMAN_NOTIFICATION;
    }
    function _getListeningUsers($id) {
        //search for users who monitor the item or its parent
        $users = array();
        $this->_getListeningUsersForAscendantHierarchy($id, $users, $this->_getType());
        return new ArrayIterator($users);
    }
   /**
    * Retrieve list of users that are monitoring a given item.
    *
    * @param Integer $id    ID of the item that we are looking for its listeners.
    * @param Array   $users Array where listeners are inserted.
    * @param String  $type  Type of listener, in order to retrieve listeners that monitor this item on a sub-hierarchy or not.
    *
    * @return void
    */
    function _getListeningUsersForAscendantHierarchy($id, &$users, $type = null) {
        if ($id) {
            $u = $this->dao->searchUserIdByObjectIdAndType($id, $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
            if ($u) {
                while ($u->valid()) {
                    $users[] = $u->current();
                    $u->next();
                }
            }
            if ($item = $this->_item_factory->getItemFromDb($id)) {
                $this->_getListeningUsersForAscendantHierarchy($item->getParentId(), $users, $type);
            }
        }
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
            )
        );
    }
    function _addMessage($to, $subject, $msg) {
        $md5 = md5($msg);
        if (!isset($this->_messages[$md5])) {
            $this->_messages[$md5] = array(
                'title'   => '['. util_unconvert_htmlspecialchars($this->_group_name) .' - Documents] '. $subject,
                'content' => $msg,
                'to'      => array()
            );
        }
        $this->_messages[$md5]['to'][$to->getId()] = $to;
    }

    /**
     * Notify each user in his preferred language
     *
     * @param PFUser $user User to be notified
     *
     * @return BaseLanguage
     */
    function _getLanguageForUser($user) {
        // TODO : return the language depending on user preferences
        return $GLOBALS['Language'];
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
        $language = $this->_getLanguageForUser($user);
        switch($message_type) {
            case self::MESSAGE_MODIFIED:
            case self::MESSAGE_NEWVERSION:
                $msg .= $params['path']->get($params['item']) .' '.$language->getText('plugin_docman', 'notif_modified_by').' '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=details&id='. $params['item']->getId() ."\n";
                break;
            case self::MESSAGE_WIKI_NEWVERSION:
                $msg .= $language->getText('plugin_docman', 'notif_wiki_new_version', $params['wiki_page']).' ' . $user->getRealName() . ".\n";
                $msg .= $params['url'] . "\n";
                break;
            default:
                $msg .= $language->getText('plugin_docman', 'notif_something_happen');
                break;
        }
        $msg .= "\n\n--------------------------------------------------------------------\n";
        $msg .= $language->getText('plugin_docman', 'notif_footer_message')."\n";
        $msg .= $language->getText('plugin_docman', 'notif_footer_message_link')."\n";
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
        $notificationsDao = new Docman_NotificationsDao(CodendiDataAccess::instance());
        return $notificationsDao->searchDocmanMonitoredItems($groupId, $userId);
    }

}
?>
