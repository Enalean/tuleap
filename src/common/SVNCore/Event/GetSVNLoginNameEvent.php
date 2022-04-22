<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\Event;

use Tuleap\Event\Dispatchable;

class GetSVNLoginNameEvent implements Dispatchable
{
    public const NAME = 'get_svn_login_name';

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var string
     */
    private $username;
    /**
     * @var \Project
     */
    private $project;

    public function __construct(\PFUser $user, \Project $project)
    {
        $this->user     = $user;
        $this->username = $user->getUserName();
        $this->project  = $project;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return \PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
