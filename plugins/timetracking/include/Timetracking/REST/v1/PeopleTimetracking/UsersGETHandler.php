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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\Widget\People\ProvideViewableUsersForManager;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\ForgePermissionsRetriever;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

final readonly class UsersGETHandler
{
    private const int OFFSET = 0;
    private const int LIMIT  = 10;

    public function __construct(
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private ProvideViewableUsersForManager $provide_viewable_users_for_manager,
        private UserManager $user_manager,
        private ForgePermissionsRetriever $forge_user_group_permissions_manager,
    ) {
    }

    /**
     * @return Ok<MinimalUserRepresentation[]>|Err<Fault>
     */
    public function handle(string $query, \PFUser $manager): Ok|Err
    {
        if ($manager->isAnonymous()) {
            return Result::err(Fault::fromMessage('Anonymous users cannot retrieve PII'));
        }

        if (
            $manager->isSuperUser()
            || $this->forge_user_group_permissions_manager->doesUserHavePermission($manager, new TrackerAdminAllProjects())
            || $this->forge_user_group_permissions_manager->doesUserHavePermission($manager, new RestReadOnlyAdminPermission())
        ) {
            $collection = $this->user_manager
                ->getPaginatedUsersByUsernameOrRealname($query, false, self::OFFSET, self::LIMIT);
        } else {
            $collection = $this->provide_viewable_users_for_manager
                ->getPaginatedViewableUsersForManager($manager, $query, self::OFFSET, self::LIMIT);
        }

        return Result::ok(
            array_map(
                fn (\PFUser $user) => MinimalUserRepresentation::build($user, $this->provide_user_avatar_url),
                $collection->getUsers(),
            ),
        );
    }
}
