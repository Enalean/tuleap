<?php
/*
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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


/**
 * Git Repository with its permissions
 */
class GitRepositoryWithPermissions
{
    private $repository;
    private $permissions = array(
        Git::PERM_READ          => array(),
        Git::PERM_WRITE         => array(),
        Git::PERM_WPLUS         => array(),
        Git::SPECIAL_PERM_ADMIN => array()
    );

    public function __construct(GitRepository $repository, array $permissions = array())
    {
        $this->repository  = $repository;
        if (count($permissions) > 0) {
            $this->permissions = $permissions;
        }
    }

    public function addUGroupForPermissionType($permission_type, $ugroup_id)
    {
        if (!isset($this->permissions[$permission_type])) {
            throw new RuntimeException('Invalid GIT permission type ' . $permission_type);
        }
        $this->permissions[$permission_type][] = $ugroup_id;
    }

    /**
     * @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Permissions for the repository
     * permission_type => array of ugroup id that can access the repo
     *
     * @return Array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
