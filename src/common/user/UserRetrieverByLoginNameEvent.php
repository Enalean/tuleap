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

namespace Tuleap\User;

use Tuleap\Event\Dispatchable;

class UserRetrieverByLoginNameEvent implements Dispatchable
{
    const NAME = 'get_user_by_login_name';
    /**
     * @var string
     */
    private $login_name;
    /**
     * @var \PFUser|null
     */
    private $user;

    public function __construct($login_name)
    {
        $this->login_name = $login_name;
    }

    /**
     * @return string
     */
    public function getLoginName()
    {
        return $this->login_name;
    }

    /**
     * @return \PFUser|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\PFUser $user)
    {
        $this->user = $user;
    }
}
