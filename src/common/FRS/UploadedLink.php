<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\FRS;

use PFUser;

class UploadedLink
{
    public const int EVENT_CREATE = 401;
    public const int EVENT_DELETE = 402;

    private $id;
    private $owner;
    private $link;
    private $name;
    private $release_time;

    public function __construct($id, PFUser $owner, $link, $name, $release_time)
    {
        $this->id           = $id;
        $this->owner        = $owner;
        $this->link         = $link;
        $this->name         = $name;
        $this->release_time = $release_time;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @psalm-mutation-free
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @psalm-mutation-free
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @psalm-mutation-free
     */
    public function getReleaseTime()
    {
        return $this->release_time;
    }

    /**
     * @psalm-mutation-free
     */
    public function getId()
    {
        return $this->id;
    }
}
