<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\SVN\Admin;

use PFUser;
use ProjectUGroup;
use Tuleap\SVNCore\Repository;

class MailNotification
{
    /**
     * @var array
     */
    private $notified_mails;
    private $path;
    private $repository;
    private $id;
    /**
     * @var PFUser[]
     */
    private $notified_users;
    /**
     * @var ProjectUGroup[]
     */
    private $notified_ugroups;

    public function __construct(
        $id,
        Repository $repository,
        $path,
        array $notified_mails,
        array $notified_users,
        array $notified_ugroups,
    ) {
        $this->id               = $id;
        $this->repository       = $repository;
        $this->notified_mails   = $notified_mails;
        $this->path             = $path;
        $this->notified_users   = $notified_users;
        $this->notified_ugroups = $notified_ugroups;
    }

    /**
     * @return array
     */
    public function getNotifiedMails()
    {
        return $this->notified_mails;
    }

    public function getNotifiedMailsAsString()
    {
        return implode(', ', $this->notified_mails);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PFUser[]
     */
    public function getNotifiedUsers()
    {
        return $this->notified_users;
    }

    public function getNotifiedUsersAsString()
    {
        $users = [];
        foreach ($this->notified_users as $user) {
            $users[] = $user->getUserName();
        }

        return implode(', ', $users);
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getNotifiedUgroups()
    {
        return $this->notified_ugroups;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUsers(array $notified_users)
    {
        $this->notified_users = $notified_users;
    }

    public function setUserGroups(array $user_groups)
    {
        $this->notified_ugroups = $user_groups;
    }

    public function getNotifiedUserGroupsAsString()
    {
        $user_groups = [];

        foreach ($this->notified_ugroups as $ugroup) {
            $user_groups[] = $ugroup->getNormalizedName();
        }

        return implode(', ', $user_groups);
    }
}
