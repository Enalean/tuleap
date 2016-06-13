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

class DefaultFineGrainedPermissionReplicator
{
    /**
     * @var FineGrainedDao
     */
    private $fine_grained_dao;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $factory;

    /**
     * @var DefaultFineGrainedPermissionSaver
     */
    private $saver;

    public function __construct(
        FineGrainedDao $fine_grained_dao,
        DefaultFineGrainedPermissionFactory $factory,
        DefaultFineGrainedPermissionSaver $saver
    ) {
        $this->fine_grained_dao = $fine_grained_dao;
        $this->factory          = $factory;
        $this->saver            = $saver;
    }

    public function replicate(
        Project $template_project,
        $new_project_id,
        array $ugroups_mapping
    ) {
        $this->fine_grained_dao->duplicateDefaultFineGrainedPermissionsEnabled(
            $template_project->getId(),
            $new_project_id
        );

        $replicated_branch_permissions = $this->factory->mapBranchPermissionsForProject(
            $template_project,
            $new_project_id,
            $ugroups_mapping
        );

        foreach ($replicated_branch_permissions as $permission) {
            $this->saver->saveBranchPermission($permission);
        }

        $replicated_tag_permissions = $this->factory->mapTagPermissionsForProject(
            $template_project,
            $new_project_id,
            $ugroups_mapping
        );

        foreach ($replicated_tag_permissions as $permission) {
            $this->saver->saveTagPermission($permission);
        }
    }
}
