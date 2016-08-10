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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

use Project;
use PermissionsNormalizer;
use PermissionsNormalizerOverrideCollection;
use UGroupDao;

class FRSPermissionCreator
{
    /** @var PermissionDao */
    private $permission_dao;

    public function __construct(
        FRSPermissionDao $permission_dao,
        UGroupDao $ugroup_dao
    ) {
        $this->permission_dao = $permission_dao;
        $this->ugroup_dao     = $ugroup_dao;
    }

    public function savePermissions(Project $project, array $ugroup_ids)
    {
        $normalizer            = new PermissionsNormalizer();
        $override_collection   = new PermissionsNormalizerOverrideCollection();
        $normalized_ugroup_ids = $normalizer->getNormalizedUGroupIds($project, $ugroup_ids, $override_collection);

        $this->permission_dao->savePermissions($project->getId(), FRSPermission::FRS_ADMIN, $normalized_ugroup_ids);

        group_add_history('perm_granted_for_files', implode(',', $this->getUGroupNames($ugroup_ids)), $project->getId());

        $override_collection->emitFeedback("");
    }

    public function duplicate(Project $project, $template_id)
    {
        $permissions = $this->permission_dao->getBindingPermissionsByProject($project->getID(), $template_id);

        $duplicate_permissions = array();
        foreach ($permissions as $permission) {
            $duplicate_permissions[] = $permission['ugroup_id'];
        }

        if (count($duplicate_permissions) > 0) {
            $this->permission_dao->savePermissions($project->getId(), $permission['permission_type'], $duplicate_permissions);
        }
    }

    private function getUGroupNames(array $ugroup_ids)
    {
        $ugroup_name = array();
        $ugroups     = $this->ugroup_dao->searchByListOfUGroupsId($ugroup_ids);

        foreach ($ugroups as $ugroup) {
            $ugroup_name[] = $ugroup['name'];
        }

        return $ugroup_name;
    }
}
