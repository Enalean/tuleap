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

declare(strict_types=1);

namespace Tuleap\FRS\REST\v1;

use FRSRelease;
use IPermissionsManagerNG;
use PFUser;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Project\REST\UserGroupRepresentation;
use UGroupManager;

class ReleasePermissionsForGroupsBuilder
{
    /**
     * @var FRSPermissionManager
     */
    private $frs_permission_manager;
    /**
     * @var IPermissionsManagerNG
     */
    private $permissions_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(FRSPermissionManager $frs_permission_manager, IPermissionsManagerNG $permissions_manager, UGroupManager $ugroup_manager)
    {
        $this->frs_permission_manager = $frs_permission_manager;
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getRepresentation(PFUser $user, FRSRelease $release): ?ReleasePermissionsForGroupsRepresentation
    {
        if (! $this->frs_permission_manager->isAdmin($release->getProject(), $user)) {
            return null;
        }

        $representation = new ReleasePermissionsForGroupsRepresentation();
        return $representation->build($this->getCanRead($release));
    }

    private function getCanRead(FRSRelease $release): array
    {
        $can_read = [];
        $ugroup_ids = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $release->getProject(),
            $release->getReleaseID(),
            FRSRelease::PERM_READ
        );
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getUGroup($release->getProject(), $ugroup_id);
            if ($ugroup) {
                $can_read[] = (new UserGroupRepresentation())->build((int) $release->getProject()->getID(), $ugroup);
            }
        }
        return $can_read;
    }
}
