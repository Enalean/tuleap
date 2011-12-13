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
        $um = parent::_getUserManager();
        $users = new ArrayIterator($params['listeners']);
        if ($users) {
            while($users->valid()) {
                $user    = $users->current();
                if ($user->isActive() || $user->isRestricted()) {
                    $this->_buildMessage($params['event'], $params, $user);
                }
                $users->next();
            }
        }
    }

    function _buildMessage($event, $params, $user) {
        $type = '';
        $language = parent::_getLanguageForUser($user);
        switch($event) {
            case 'plugin_docman_add_monitoring':
                $type = self::MESSAGE_ADDED;
                $subject = $language->getText('plugin_docman', 'notifications_added_to_monitoring_list_subject', array($params['item']->getTitle()));
                break;
            case 'plugin_docman_remove_monitoring':
                $type = self::MESSAGE_REMOVED;
                $subject = $language->getText('plugin_docman', 'notifications_removed_from_monitoring_list_subject', array($params['item']->getTitle()));
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
        $language = $this->_getLanguageForUser($user);
        $msg .= "\n\n--------------------------------------------------------------------\n";
        switch($message_type) {
            case self::MESSAGE_ADDED:
                $msg .= $language->getText('plugin_docman', 'notifications_added_to_monitoring_list')."\n";
                $msg .= $language->getText('plugin_docman', 'notif_footer_message_link')."\n";
                break;
            case self::MESSAGE_REMOVED:
                $msg .= $language->getText('plugin_docman', 'notifications_removed_from_monitoring_list')."\n";
                $msg .= $language->getText('plugin_docman', 'notif_footer_message_restore_link')."\n";
                break;
            default:
                $msg .= $language->getText('plugin_docman', 'notif_something_happen')."\n";
                break;
        }
        $msg .= $this->_url .'&action=details&section=notifications&id='. $params['item']->getId();
        return $msg;
    }
}

?>