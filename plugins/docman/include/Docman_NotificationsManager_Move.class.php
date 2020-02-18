<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

class Docman_NotificationsManager_Move extends Docman_NotificationsManager
{

    public const MESSAGE_MOVED      = 'moved';      // X has been moved from to
    public const MESSAGE_MOVED_FROM = 'moved_from'; // X has been moved from
    public const MESSAGE_MOVED_TO   = 'moved_to';   // X has been moved to

    public function somethingHappen($event, $params)
    {
        if ($event == 'plugin_docman_event_move') {
            if ($params['item']->getParentId() != $params['parent']->getId()) {
                $params['path'] = $this->_getDocmanPath();
                $users = $this->notified_people_retriever->getNotifiedUsers($this->project, $params['item']->getId());
                $this->_buildMessagesForUsers($users, self::MESSAGE_MOVED, $params);
                $users = $this->notified_people_retriever->getNotifiedUsers($this->project, $params['parent']->getId());
                $this->_buildMessagesForUsers($users, self::MESSAGE_MOVED_TO, $params);
                $users = $this->notified_people_retriever->getNotifiedUsers($this->project, $params['item']->getParentId());
                $this->_buildMessagesForUsers($users, self::MESSAGE_MOVED_FROM, $params);
            }
        }
    }

    public $do_not_send_notifications_to;

    public function _buildMessagesForUsers(&$users, $type, $params)
    {
        if ($users) {
            $um = $this->_getUserManager();
            while ($users->valid()) {
                $u    = $users->current();
                $user = $um->getUserById($u['user_id']);
                $dpm  = $this->_getPermissionsManager();
                if ($dpm->userCanRead($user, $params['item']->getId()) && ($dpm->userCanAccess($user, $params['parent']->getId()) || $dpm->userCanAccess($user, $params['item']->getParentId())) && ($u['item_id'] == $params['item']->getId() || $dpm->userCanAccess($user, $u['item_id']))) {
                    if (!isset($this->do_not_send_notifications_to[$user->getId()])) {
                        $this->_buildMessage(array_merge($params, array('user_monitor' => &$user)), $user, $type);
                        $this->do_not_send_notifications_to[$user->getId()] = true;
                    }
                }
                $users->next();
            }
        }
    }

    public function _buildMessage($params, $user, $type)
    {
        $params['old_parent'] = $this->_item_factory->getItemFromDb($params['item']->getParentId());
        $this->_addMessage(
            $user,
            $type == self::MESSAGE_MOVED ? $params['item']->getTitle() : ($type == self::MESSAGE_MOVED_FROM ? $params['old_parent']->getTitle() : $params['parent']->getTitle()),
            $this->_getMessageForUser(
                $params['user'],
                $type,
                $params
            ),
            $this->getMessageLink($type, $params)
        );
    }
    public function _getMessageForUser($user, $message_type, $params)
    {
        $msg = '';
        switch ($message_type) {
            case self::MESSAGE_MOVED:
                $msg = sprintf(
                    dgettext('tuleap-docman', "%s has been modified by %s."),
                    $params['item']->getTitle(),
                    $user->getRealName()
                );

                $msg .= "\n" . $this->getUrlProvider()->getShowLinkUrl($params['parent']) . "\n\n";
                $msg .= dgettext('tuleap-docman', "Moved");

                $msg .= " ";
                $msg .= $this->getFromToInformation($params, true);

                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
                $msg           .= $this->getMonitoringInformation($monitoredItem);
                break;
            case self::MESSAGE_MOVED_FROM:
                $msg .= sprintf(
                    dgettext('tuleap-docman', "%s has been modified by %s."),
                    $params['path']->get($params['old_parent']),
                    $user->getRealName()
                );

                $msg .= "\n" . $this->getUrlProvider()->getShowLinkUrl($params['parent']) . "\n\n";
                $msg .= dgettext('tuleap-docman', "Moved");

                $msg .= " ";
                $msg .= $this->getFromToInformation($params, false);

                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
                $msg           .= $this->getMonitoringInformation($monitoredItem);
                break;
            case self::MESSAGE_MOVED_TO:
                $msg .= sprintf(
                    dgettext('tuleap-docman', "%s has been modified by %s."),
                    $params['path']->get($params['parent']),
                    $user->getRealName()
                );
                $msg .= "\n" . $this->getUrlProvider()->getShowLinkUrl($params['parent']) . "\n\n";
                $msg .= dgettext('tuleap-docman', "Moved");

                $msg           .= " ";
                $msg           .= $this->getFromToInformation($params, false);
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
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
            case self::MESSAGE_MOVED:
            case self::MESSAGE_MOVED_TO:
            case self::MESSAGE_MOVED_FROM:
                $link = $this->getUrlProvider()->getShowLinkUrl($params['parent']);
                break;
            default:
                $link = $this->getUrlProvider()->getPluginLinkUrl();
                break;
        }
        return $link;
    }

    protected function getFromToInformation(array $params, bool $need_sep): string
    {
        $msg                = '';
        $permission_manager = $this->_getPermissionsManager();
        if ($permission_manager->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
            $msg .= sprintf(
                dgettext('tuleap-docman', "from:\n %s"),
                $params['path']->get($params['old_parent'])
            );
        } else {
            $need_sep = false;
        }
        if ($permission_manager->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
            if ($need_sep) {
                $msg .= "\n        ";
            } else {
                $msg .= " ";
            }
            $msg .= sprintf(
                dgettext('tuleap-docman', "to:\n %s"),
                $params['path']->get($params['parent'])
            );
        }

        return $msg;
    }
}
