<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/event/NotificationsManager.class.php');
require_once('common/mail/Mail.class.php');
require_once('Docman_ItemFactory.class.php');
require_once('Docman_Path.class.php');
require_once('DocmanConstants.class.php');

class Docman_NotificationsManager extends NotificationsManager { 

    var $MESSAGE_MODIFIED   = 'modified';
    var $MESSAGE_NEWVERSION = 'new_version';
    
    var $_listeners;
    var $_feedback;
    var $_item_factory;
    var $_messages;
    var $_url;
    var $_group_id;
    var $_group_name;
    function Docman_NotificationsManager($group_id, $url, &$feedback) {
        parent::NotificationsManager();
        $this->_group_id     =  $group_id;
        $this->_url          =  $url;
        $this->_listeners    =  array();
        $this->_feedback     =& $feedback;
        $this->_item_factory =& $this->_getItemFactory();
        $this->_messages     =  array();
        if ($g =& $this->_groupGetObject($group_id)) {
            $this->_group_name = $g->getPublicName();
        }
    }
    function &_getItemFactory() {
        return new Docman_ItemFactory();
    }
    function &_groupGetObject($group_id) {
        return group_get_object($group_id);
    }
    function &_getUserManager() {
        return UserManager::instance();
    }
    function &_getPermissionsManager() {
        return Docman_PermissionsManager::instance($this->_group_id);
    }
    function &_getDocmanPath() {
        return new Docman_Path();
    }
    function somethingHappen($event, $params) {
        $um =& $this->_getUserManager();
        $params['path'] =& $this->_getDocmanPath();
        $users = $this->_getListeningUsers($this->_getListeningUsersItemId($params));
        if ($users) {
            while($users->valid()) {
                $u = $users->current();
                $user =& $um->getUserById($u['user_id']);
                if ($user->isActive() || $user->isRestricted()) {
                    $dpm =& $this->_getPermissionsManager();
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
            $m = new Mail();
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
            $this->_feedback->log('error', 'Error when sending some notifications.');
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
    function _getListeningUsersForAscendantHierarchy($id, &$users, $type = null) {
        if ($id) {
            $u = $this->dao->searchUserIdByObjectIdAndType($id, $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
            if ($u) {
                while ($u->valid()) {
                    $users[] = $u->current();
                    $u->next();
                }
            }
            if ($item =& $this->_item_factory->getItemFromDb($id)) {
                $this->_getListeningUsersForAscendantHierarchy($item->getParentId(), $users, $type);
            }
        }
    }
    function _buildMessage($event, $params, $user) {
        $type = '';
        switch($event) {
            case PLUGIN_DOCMAN_EVENT_EDIT:
            case PLUGIN_DOCMAN_EVENT_METADATA_UPDATE:
                $type = $this->MESSAGE_MODIFIED;
                break;
            case PLUGIN_DOCMAN_EVENT_NEW_VERSION:
                $type = $this->MESSAGE_NEWVERSION;
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
                'title'   => '['. $this->_group_name .' - Documents] '. $subject,
                'content' => $msg,
                'to'      => array()
            );
        }
        $this->_messages[$md5]['to'][$to->getId()] =& $to;
    }
    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        switch($message_type) {
            case $this->MESSAGE_MODIFIED:
            case $this->MESSAGE_NEWVERSION:
                $msg .= $params['path']->get($params['item']) .' has been modified by '. $user->getRealName() .".\n";
                break;
            default:
                $msg .= 'Something happen !';
                break;
        }
        $msg .= "\n\n--------------------------------------------------------------------\n";
        $msg .= "You are receiving this message because you are monitoring this item.\n";
        $msg .= "To stop monitoring, please visit:\n";
        $msg .= $this->_url .'&action=details&section=notifications&id='. $params['item']->getId();
        return $msg;
    }
}
?>
