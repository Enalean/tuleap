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

require_once('Docman_NotificationsManager.class.php');
require_once('Docman_Path.class.php');

class Docman_NotificationsManager_Add extends Docman_NotificationsManager { 

    const MESSAGE_ADDED = 'added'; // X has been added
    
    function __construct($group_id, $url, $feedback) {
        parent::__construct($group_id, $url, $feedback);
    }
    function _getListeningUsersItemId($params) {
        return $params['parent']->getId();
    }
    function _buildMessage($event, $params, $user) {
        switch($event) {
            case 'plugin_docman_event_add':
                $parent = $this->_item_factory->getItemFromDb($params['item']->getParentId());
                $this->_addMessage(
                    $user, 
                    $parent->getTitle(), 
                    $this->_getMessageForUser(
                        $params['user'], 
                        self::MESSAGE_ADDED, 
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
            case self::MESSAGE_ADDED:
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['parent']);
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_added_mail_body', array($params['path']->get($params['parent']), 
                                                              $user->getRealName(),
                                                              $this->_url,
                                                              $params['parent']->getId(),
                                                              $params['item']->getTitle(),
                                                              $monitoredItem->getId()));
                break;
            default:
                $msg .= parent::_getMessageForUser($user, $message_type, $params);
                break;
        }
        return $msg;
    }
}
?>