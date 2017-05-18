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

namespace Tuleap\Dashboard\Widget;

use PFUser;
use Project;

class OwnerInfo
{
    public static $TYPE_PROJECT = 'g';
    public static $TYPE_USER    = 'u';

    private $type;
    private $id;

    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id   = $id;
    }

    public static function createForProject(Project $project)
    {
        return new OwnerInfo(self::$TYPE_PROJECT, $project->getID());
    }

    public static function createForUser(PFUser $user)
    {
        return new OwnerInfo(self::$TYPE_USER, $user->getId());
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
