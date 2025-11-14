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

final readonly class NotificationPresenter
{
    public int $notification_id;
    public string $path;
    public string $comma_separated_emails_to_be_notified;

    /**
     * @param list<array{id: int, name: string, selected: bool}> $ugroups
     */
    public function __construct(
        MailNotification $notification,
        public array $emails_to_be_notified,
        public array $users_to_be_notified,
        public array $ugroups_to_be_notified,
        public string $users_to_be_notified_json,
        public array $ugroups,
    ) {
        $this->notification_id = $notification->getId();
        $this->path            = $notification->getPath();

        $this->comma_separated_emails_to_be_notified = implode(', ', $emails_to_be_notified);
    }
}
