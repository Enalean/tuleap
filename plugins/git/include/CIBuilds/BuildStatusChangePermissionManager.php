<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\CIBuilds;

class BuildStatusChangePermissionManager
{
    /**
     * @var BuildStatusChangePermissionDAO
     */
    private $dao;

    public function __construct(
        BuildStatusChangePermissionDAO $dao,
    ) {
        $this->dao = $dao;
    }

    public function updateBuildStatusChangePermissions(\GitRepository $repository, array $permissions): void
    {
        $granted_groups_ids = implode(',', $permissions);

        $this->dao->updateBuildStatusChangePermissionsForRepository($repository->getId(), $granted_groups_ids);
    }

    public function getBuildStatusChangePermissions(\GitRepository $repository): array
    {
        $permissions = $this->dao->searchBuildStatusChangePermissionsForRepository($repository->getId());

        if ($permissions === null) {
            return [];
        }

        return explode(',', $permissions);
    }

    public function canUserSetBuildStatusInRepository(\PFUser $user, \GitRepository $repository): bool
    {
        $project            = $repository->getProject();
        $granted_groups_ids = $this->getBuildStatusChangePermissions($repository);

        foreach ($granted_groups_ids as $group_id) {
            if ($user->isMemberOfUGroup($group_id, (int) $project->getID())) {
                return true;
            }
        }

        return false;
    }
}
