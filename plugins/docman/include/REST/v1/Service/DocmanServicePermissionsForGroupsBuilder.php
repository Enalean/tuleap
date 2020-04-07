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
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Service;

use Docman_PermissionsManager;
use IPermissionsManagerNG;
use Project;
use ProjectUGroup;
use Tuleap\Project\REST\UserGroupRepresentation;
use UGroupManager;

class DocmanServicePermissionsForGroupsBuilder
{
    /**
     * @var IPermissionsManagerNG
     */
    private $permissions_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(IPermissionsManagerNG $permissions_manager, UGroupManager $ugroup_manager)
    {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function getServicePermissionsForGroupRepresentation(Project $project): DocmanServicePermissionsForGroupsRepresentation
    {
        $project_id = $project->getID();
        $ugroup_ids = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $project_id,
            Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN
        );

        $user_group_representations = [];

        foreach ($ugroup_ids as $ugroup_id) {
            if ((int) $ugroup_id === ProjectUGroup::NONE) {
                continue;
            }
            $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if ($ugroup !== null) {
                $user_group_representations[] = (new UserGroupRepresentation())->build((int) $project_id, $ugroup);
            }
        }

        return DocmanServicePermissionsForGroupsRepresentation::build($user_group_representations);
    }
}
