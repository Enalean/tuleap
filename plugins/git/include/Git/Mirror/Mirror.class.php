<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Git_Mirror_Mirror
{

    public $id;

    public $name;

    public $url;

    public $hostname;

     /** @var PFUser */
    public $owner;

    public $owner_name;

    public $owner_id;

    public $ssh_key;

    public function __construct(PFUser $owner, $id, $url, $hostname, $name)
    {
        $this->id       = $id;
        $this->url      = $url;
        $this->hostname = $hostname;
        $this->owner    = $owner;
        $this->name     = $name;

        $this->ssh_key    = ($owner->getAuthorizedKeysRaw()) ? $owner->getAuthorizedKeysRaw() : '';
        $this->owner_name = $owner->getName();
        $this->owner_id   = $owner->getId();
    }

    public function __toString()
    {
        return self::class . ' ' . $this->id;
    }
}
