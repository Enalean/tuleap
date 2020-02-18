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

class Docman_NotificationsManager_Delete extends Docman_NotificationsManager
{
    public const MESSAGE_REMOVED_FROM = 'removed_from'; // X has been removed from folder F
    public const MESSAGE_REMOVED      = 'removed'; // X has been removed

    public function somethingHappen($event, $params)
    {
        //search for users who monitor the item
        if ($event == 'plugin_docman_event_del') {
            $this->_storeEvents($params['item']->getId(), self::MESSAGE_REMOVED, $params);
            $this->_storeEvents($params['item']->getParentId(), self::MESSAGE_REMOVED_FROM, $params);
        }
    }
    public function sendNotifications($event, $params)
    {
        $path = $this->_getDocmanPath();
        foreach ($this->_listeners as $l) {
            if (count($l['items']) > 1) {
                //A folder and its content have been deleted
                //We receive n+1 events, n is the number of subitems
                //=> we have to send only one notification
                $last = end($l['items']);
                //Search for parent
                $p = null;
                foreach ($last['events'] as $key => $v) {
                    if (isset($last['events'][$key]['parent'])) {
                        $p =  $last['events'][$key]['parent'];
                        $t =  $last['events'][$key]['type'];
                        $u = $last['events'][$key]['user'];
                    }
                    if ($p !== null) {
                        break;
                    }
                }
                assert(isset($t));
                $this->_addMessage(
                    $l['user'],
                    $t == self::MESSAGE_REMOVED ? $last['item']->getTitle() : $p->getTitle(),
                    $this->_getMessageForUser(
                        $u,
                        $t,
                        array('path' => &$path, 'parent' => &$p, 'item' => &$last['item'])
                    ),
                    $this->getMessageLink($t, array('path' => &$path, 'parent' => &$p, 'item' => &$last['item']))
                );
            } else {
                $i = array_pop($l['items']);
                $params = array(
                    'item' => $i['item'],
                    'path' => &$path
                );
                if (count($i['events']) > 1) {
                    // A folder A has a subitem B
                    // User U monitor A and B
                    // If I delete B
                    // There are two notifications :
                    // - B has been removed from A
                    // - A/B has been removed
                    // We keep only the second notifications
                    $found = false;
                    foreach ($i['events'] as $v) {
                        $found = $v['type'] == self::MESSAGE_REMOVED;
                        if ($found) {
                            break;
                        }
                    }
                    if ($found) {
                        $e = $v;
                        $title = $e['parent']->getTitle();
                    } else {
                        trigger_error('Program Error, _REMOVED not found in notifications.');
                    }
                } else {
                    $e = end($i['events']);
                    if ($e['type'] == self::MESSAGE_REMOVED_FROM) {
                        $title = $e['parent']->getTitle();
                    } else {
                        $title = $i['item']->getTitle();
                    }
                }
                $this->_addMessage(
                    $l['user'],
                    $title,
                    $this->_getMessageForUser(
                        $e['user'],
                        $e['type'],
                        array_merge($e, $params)
                    ),
                    $this->getMessageLink($e['type'], array_merge($e, $params))
                );
            }
        }
        if (count($this->_listeners)) {
            parent::sendNotifications($event, $params);
        }
    }
    public function _getMessageForUser($user, $message_type, $params)
    {
        $msg = '';
        switch ($message_type) {
            case self::MESSAGE_REMOVED:
                $msg = sprintf(
                    dgettext('tuleap-docman', "%s has been removed by %s."),
                    $params['path']->get($params['item']),
                    $user->getRealName()
                ) . "\n";

                $msg .= dgettext(
                    'tuleap-docman',
                    "You are receiving this message because you are monitoring this item."
                );
                $msg .=  "\n" . $this->getUrlProvider()->getPluginLinkUrl();
                break;
            case self::MESSAGE_REMOVED_FROM:
                $monitoredItem = $this->_getMonitoredItemForUser($user, $params['parent']);
                $msg           = sprintf(
                    dgettext('tuleap-docman', "%s has been modified by %s."),
                    $params['path']->get($params['parent']),
                    $user->getRealName()
                );

                $msg .= "\n" . $this->getMessageLink($message_type, $params) . "\n\n";
                $msg .= dgettext('tuleap-docman', "Removed:");
                $msg .= "\n";
                $msg .= $params['item']->getTitle();

                $msg .= $this->getMonitoringInformation($monitoredItem);
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
            case self::MESSAGE_REMOVED_FROM:
                $link = $this->getUrlProvider()->getShowLinkUrl($params['parent']);
                break;
            default:
                $link = $this->getUrlProvider()->getPluginLinkUrl();
        }
        return $link;
    }

    public function _storeEvents($id, $message_type, $params)
    {
        $dpm   = $this->_getPermissionsManager();
        $users = $this->notified_people_retriever->getNotifiedUsers($this->project, $id);
        while ($users->valid()) {
            $row  = $users->current();
            if (!isset($this->_listeners[$row['user_id']])) {
                $um   = $this->_getUserManager();
                $user = $um->getUserById($row['user_id']);
                if ($user && $dpm->userCanRead($user, $params['item']->getId()) && $dpm->userCanAccess($user, $params['item']->getParentId()) && $dpm->userCanAccess($user, $row['item_id'])) {
                    $this->_listeners[$user->getId()] = array(
                        'user'  => $user,
                        'items' => array()
                    );
                }
            }
            if (isset($this->_listeners[$row['user_id']])) {
                if (!isset($this->_listeners[$row['user_id']]['items'][$params['item']->getId()])) {
                    $this->_listeners[$row['user_id']]['items'][$params['item']->getId()] = array(
                        'item'   => &$params['item'],
                        'events' => array()
                    );
                }
                $event = array(
                    'type' => $message_type,
                    'user' => &$params['user']
                );
                if (isset($params['parent'])) {
                    $event['parent'] = $params['parent'];
                }
                $this->_listeners[$row['user_id']]['items'][$params['item']->getId()]['events'][] = $event;
            }
            $users->next();
        }
    }
}
