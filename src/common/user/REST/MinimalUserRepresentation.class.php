<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\User\REST;

use \PFUser;
use \Tuleap\REST\JsonCast;

class MinimalUserRepresentation {

    const ROUTE = 'users';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var String {@type string}
     */
    public $real_name;

    /**
     * @var String {@type string}
     */
    public $username;

    /**
     * @var String {@type string}
     */
    public $ldap_id;

    /**
     * @var string {@type string}
     */
    public $avatar_url;

    public function build(PFUser $user) {
        $this->id         = JsonCast::toInt($user->getId());
        $this->uri        = UserRepresentation::ROUTE . '/' . $this->id;
        $this->real_name  = $user->getRealName();
        $this->username   = $user->getUserName();
        $this->ldap_id    = $user->getLdapId();
        $this->avatar_url = $user->getAvatarUrl();

        return $this;
    }
}