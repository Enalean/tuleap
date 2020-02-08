<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Widget\Event;

use Project;
use Tuleap\Event\Dispatchable;

class UserWithStarBadgeCollector implements Dispatchable
{
    public const NAME = "userWithStarBadgeCollector";

    /** @var Project */
    private $project;
    /** @var \PFUser[] */
    private $users;
    /** @var \Tuleap\Widget\Event\UserWithStarBadge */
    private $badged_user;

    /**
     * @param \PFUser[] $users
     */
    public function __construct(Project $project, array $users)
    {
        $this->users = $users;
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return \PFUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return UserWithStarBadge
     */
    public function getUserWithStarBadge()
    {
        return $this->badged_user;
    }

    public function setUserWithStarBadge(UserWithStarBadge $badged_user)
    {
        $this->badged_user = $badged_user;
    }
}
