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
    public $notification_id;
    public $all_updates;
    public $check_permissions;

    public $emails_to_be_notified;
    public $users_to_be_notified;
    public $ugroups_to_be_notified;

    public $has_notified;

    public function __construct(
        Tracker_GlobalNotification $notification,
        array $emails_to_be_notified,
        array $users_to_be_notified,
        array $ugroups_to_be_notified
    ) {
        $this->notification_id   = $notification->getId();
        $this->all_updates       = $notification->isAllUpdates();
        $this->check_permissions = $notification->isCheckPermissions();

        $this->users_to_be_notified   = $users_to_be_notified;
        $this->ugroups_to_be_notified = $ugroups_to_be_notified;
        $this->emails_to_be_notified  = $emails_to_be_notified;

        $this->has_notified = count($this->emails_to_be_notified) > 0
            || count($this->users_to_be_notified) > 0
            || count($this->ugroups_to_be_notified) > 0;
    }
}
