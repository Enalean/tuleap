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
class Docman_NotificationsManager_Add extends Docman_NotificationsManager { 

    var $MESSAGE_ADDED = 'added'; // X has been added
    
    function Docman_NotificationsManager_Add($group_id, $url, &$feedback) {
        parent::Docman_NotificationsManager($group_id, $url, $feedback);
    }
    function _getListeningUsers($event, $params) {
        //search for users who monitor the parent item
        return $this->dao->searchUserIdByObjectIdAndType($params['parent']->getId(), $this->_getType());
    }
    function _buildMessage($event, $params, $user) {
        switch($event) {
            case PLUGIN_DOCMAN_EVENT_ADD:
                $parent =& $this->_item_factory->getItemFromDb($params['item']->getParentId());
                $this->_addMessage(
                    $user, 
                    $parent->getTitle(), 
                    $this->_getMessageForUser(
                        $params['user'], 
                        $this->MESSAGE_ADDED, 
                        $params
                    )
                );
                break;
            default:
                break;
        }
    }
    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        switch($message_type) {
            case $this->MESSAGE_ADDED:
                $msg .= $params['path']->get($params['parent']) .' has been modified by '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=show&id='. $params['parent']->getId() ."\n";
                $msg .= "\nAdded:";
                $msg .= "\n   ". $params['item']->getTitle();
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