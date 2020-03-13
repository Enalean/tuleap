<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('Docman_NotificationsManager.class.php');
require_once('Docman_Path.class.php');

class Docman_NotificationsManager_Add extends Docman_NotificationsManager
{

    public const MESSAGE_ADDED = 'added'; // X has been added

    public function _getListeningUsersItemId($params)
    {
        return $params['parent']->getId();
    }
    public function _buildMessage($event, $params, $user)
    {
        switch ($event) {
            case 'plugin_docman_event_add':
                $parent = $this->_item_factory->getItemFromDb($params['item']->getParentId());
                $this->_addMessage(
                    $user,
                    $parent->getTitle(),
                    $this->_getMessageForUser(
                        $params['user'],
                        self::MESSAGE_ADDED,
                        $params
                    ),
                    $this->getMessageLink(self::MESSAGE_ADDED, $params)
                );
                break;
            default:
                break;
        }
    }
    public function _getMessageForUser($user, $message_type, $params)
    {
        $msg = '';
        switch ($message_type) {
            case self::MESSAGE_ADDED:
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['parent']);

                $msg = sprintf(
                    dgettext('tuleap-docman', "%s has been modified by %s."),
                    $params['path']->get($params['parent']),
                    $user->getRealName()
                ) . "\n";

                $msg .= $this->getMessageLink($message_type, $params) . "\n\n";
                $msg .= dgettext('tuleap-docman', "Added:");
                $msg .= "\n" . $params['item']->getTitle();

                $msg           .= $this->getMonitoringInformation($monitoredItem);
                break;
            default:
                $msg .= parent::_getMessageForUser($user, $message_type, $params);
                break;
        }
        return $msg;
    }

    protected function getMessageLink($type, $params)
    {
        switch ($type) {
            case self::MESSAGE_ADDED:
                $link = $this->getUrlProvider()->getShowLinkUrl($params['parent']);
                break;
            default:
                $link =  $this->getUrlProvider()->getPluginLinkUrl();
                break;
        }
        return $link;
    }
}
