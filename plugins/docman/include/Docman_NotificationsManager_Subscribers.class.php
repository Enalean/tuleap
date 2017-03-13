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

    /**
     * Trigger notification message build for a list of users monitoring a given docman item.
     *
     * @param String $event  Event listened at Docman_Controller side
     * @param Array  $params Array of params from which we can retrive an array of listners and the event type
     *
     * @return void
     */
    function somethingHappen($event, $params) {
        $um = $this->_getUserManager();
        $users = new ArrayIterator($params['listeners']);
        if ($users) {
            $dpm = $this->_getPermissionsManager();
            foreach ($users as $user) {
                if ($user->isActive() || $user->isRestricted()) {
                    if ($dpm->userCanAccess($user, $params['item']->getId())) {
                        $this->_buildMessage($params['event'], $params, $user);
                    }
                }
            }
        }
        $this->sendNotifications('', array());
    }

    /**
    * Build notification for a given user according to the type of the event on the monitoring list.
    *
    * @param String $event  type of the listened action on the monitoring list.
    * @param Array  $params Array of params from which we can retrive docman item.
    * @param PFUser   $user   User we want to notify
    *
    * @return void
    */
    function _buildMessage($event, $params, $user) {
        $type = '';
        switch($event) {
        case 'plugin_docman_add_monitoring':
            $type = self::MESSAGE_ADDED;
            $subject = $GLOBALS['Language']->getText('plugin_docman', 'notifications_added_to_monitoring_list_subject', array($params['item']->getTitle()));
            break;
        case 'plugin_docman_remove_monitoring':
            $type = self::MESSAGE_REMOVED;
            $subject = $GLOBALS['Language']->getText('plugin_docman', 'notifications_removed_from_monitoring_list_subject', array($params['item']->getTitle()));
            break;
        default:
            $subject = $params['item']->getTitle();
            break;
        }
        $this->_addMessage(
            $user,
            $subject,
            $this->_getMessageForUser($user, $type, $params),
            $this->getMessageLink($type, $params)
        );
    }

    /**
    * Prepare notification message
    *
    * @param PFUser   $user         User we want to notify.
    * @param String $message_type Nature of the operation on the monitoring list.
    * @param Array  $params       Array of params from which we can retrive docman item.
    *
    * @return String
    */
    function _getMessageForUser($user, $message_type, $params) {
        $msg = '';
        $separator = "\n\n--------------------------------------------------------------------\n";
        $itemUrl = $this->getMessageLink($message_type, $params);
        switch($message_type) {
        case self::MESSAGE_ADDED:
            $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_added_to_monitoring_list')."\n";
            $msg .= $itemUrl;
            $msg .= $separator;
            $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_footer_message_link')."\n";
            break;
        case self::MESSAGE_REMOVED:
            $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_removed_from_monitoring_list')."\n";
            $msg .= $itemUrl;
            $msg .= $separator;
            $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_footer_message_restore_link')."\n";
            break;
        default:
            $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notif_something_happen')."\n";
            break;
        }
        $msg .= $this->_url .'&action=details&section=notifications&id='. $params['item']->getId();
        return $msg;
    }

    protected function getMessageLink($type, $params) {
        switch ($type) {
            case self::MESSAGE_ADDED:
            case self::MESSAGE_REMOVED:
                $link = $this->_url .'&action=show&id='. $params['item']->getId();
                break;
            default:
                $link = $this->_url;
                break;
        }
        return $link;
    }
}

?>
