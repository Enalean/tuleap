<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tracker_GlobalNotification;

class PaneNotificationPresenter
{
    public $users_to_be_notified;
    public $notification_id;
    public $list_of_mails;
    public $has_notified;
    public $check_permissions;
    public $all_updates;

    public function __construct(
        Tracker_GlobalNotification $notification,
        array $users_to_be_notified
    ) {
        $this->notification_id          = $notification->getId();
        $this->all_updates              = $notification->isAllUpdates();
        $this->check_permissions        = $notification->isCheckPermissions();
        $this->users_to_be_notified     = $users_to_be_notified;
        $this->list_of_mails            = $notification->getAddresses();
        $this->has_notified             = count($this->list_of_mails) > 0 || count($this->users_to_be_notified) > 0;
        $this->has_users_to_be_notified = count($users_to_be_notified) > 0;
    }
}
