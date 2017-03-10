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

class GlobalNotificationsEmailRetriever
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;

    public function __construct(UsersToNotifyDao $user_dao)
    {
        $this->user_dao = $user_dao;
    }

    /**
     * @return string[]
     */
    public function getNotifiedEmails(Tracker_GlobalNotification $notification)
    {
        $emails = $this->transformNotificationAddressesStringAsArray($notification);
        foreach ($this->user_dao->searchUsersByNotificationId($notification->getId()) as $row) {
            $emails[] = $row['email'];
        }

        return array_unique($emails);
    }

    /**
     * @return string[]
     */
    private function transformNotificationAddressesStringAsArray(Tracker_GlobalNotification $notification)
    {
        $addresses = $notification->getAddresses();
        $addresses = preg_split('/[,;]/', $addresses);
        $addresses = array_map('trim', $addresses);
        return $addresses;
    }
}
