<?php
/**
 * Copyright (c) Enalean, 2011 - present. All Rights Reserved.
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

class Docman_NotificationsManager_Subscribers extends Docman_NotificationsManager
{

    public const MESSAGE_ADDED = 'added'; // X has been added to monitoring list
    public const MESSAGE_REMOVED = 'removed'; // X has been removed from monitoring list

    /**
     * Trigger notification message build for a list of users monitoring a given docman item.
     *
     * @param String $event  Event listened at Docman_Controller side
     * @param Array  $params Array of params from which we can retrive an array of listners and the event type
     *
     * @return void
     */
    public function somethingHappen($event, $params)
    {
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
    public function _buildMessage($event, $params, $user)
    {
        $type = '';
        switch ($event) {
            case 'plugin_docman_add_monitoring':
                $type = self::MESSAGE_ADDED;
                $subject = sprintf(
                    dgettext(
                        'tuleap-docman',
                        "You were added to '%s' monitoring list"
                    ),
                    $params['item']->getTitle()
                );
                break;
            case 'plugin_docman_remove_monitoring':
                $type = self::MESSAGE_REMOVED;
                $subject = sprintf(
                    dgettext(
                        'tuleap-docman',
                        "You were removed from '%s' monitoring list"
                    ),
                    $params['item']->getTitle()
                );
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
    public function _getMessageForUser($user, $message_type, $params)
    {
        $msg = '';
        $separator = "\n\n--------------------------------------------------------------------\n";
        $itemUrl = $this->getMessageLink($message_type, $params);
        switch ($message_type) {
            case self::MESSAGE_ADDED:
                $msg .= dgettext(
                    'tuleap-docman',
                    "You are receiving this message because you were added to the monitoring list of this item:"
                ) . "\n";
                $msg .= $itemUrl;
                $msg .= $separator;
                $msg .= dgettext('tuleap-docman', 'To stop monitoring, please visit:') . "\n";
                break;
            case self::MESSAGE_REMOVED:
                $msg .= dgettext(
                    'tuleap-docman',
                    "You are receiving this message because you were removed from the monitoring list of this item:"
                ) . "\n";
                $msg .= $itemUrl;
                $msg .= $separator;
                $msg .= dgettext('tuleap-docman', 'To restore monitoring, please visit:') . "\n";
                break;
            default:
                $msg .= dgettext('tuleap-docman', 'Something happen!') . "\n";
                break;
        }
        $msg .= $this->getUrlProvider()->getNotificationLinkUrl($params['item']);
        return $msg;
    }

    protected function getMessageLink($type, $params)
    {
        switch ($type) {
            case self::MESSAGE_ADDED:
            case self::MESSAGE_REMOVED:
                $link = $this->getUrlProvider()->getShowLinkUrl($params['item']);
                break;
            default:
                $link = $this->getUrlProvider()->getPluginLinkUrl();
                break;
        }
        return $link;
    }
}
