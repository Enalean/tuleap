<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
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

class Tracker_GlobalNotification
{
    private $id;
    private $tracker_id;
    private $addresses;
    private $all_updates;
    private $check_permissions;

    public function __construct(
        $id,
        $tracker_id,
        $addresses,
        $all_updates,
        $check_permissions
    ) {
        $this->id                = $id;
        $this->tracker_id        = $tracker_id;
        $this->addresses         = $addresses;
        $this->all_updates       = $all_updates;
        $this->check_permissions = $check_permissions;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTrackerId()
    {
        return $this->tracker_id;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function isAllUpdates()
    {
        return $this->all_updates;
    }

    public function isCheckPermissions()
    {
        return $this->check_permissions;
    }
}
