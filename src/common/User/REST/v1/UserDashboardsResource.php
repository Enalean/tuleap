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

namespace Tuleap\User\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Dashboard\User\DashboardDao;
use Tuleap\Dashboard\User\UserDashboard;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardSaver;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

final class UserDashboardsResource extends AuthenticatedResource
{
    public const string ROUTE = 'user_dashboards';

    /**
     * @url OPTIONS /
     *
     * @access public
     */
    public function option(): void
    {
        Header::allowOptionsGetPost();
    }

    /**
     * Create dashboard
     *
     * Create a new dashboard for current user
     *
     * @url POST /
     *
     * @access hybrid
     *
     * @param string $name Name of the dashboard {@from body}{@required}
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 400
     *
     */
    protected function post(string $name): DashboardRepresentation
    {
        $this->checkAccess();

        $current_user =  UserManager::instance()->getCurrentUser();

        $dao        = new DashboardDao();
        $legacy_dao = new UserDashboardDao(
            new DashboardWidgetDao(
                new WidgetFactory(
                    UserManager::instance(),
                    new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                    \EventManager::instance()
                )
            )
        );

        $saver = new UserDashboardSaver($dao, $legacy_dao);

        try {
            $id = $saver->save($current_user, $name);
            if ($id === false) {
                throw new RestException(500, 'Unable to save user dashboard.');
            }

            $user_dashboard = $dao->searchByUserIdAndName($current_user, $name);
            if ($user_dashboard === null) {
                throw new RestException(500, 'Unable to retrieve just created user dashboard.');
            }

            return DashboardRepresentation::fromDashboard(
                new UserDashboard(
                    $user_dashboard['id'],
                    $user_dashboard['user_id'],
                    $user_dashboard['name']
                )
            );
        } catch (NameDashboardAlreadyExistsException) {
            throw new RestException(400, 'Dashboard already exists');
        } catch (NameDashboardDoesNotExistException) {
            throw new RestException(400, 'Dashboard name cannot be empty');
        }
    }
}
