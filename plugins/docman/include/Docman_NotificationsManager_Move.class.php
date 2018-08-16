<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class Docman_NotificationsManager_Move extends Docman_NotificationsManager {

    const MESSAGE_MOVED      = 'moved';      // X has been moved from to
    const MESSAGE_MOVED_FROM = 'moved_from'; // X has been moved from
    const MESSAGE_MOVED_TO   = 'moved_to';   // X has been moved to

    function somethingHappen($event, $params) {
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

    var $do_not_send_notifications_to;
    function _buildMessagesForUsers(&$users, $type, $params) {
        if ($users) {
            $um = $this->_getUserManager();
            while($users->valid()) {
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
    function _buildMessage($params, $user, $type) {
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
    function _getMessageForUser($user, $message_type, $params) {
        $msg = '';
        $dpm = $this->_getPermissionsManager();
        switch($message_type) {
            case self::MESSAGE_MOVED:
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_mail_body_begin',
                                                                array($params['item']->getTitle(),
                                                                            $user->getRealName(),
                                                                            $this->_url,
                                                                            $params['parent']->getId()));
                $msg .=" ";
                $need_sep = false;
                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_from', array($params['path']->get($params['old_parent'])));
                    $need_sep = true;
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    if ($need_sep) {
                        $msg .= "\n        ";
                    }
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_to', array($params['path']->get($params['parent'])));
                }
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
                 $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_mail_body_end',
                                                                array( $this->_url,
                                                                             $monitoredItem->getId()));
                 break;
            case self::MESSAGE_MOVED_FROM:
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_from_mail_body_begin',
                                                                array($params['path']->get($params['old_parent']),
                                                                            $user->getRealName(),
                                                                            $this->_url,
                                                                            $params['parent']->getId(),
                                                                            $params['item']->getTitle()));
                $msg .=" ";

                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_from', array($params['path']->get($params['old_parent'])));
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_to', array($params['path']->get($params['parent'])));
                }
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['old_parent']);
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_mail_body_end',
                                                                array( $this->_url,
                                                                             $monitoredItem->getId()));
                break;
            case self::MESSAGE_MOVED_TO:
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_from_mail_body_begin',
                                                                array($params['path']->get($params['parent']),
                                                                            $user->getRealName(),
                                                                            $this->_url,
                                                                            $params['parent']->getId(),
                                                                            $params['item']->getTitle()));
                $msg .=" ";
                if ($dpm->userCanAccess($params['user_monitor'], $params['old_parent']->getId())) {
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_from', array($params['path']->get($params['old_parent'])));
                }
                if ($dpm->userCanAccess($params['user_monitor'], $params['parent']->getId())) {
                    $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_to', array($params['path']->get($params['parent'])));
                }
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['parent']);
                $msg .= $GLOBALS['Language']->getText('plugin_docman', 'notifications_moved_mail_body_end',
                                                                array( $this->_url,
                                                                             $monitoredItem->getId()));
                break;
            default:
                $msg .= parent::_getMessageForUser($user, $message_type, $params);
                break;
        }
        return $msg;
    }

    protected function getMessageLink($type, $params) {
        switch ($type) {
            case self::MESSAGE_MOVED:
            case self::MESSAGE_MOVED_TO:
            case self::MESSAGE_MOVED_FROM:
                $link = $this->_url . '&action=show&id=' . $params['parent']->getId();
                break;
            default:
                $link = $this->_url;
                break;
        }
        return $link;
    }
}

?>
