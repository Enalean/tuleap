<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Access;

use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final class UserCanAccessProjectAdministrationVerifier implements VerifyUserCanAccessProjectAdministration
{
    private MembershipDelegationDao $membership_delegation_dao;

    public function __construct(MembershipDelegationDao $membership_delegation_dao)
    {
        $this->membership_delegation_dao = $membership_delegation_dao;
    }

    #[\Override]
    public function canUserAccessProjectAdministration(CurrentUserWithLoggedInInformation $current_user, \Project $project): bool
    {
        if (! $current_user->is_logged_in) {
            return false;
        }

        $user       = $current_user->user;
        $project_id = (int) $project->getID();
        return $user->isSuperUser()
            || $user->isAdmin($project_id)
            || $this->membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $project_id);
    }
}
