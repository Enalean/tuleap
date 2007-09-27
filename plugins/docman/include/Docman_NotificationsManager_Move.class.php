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
 * $Id$
 */
require_once('Docman_NotificationsManager.class.php');
require_once('Docman_Path.class.php');
class Docman_NotificationsManager_Move extends Docman_NotificationsManager { 

    var $MESSAGE_MOVED      = 'moved';      // X has been moved from to
    var $MESSAGE_MOVED_FROM = 'moved_from'; // X has been moved from
    var $MESSAGE_MOVED_TO   = 'moved_to';   // X has been moved to
    
    function Docman_NotificationsManager_Move($group_id, $url, &$feedback) {
        parent::Docman_NotificationsManager($group_id, $url, $feedback);
    }
    function somethingHappen($event, $params) {
        if ($event == PLUGIN_DOCMAN_EVENT_MOVE) {
            if ($params['item']->getParentId() != $params['parent']->getId()) {
                $params['path'] =& $this->_getDocmanPath();
                $this->_buildMessagesForUsers($this->_getListeningUsers($params['item']->getId()), $this->MESSAGE_MOVED, $params);
                $this->_buildMessagesForUsers($this->_getListeningUsers($params['parent']->getId()), $this->MESSAGE_MOVED_TO, $params);
                $this->_buildMessagesForUsers($this->_getListeningUsers($params['item']->getParentId()), $this->MESSAGE_MOVED_FROM, $params);
            }
        }
    }
    var $do_not_send_notifications_to;
    function _buildMessagesForUsers(&$users, $type, $params) {
        if ($users) {
            $um =& $this->_getUserManager();
            while($users->valid()) {
                $u = $users->current();
                $user =& $um->getUserById($u['user_id']);
                $dpm =& $this->_getPermissionsManager();
                if ($dpm->userCanRead($user, $params['item']->getId()) && ($dpm->userCanAccess($user, $params['parent']->getId()) || $dpm->userCanAccess($user, $params['item']->getParentId())) && ($u['object_id'] == $params['item']->getId() || $dpm->userCanAccess($user, $u['object_id']))) {
                    if (!isset($this->do_not_send_notifications_to[$user->getId()])) {
                        $this->_buildMessage(array_merge($params, array('user_monitor' => &$user)), $user, $type);
                        $this->do_not_send_notifications_to[$user->getId()] = true;
                    }
                }
                $users->next();
            }
        }
    }
    function _buildMessage($params, $user, $type) {
        $params['old_parent'] =& $this->_item_factory->getItemFromDb($params['item']->getParentId());
        $this->_addMessage(
            $user, 
            $type == $this->MESSAGE_MOVED ? $params['item']->getTitle() : (  $type == $this->MESSAGE_MOVED_FROM ? $params['old_parent']->getTitle() : $params['parent']->getTitle() ),
            $this->_getMessageForUser(
                $params['user'], 
                $type, 
                $params
            )
        );
    }
    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        $dpm =& $this->_getPermissionsManager();
        switch($message_type) {
            case $this->MESSAGE_MOVED:
                $msg .= $params['item']->getTitle() .' has been modified by '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=show&id='. $params['parent']->getId() ."\n";
                $msg .= "\nMoved ";
                $need_sep = false;
                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= "from:";
                    $msg .= "\n            ". $params['path']->get($params['old_parent']);
                    $need_sep = true;
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    if ($need_sep) {
                        $msg .= "\n        ";
                    }
                    $msg .= "to:";
                    $msg .= "\n            ". $params['path']->get($params['parent']);
                }
                $msg .= "\n\n--------------------------------------------------------------------\n";
                $msg .= "You are receiving this message because you are monitoring this item.\n";
                $msg .= "To stop monitoring, please visit:\n";
                $msg .= $this->_url .'&action=details&section=notifications&id='. $params['item']->getId();
                break;
            case $this->MESSAGE_MOVED_FROM:
                $msg .= $params['path']->get($params['old_parent']) .' has been modified by '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=show&id='. $params['parent']->getId() ."\n";
                $msg .= "\n ". $params['item']->getTitle() ." moved";
                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= "\n    from:";
                    $msg .= "\n          ". $params['path']->get($params['old_parent']);
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    $msg .= "\n    to:";
                    $msg .= "\n          ". $params['path']->get($params['parent']);
                }
                $msg .= "\n\n----------------------------------------------------------------------\n";
                $msg .= "You are receiving this message because you are monitoring this folder.\n";
                $msg .= "To stop monitoring, please visit:\n";
                $msg .= $this->_url .'&action=details&section=notifications&id='. $params['old_parent']->getId();
                break;
            case $this->MESSAGE_MOVED_TO:
                $msg .= $params['path']->get($params['parent']) .' has been modified by '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=show&id='. $params['parent']->getId() ."\n";
                $msg .= "\n ". $params['item']->getTitle() ." moved";
                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= "\n    from:";
                    $msg .= "\n          ". $params['path']->get($params['old_parent']);
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    $msg .= "\n    to:";
                    $msg .= "\n          ". $params['path']->get($params['parent']);
                }
                $msg .= "\n\n----------------------------------------------------------------------\n";
                $msg .= "You are receiving this message because you are monitoring this folder.\n";
                $msg .= "To stop monitoring, please visit:\n";
                $msg .= $this->_url .'&action=details&section=notifications&id='. $params['parent']->getId();
                break;
            default:
                $msg .= parent::_getMessageForUser($user, $message_type, $params);
                break;
        }
        return $msg;
    }
}

?>