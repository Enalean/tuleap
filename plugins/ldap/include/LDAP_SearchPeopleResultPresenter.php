<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class LDAP_SearchPeopleResultPresenter
{

    /** @var  string */
    private $user_name;

    /** @var  string */
    private $real_name;

    /** @var  string */
    private $avatar;

    /** @var string */
    private $directory_uri;

    public function __construct($real_name, $avatar, $directory_uri, $user_name = null)
    {
        $this->real_name     = $real_name;
        $this->avatar        = $avatar;
        $this->directory_uri = $directory_uri;
        $this->user_name     = $user_name;
    }

    public function user_name()
    {
        return $this->user_name;
    }

    public function real_name()
    {
        return $this->real_name;
    }

    public function is_local()
    {
        return $this->user_name !== null;
    }

    public function user_uri()
    {
        return '/users/' . $this->user_name;
    }

    public function has_directory_uri()
    {
        return $this->directory_uri != '';
    }

    public function directory_uri()
    {
        return $this->directory_uri;
    }

    public function avatar()
    {
        return $this->avatar;
    }
}
