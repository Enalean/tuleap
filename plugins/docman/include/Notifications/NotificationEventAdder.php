<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Docman_NotificationsManager;
use Docman_NotificationsManager_Add;
use Docman_NotificationsManager_Delete;
use Docman_NotificationsManager_Move;
use Docman_NotificationsManager_Subscribers;

class NotificationEventAdder
{
    /**
     * @var Docman_NotificationsManager
     */
    private $notifications_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var Docman_NotificationsManager_Add
     */
    private $notifications_manager_add;
    /**
     * @var Docman_NotificationsManager_Delete
     */
    private $notifications_manager_delete;
    /**
     * @var Docman_NotificationsManager_Move
     */
    private $notifications_manager_move;
    /**
     * @var Docman_NotificationsManager_Subscribers
     */
    private $notifications_manager_subscribers;

    public function __construct(
        \EventManager $event_manager,
        Docman_NotificationsManager $notifications_manager,
        Docman_NotificationsManager_Add $notifications_manager_add,
        Docman_NotificationsManager_Delete $notifications_manager_delete,
        Docman_NotificationsManager_Move $notifications_manager_move,
        Docman_NotificationsManager_Subscribers $notifications_manager_subscribers
    ) {
        $this->notifications_manager             = $notifications_manager;
        $this->event_manager                     = $event_manager;
        $this->notifications_manager_add         = $notifications_manager_add;
        $this->notifications_manager_delete      = $notifications_manager_delete;
        $this->notifications_manager_move        = $notifications_manager_move;
        $this->notifications_manager_subscribers = $notifications_manager_subscribers;
    }

    public function addNotificationManagement()
    {
        $this->addListenersForDocmanNotificationsManager();
        $this->addListenersForDocmanManagerAdd();
        $this->addListenersForDocmanManagerDelete();
        $this->addListenersForDocmanManagerMove();
        $this->addListenersForDocmanManagerSubscribers();
    }

    private function addListenersForDocmanNotificationsManager()
    {
        $this->event_manager->addListener(
            'plugin_docman_event_edit',
            $this->notifications_manager,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'plugin_docman_event_new_version',
            $this->notifications_manager,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'plugin_docman_event_metadata_update',
            $this->notifications_manager,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'send_notifications',
            $this->notifications_manager,
            'sendNotifications',
            true
        );
    }

    private function addListenersForDocmanManagerAdd()
    {
        $this->event_manager->addListener(
            'plugin_docman_event_add',
            $this->notifications_manager_add,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'send_notifications',
            $this->notifications_manager_add,
            'sendNotifications',
            true
        );
    }

    private function addListenersForDocmanManagerDelete()
    {
        $this->event_manager->addListener(
            'plugin_docman_event_del',
            $this->notifications_manager_delete,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'send_notifications',
            $this->notifications_manager_delete,
            'sendNotifications',
            true
        );
    }

    private function addListenersForDocmanManagerMove()
    {
        $this->event_manager->addListener(
            'plugin_docman_event_move',
            $this->notifications_manager_move,
            'somethingHappen',
            true
        );
        $this->event_manager->addListener(
            'send_notifications',
            $this->notifications_manager_move,
            'sendNotifications',
            true
        );
    }

    private function addListenersForDocmanManagerSubscribers()
    {
        $this->event_manager->addListener(
            'plugin_docman_event_subcribers',
            $this->notifications_manager_subscribers,
            'somethingHappen',
            true
        );
    }
}
