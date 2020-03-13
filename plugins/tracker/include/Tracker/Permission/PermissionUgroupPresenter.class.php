<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Permission_PermissionUgroupPresenter
{

    private $id;
    private $name;
    private $url;
    private $permissions_list = array();

    public function __construct($id, $name, $url, array $permissions_list)
    {
        $this->id               = $id;
        $this->name             = $name;
        $this->url              = $url;
        $this->permissions_list = $permissions_list;
    }

    public function has_link()
    {
        return $this->url != '';
    }

    public function name()
    {
        return $this->name;
    }

    public function url()
    {
        return $this->url;
    }

    public function permission_name()
    {
        return Tracker_Permission_Command::PERMISSION_PREFIX . $this->id;
    }

    public function permissions_list()
    {
        return $this->permissions_list;
    }
}
