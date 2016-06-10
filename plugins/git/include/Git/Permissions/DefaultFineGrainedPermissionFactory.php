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

use Project;
use UGroupManager;
use Codendi_Request;

class DefaultFineGrainedPermissionFactory
{

    const ADD_BRANCH_PREFIX = 'add-branch';
    const ADD_TAG_PREFIX    = 'add-tag';

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

    public function getBranchesFineGrainedPermissionsFromRequest(Codendi_Request $request, Project $project)
    {
        return $this->buildRepresentationFromRequest($request, $project, self::ADD_BRANCH_PREFIX);
    }

    public function getTagsFineGrainedPermissionsFromRequest(Codendi_Request $request, Project $project)
    {
        return $this->buildRepresentationFromRequest($request, $project, self::ADD_TAG_PREFIX);
    }

    private function buildRepresentationFromRequest(Codendi_Request $request, Project $project, $prefix)
    {
        $permissions = array();
        $patterns    = $request->get("$prefix-name");

        if ($patterns) {
            foreach ($patterns as $index => $pattern) {
                if ($pattern === '') {
                    continue;
                }

                $writers   = $this->getWritersFromRequest($request, $index, $prefix);
                $rewinders = $this->getRewindersFromRequest($request, $index, $prefix);

                $permissions[] = new DefaultFineGrainedPermissionRepresentation(
                    0,
                    $project->getID(),
                    $pattern,
                    $writers,
                    $rewinders
                );
            }
        }

        return $permissions;
    }

    private function getWritersFromRequest(Codendi_Request $request, $index, $prefix)
    {
        $all_ugroup_ids = $request->get("$prefix-write") ? $request->get("$prefix-write") : array();

        return $this->buildUgroups($all_ugroup_ids, $index);
    }

    private function getRewindersFromRequest(Codendi_Request $request, $index, $prefix)
    {
        $all_ugroup_ids = $request->get("$prefix-rewind") ? $request->get("$prefix-rewind") : array();

        return $this->buildUgroups($all_ugroup_ids, $index);
    }

    /**
     * @return array
     */
    private function buildUgroups(array $all_ugroup_ids, $index)
    {
        $ugroups = array();

        if (isset($all_ugroup_ids[$index])) {
            foreach ($all_ugroup_ids[$index] as $ugroup_id) {
                $ugroups[] = $this->ugroup_manager->getById($ugroup_id);
            }
        }

        return $ugroups;
    }

    public function getBranchesFineGrainedPermissionsForProject(Project $project)
    {
        $permissions = array();

        foreach ($this->dao->searchDefaultBranchesFineGrainedPermissions($project->getID()) as $row) {
            $permissions[] = $this->getInstanceFromRow($row);
        }

        return $permissions;
    }

    public function getTagsFineGrainedPermissionsForProject(Project $project)
    {
        $permissions = array();

        foreach ($this->dao->searchDefaultTagsFineGrainedPermissions($project->getID()) as $row) {
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

        foreach ($this->dao->searchDefaultWriterUgroupIdsForFineGrainedPermissions($permission_id) as $row) {
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

        foreach ($this->dao->searchDefaultRewinderUgroupIdsForFineGrainePermissions($permission_id) as $row) {
            $ugroups[] = $this->ugroup_manager->getById($row['ugroup_id']);
        }

        return $ugroups;
    }

    private function getInstanceFromRow(array $row)
    {
        $permission_id = $row['id'];

        return new DefaultFineGrainedPermissionRepresentation(
            $permission_id,
            $row['project_id'],
            $row['pattern'],
            $this->getWritersForPermission($permission_id),
            $this->getRewindersForPermission($permission_id)
        );
    }
}
