<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Enalean\LicenseManager\Webhook;

use HTTPRequest;
use Tuleap\Admin\Homepage\UserCounterDao;
use Tuleap\Webhook\Payload;

class UserCounterPayload implements Payload
{
    private $payload;

    public function __construct(HTTPRequest $request, UserCounterDao $dao, $max_users, $event, $user_id)
    {
        $this->payload = array(
            'event'     => $event,
            'url'       => $request->getServerUrl(),
            'users'     => array(),
            'max_users' => $max_users,
            'user_id'   => $user_id
        );

        $dao = new UserCounterDao();
        foreach ($dao->getNbOfUsersByStatus() as $row) {
            $this->payload['users'][$row['status']] = $row['nb'];
        }
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
