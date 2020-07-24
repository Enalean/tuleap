<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

use Project;
use PermissionsNormalizer;
use PermissionsNormalizerOverrideCollection;
use ProjectHistoryDao;
use UGroupDao;
use ForgeAccess;

class FRSPermissionCreator
{
    /** @var FRSPermissionDao */
    private $permission_dao;

    /** @var UGroupDao */
    private $ugroup_dao;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    public function __construct(
        FRSPermissionDao $permission_dao,
        UGroupDao $ugroup_dao,
        ProjectHistoryDao $project_history_dao
    ) {
        $this->permission_dao      = $permission_dao;
        $this->ugroup_dao          = $ugroup_dao;
        $this->project_history_dao = $project_history_dao;
    }

    public function savePermissions(Project $project, array $ugroup_ids, $permission_type)
    {
        $normalizer            = new PermissionsNormalizer();
        $override_collection   = new PermissionsNormalizerOverrideCollection();
        $normalized_ugroup_ids = $normalizer->getNormalizedUGroupIds($project, $ugroup_ids, $override_collection);

        $this->permission_dao->savePermissions($project->getId(), $permission_type, $normalized_ugroup_ids);

        $this->project_history_dao->groupAddHistory('perm_granted_for_files', implode(',', $this->getUGroupNames($ugroup_ids)), $project->getId(), [$permission_type]);

        $override_collection->emitFeedback("");
    }

    public function duplicate(Project $project, $template_id)
    {
        $this->permission_dao->duplicate($project->getId(), $template_id);
    }

    private function getUGroupNames(array $ugroup_ids)
    {
        if (! $ugroup_ids) {
            return [];
        }

        $ugroup_name = [];
        $ugroups     = $this->ugroup_dao->searchByListOfUGroupsId($ugroup_ids);

        foreach ($ugroups as $ugroup) {
            $ugroup_name[] = $ugroup['name'];
        }

        return $ugroup_name;
    }

    public function updateProjectAccess(Project $project, $old_access, $new_access)
    {
        if ($new_access === Project::ACCESS_PRIVATE || $new_access === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            $this->permission_dao->disableAnonymousRegisteredAuthenticated($project->getID());
        }
        if ($new_access === Project::ACCESS_PUBLIC && $old_access === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->permission_dao->disableAuthenticated($project->getID());
        }
    }

    public function updateSiteAccess($old_value)
    {
        if ($old_value === ForgeAccess::ANONYMOUS) {
            $this->permission_dao->updateAllAnonymousAccessToRegistered();
        }
        if ($old_value === ForgeAccess::RESTRICTED) {
            $this->permission_dao->updateAllAuthenticatedAccessToRegistered();
        }
    }
}
