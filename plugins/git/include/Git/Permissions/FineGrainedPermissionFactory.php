<?php

/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use GitRepository;
use UGroupManager;

class FineGrainedPermissionFactory
{

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var FineGrainedDao
     */
    private $dao;

    public function __construct(FineGrainedDao $dao, UGroupManager $ugroup_manager)
    {
        $this->dao            = $dao;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getBranchesFineGrainedPermissionsForRepository(GitRepository $repository)
    {
        $permissions = array();

        foreach ($this->dao->searchBranchesFineGrainedPermissionsForRepository($repository->getId()) as $row) {
            $permissions[] = $this->getInstanceFromRow($row);
        }

        return $permissions;
    }

    public function getTagsFineGrainedPermissionsForRepository(GitRepository $repository)
    {
        $permissions = array();

        foreach ($this->dao->searchTagsFineGrainedPermissionsForRepository($repository->getId()) as $row) {
            $permissions[] = $this->getInstanceFromRow($row);
        }

        return $permissions;
    }

    /**
     * @return UGroups[]
     */
    private function getWritersForPermission($permission_id)
    {
        $ugroups = array();

        foreach ($this->dao->searchWriterUgroupIdsForFineGrainedPermissions($permission_id) as $row) {
            $ugroups[] = $this->ugroup_manager->getById($row['ugroup_id']);
        }

        return $ugroups;
    }

    /**
     * @return UGroups[]
     */
    private function getRewindersForPermission($permission_id)
    {
        $ugroups = array();

        foreach ($this->dao->searchRewinderUgroupIdsForFineGrainePermissions($permission_id) as $row) {
            $ugroups[] = $this->ugroup_manager->getById($row['ugroup_id']);
        }

        return $ugroups;
    }

    private function getInstanceFromRow(array $row)
    {
        $permission_id = $row['id'];

        return new FineGrainedPermissionRepresentation(
            $permission_id,
            $row['repository_id'],
            $row['pattern'],
            $this->getWritersForPermission($permission_id),
            $this->getRewindersForPermission($permission_id)
        );
    }
}
