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

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Timetracking\Widget\People\ViewableUsersForManagerProviderDao;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;

final class PeopleTimetrackingUsersResource extends AuthenticatedResource
{
    public const string NAME = 'timetracking_people_users';

    /**
     * @url OPTIONS /
     */
    public function options(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get users
     *
     * Get the users matching the query. The current user must be allowed to read their timetracking.<br>
     *
     * Note: only the first 10 users are returned.
     *
     *
     * @url GET /
     * @access protected
     *
     * @param string $query Search string (3 chars min in length) {@from query} {@min 3}
     *
     * @return array list of users {@type \Tuleap\User\REST\MinimalUserRepresentation}
     * @throws RestException
     */
    protected function get(string $query): array
    {
        $this->checkAccess();

        $user_manager = \UserManager::instance();

        $handler = new UsersGETHandler(
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            new ViewableUsersForManagerProviderDao($user_manager),
            $user_manager,
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao(),
            ),
        );

        return $handler->handle($query, $user_manager->getCurrentUser())
            ->match(
                fn (array $users) => $users,
                function (Fault $fault) {
                    FaultMapper::mapToRestException($fault);
                }
            );
    }
}
