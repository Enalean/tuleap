<?php
/**
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_NotificationsManager.class.php');

class Docman_NotificationsManager_Subscribers extends Docman_NotificationsManager {

    const MESSAGE_ADDED = 'added'; // X has been added to monitoring list
    const MESSAGE_REMOVED = 'removed'; // X has been removed from monitoring list

    function __construct($group_id, $url, $feedback) {
        parent::__construct($group_id, $url, $feedback);
    }

    function somethingHappen($event, $params) {
        $um = $this->_getUserManager();
        $users = new ArrayIterator($params['listeners']);
        if ($users) {
            while($users->valid()) {
                $u    = $users->current();
                $user = $u;
                $this->_buildMessage($params['event'], $params, $user);
                $users->next();
            }
        }
    }

    function _buildMessage($event, $params, $user) {
        $type = '';
        switch($event) {
            case 'plugin_docman_add_monitoring':
                $type = self::MESSAGE_ADDED;
                $subject = 'You were added to '.$params['item']->getTitle().' monitoring list';
                break;
            case 'plugin_docman_remove_monitoring':
                $type = self::MESSAGE_REMOVED;
                $subject = 'You were removed to '.$params['item']->getTitle().' monitoring list';
                break;
            default:
                $subject = $params['item']->getTitle();
                break;
        }
        $this->_addMessage($user,
        $subject,
        $this->_getMessageForUser($user, $type, $params)
        );
    }

    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        $msg .= "\n\n--------------------------------------------------------------------\n";
        $msg .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.
         Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor. 
        Cras elementum ultrices diam.";
        $msg .= $language->getText('plugin_docman', 'notif_footer_message_link')."\n";
        $msg .= $this->_url .'&action=details&section=notifications&id='. $params['item']->getId();
        return $msg;
    }

    function _getUserManager() {
        return UserManager::instance();
    }
    function _getPermissionsManager() {
        return Docman_PermissionsManager::instance($this->_group_id);
    }
    function _getMail() {
        return new Mail();
    }
}

?>