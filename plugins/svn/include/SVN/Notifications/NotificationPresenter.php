<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\SVN\Notifications;

use Tuleap\SVN\Admin\MailNotification;

final class NotificationPresenter
{
    /**
     * @var int
     */
    public $notification_id;
    /**
     * @var string
     */
    public $path;

    /**
     * @var array
     */
    public $emails_to_be_notified;
    /**
     * @var array
     */
    public $ugroups_to_be_notified;
    /**
     * @var array
     */
    public $users_to_be_notified;
    /**
     * @var string
     */
    public $users_to_be_notified_json;
    /**
     * @var string
     */
    public $ugroups_to_be_notified_json;
    /**
     * @var string
     */
    public $emails_to_be_notified_json;

    public function __construct(
        MailNotification $notification,
        array $emails_to_be_notified,
        array $users_to_be_notified,
        array $ugroups_to_be_notified,
        $emails_to_be_notified_json,
        $users_to_be_notified_json,
        $ugroups_to_be_notified_json,
    ) {
        $this->notification_id = $notification->getId();
        $this->path            = $notification->getPath();

        $this->users_to_be_notified   = $users_to_be_notified;
        $this->ugroups_to_be_notified = $ugroups_to_be_notified;
        $this->emails_to_be_notified  = $emails_to_be_notified;

        $this->emails_to_be_notified_json  = $emails_to_be_notified_json;
        $this->users_to_be_notified_json   = $users_to_be_notified_json;
        $this->ugroups_to_be_notified_json = $ugroups_to_be_notified_json;
    }
}
