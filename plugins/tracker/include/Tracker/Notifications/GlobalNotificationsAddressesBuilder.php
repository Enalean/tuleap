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

use PFUser;

class GlobalNotificationsAddressesBuilder
{
    public function removeAddressFromString($addresses, PFUser $user)
    {
        $addresses = $this->transformNotificationAddressesStringAsArray($addresses);
        $addresses = array_diff($addresses, [$user->getEmail(), $user->getUserName()]);

        return implode(",", $addresses);
    }

    /**
     * @return string[]
     */
    public function transformNotificationAddressesStringAsArray($addresses)
    {
        $addresses = preg_split('/[,;]/', $addresses);
        $addresses = array_filter(array_map('trim', $addresses));

        return $addresses;
    }

    /**
     * @return string
     */
    public function transformNotificationAddressesArrayAsString($addresses)
    {
        $addresses = implode(',', $addresses);

        return $addresses;
    }
}
