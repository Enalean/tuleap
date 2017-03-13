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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html

function aGlobalNotification()
{
    return new \Tracker_GlobalNotification_Builder();
}

class Tracker_GlobalNotification_Builder
{
    private $name;
    private $id;
    private $tracker_id;
    private $addresses;
    private $all_updates;
    private $check_permissions;

    public function __construct()
    {
        $this->name = 'Tracker_GlobalNotification';
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withTrackerId($tracker_id)
    {
        $this->tracker_id = $tracker_id;
        return $this;
    }

    public function withAddresses($addresses)
    {
        $this->addresses = $addresses;
        return $this;
    }

    public function withAllUpdates($all_updates)
    {
        $this->all_updates = $all_updates;
        return $this;
    }

    public function withCheckPermissions($check_permissions)
    {
        $this->check_permissions = $check_permissions;
        return $this;
    }

    /** @return Tracker_GlobalNotification */
    public function build()
    {
        $klass  = $this->name;
        $object = new $klass(
            $this->id,
            $this->tracker_id,
            $this->addresses,
            $this->all_updates,
            $this->check_permissions
        );
        return $object;
    }
}
