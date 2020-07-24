<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
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

use PFUser;

class NotificationsEmailsBuilder
{
    public function removeAddressFromString($emails, PFUser $user)
    {
        $emails = $this->transformNotificationEmailsStringAsArray($emails);
        $emails = array_diff($emails, [$user->getEmail(), $user->getUserName()]);

        return implode(",", $emails);
    }

    /**
     * @return string[]
     */
    public function transformNotificationEmailsStringAsArray($emails)
    {
        $emails = preg_split('/[,;]/', $emails);
        $emails = array_filter(array_map('trim', $emails));

        return $emails;
    }
}
