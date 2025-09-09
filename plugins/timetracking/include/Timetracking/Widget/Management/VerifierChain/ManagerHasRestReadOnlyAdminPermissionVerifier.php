<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management\VerifierChain;

use Tuleap\Timetracking\Widget\Management\TimeSpentInArtifact;
use Tuleap\User\ForgePermissionsRetriever;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;

final class ManagerHasRestReadOnlyAdminPermissionVerifier extends ManagerIsAllowedToSeeTimesVerifierChain
{
    public function __construct(
        private readonly ForgePermissionsRetriever $forge_user_group_permissions_manager,
    ) {
    }

    #[\Override]
    public function isManagerAllowedToSeeTimes(TimeSpentInArtifact $time, \PFUser $manager): bool
    {
        return $this->forge_user_group_permissions_manager->doesUserHavePermission($manager, new RestReadOnlyAdminPermission())
            || parent::isManagerAllowedToSeeTimes($time, $manager);
    }
}
