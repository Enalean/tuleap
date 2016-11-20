<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\SystemEvent;

use CSRFSynchronizerToken;

class NotificationsPresenter
{
    public $title;
    public $events_label;
    public $notifications_label;
    public $add_notification_label;
    public $emails_label;
    public $listen_label;
    public $edit_label;
    public $delete_label;
    public $csrf;
    public $delete_notif_label;
    public $edit_notif_label;
    public $save_notif_label;
    public $notified_label;
    public $delete_confirm;
    public $status_to_listen;
    public $emails_instructions;

    public function __construct($title, array $notifications, CSRFSynchronizerToken $csrf)
    {
        $this->title         = $title;
        $this->notifications = $notifications;
        $this->csrf          = $csrf;

        $this->events_label        = $GLOBALS['Language']->getText('admin_system_events', 'events');
        $this->notifications_label = $GLOBALS['Language']->getText('admin_system_events', 'notifications');

        $this->add_notification_label = $GLOBALS['Language']->getText('admin_system_events', 'add_notification');
        $this->emails_label           = $GLOBALS['Language']->getText('admin_system_events', 'emails');
        $this->listen_label           = $GLOBALS['Language']->getText('admin_system_events', 'listen');
        $this->edit_label             = $GLOBALS['Language']->getText('admin_system_events', 'edit');
        $this->delete_label           = $GLOBALS['Language']->getText('admin_system_events', 'delete');
        $this->delete_confirm         = $GLOBALS['Language']->getText('admin_system_events', 'delete_confirm');
        $this->delete_notif_label     = $GLOBALS['Language']->getText('admin_system_events', 'delete_notif_label');
        $this->edit_notif_label       = $GLOBALS['Language']->getText('admin_system_events', 'edit_notif_label');
        $this->save_notif_label       = $GLOBALS['Language']->getText('admin_system_events', 'save_notif_label');
        $this->empty_state            = $GLOBALS['Language']->getText('admin_system_events', 'notifs_empty');
        $this->notified_label         = $GLOBALS['Language']->getText('admin_system_events', 'notified_label');
        $this->status_to_listen       = $GLOBALS['Language']->getText('admin_system_events', 'status_to_listen');
        $this->emails_instructions    = $GLOBALS['Language']->getText('admin_system_events', 'emails_instructions');

        $this->cancel = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
