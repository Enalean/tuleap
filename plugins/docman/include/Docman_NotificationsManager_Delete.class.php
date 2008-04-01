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
 * 
 */
require_once('Docman_NotificationsManager.class.php');
require_once('Docman_Path.class.php');
class Docman_NotificationsManager_Delete extends Docman_NotificationsManager { 

    var $MESSAGE_REMOVED_FROM = 'removed_from'; // X has been removed from folder F
    var $MESSAGE_REMOVED      = 'removed'; // X has been removed
    
    function Docman_NotificationsManager_Delete($group_id, $url, &$feedback) {
        parent::Docman_NotificationsManager($group_id, $url, $feedback);
    }
    function somethingHappen($event, $params) {
        //search for users who monitor the item
        if ($event == PLUGIN_DOCMAN_EVENT_DEL) {
            $this->_storeEvents($params['item']->getId(), $this->MESSAGE_REMOVED, $params);
            $this->_storeEvents($params['item']->getParentId(), $this->MESSAGE_REMOVED_FROM, $params);
        }
    }
    function sendNotifications($event, $params) {
        $path =& new Docman_Path();
        foreach($this->_listeners as $l) {
            if (count($l['items']) > 1) {
                //A folder and its content have been deleted
                //We receive n+1 events, n is the number of subitems
                //=> we have to send only one notification
                $last = end($l['items']);
                //Search for parent
                $p = null;
                while(!$p && (list($k,) = each($last['events']))) {
                    if (isset($last['events'][$k]['parent'])) {
                        $p =  $last['events'][$k]['parent'];
                        $t =  $last['events'][$k]['type'];
                        $u =& $last['events'][$k]['user'];
                    }
                }
                $this->_addMessage(
                    $l['user'],
                    $t == $this->MESSAGE_REMOVED ? $last['item']->getTitle() : $p->getTitle(),
                    $this->_getMessageForUser($u, $t, array('path' => &$path, 'parent' => &$p, 'item' => &$last['item'])),
                    $p
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
                    reset($i['events']);
                    while (!$found && (list($k,$v) = each($i['events']))) {
                        $found = $v['type'] == $this->MESSAGE_REMOVED;
                    }
                    if ($found) {
                        $e = $v;
                        $title = $e['parent']->getTitle();
                    } else {
                        trigger_error('Program Error, _REMOVED not found in notifications.');
                    }
                } else {
                    $e = end($i['events']);
                    if ($e['type'] == $this->MESSAGE_REMOVED_FROM) {
                        $title = $e['parent']->getTitle();
                    } else {
                        $title = $i['item']->getTitle();
                    }
                }
                $this->_addMessage(
                    $l['user'],
                    $title,
                    $this->_getMessageForUser($e['user'], $e['type'], array_merge($e, $params)),
                    $e['parent']
                );
            }
        }
        if (count($this->_listeners)) {
            parent::sendNotifications($event, $params);
        }
    }
    function _getMessageForUser(&$user, $message_type, $params) {
        $msg = '';
        switch($message_type) {
            case $this->MESSAGE_REMOVED:
                $msg .= $params['path']->get($params['item']) .' has been removed by '. $user->getRealName() .'.';
                $msg .= "\n\n---------------------------------------------------------------------\n";
                $msg .= "You are receiving this message because you were monitoring this item.\n";
                $msg .= $this->_url;
                break;
            case $this->MESSAGE_REMOVED_FROM:
                $msg .= $params['path']->get($params['parent']) .' has been modified by '. $user->getRealName() .".\n";
                $msg .= $this->_url .'&action=show&id='. $params['parent']->getId() ."\n";
                $msg .= "\nRemoved:";
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
    function _storeEvents($id, $message_type, $params) {
        $dpm =& Docman_PermissionsManager::instance($this->_group_id);
        $users = $this->_getListeningUsers($id);
        while($users->valid()) {
            $row  = $users->current();
            if (!isset($this->_listeners[$row['user_id']])) {
                $um =& UserManager::instance();
                $user =& $um->getuserById($row['user_id']);
                if ($user && $dpm->userCanRead($user, $params['item']->getId()) && $dpm->userCanAccess($user, $params['item']->getParentId()) && $dpm->userCanAccess($user, $row['object_id'])) {
                    $this->_listeners[$user->getId()] = array(
                        'user'   => &$user,
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
                    $event['parent'] =& $params['parent'];
                }
                $this->_listeners[$row['user_id']]['items'][$params['item']->getId()]['events'][] = $event;
            }
            $users->next();
        }
    }
}
?>