<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\iAuthenticate;
use Luracast\Restler\InvalidAuthCredentials;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminUserBuilder;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class BasicAuthentication implements iAuthenticate
{
    /**
     * @var RestReadOnlyAdminUserBuilder
     */
    private $read_only_admin_user_builder;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->read_only_admin_user_builder = new RestReadOnlyAdminUserBuilder(
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            )
        );

        $this->user_manager = UserManager::instance();
    }

    public function __isAllowed() // phpcs:ignore
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $current_user = $this->user_manager->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

            if ($current_user->isLoggedIn()) {
                $current_user = $this->read_only_admin_user_builder->buildReadOnlyAdminUser($current_user);
                $this->user_manager->setCurrentUser($current_user);

                return true;
            }

            throw new InvalidAuthCredentials(401, 'Basic Authentication Required');
        }
    }

    public static function __getMaximumSupportedVersion() // phpcs:ignore
    {
        return 2;
    }

    /**
     * Needed due to iAuthenticate interface since Restler v3.0.0-RC6
     */
    public function __getWWWAuthenticateString() // phpcs:ignore
    {
        return 'Basic realm="' . AuthenticatedResource::REALM . '" Token realm="' . AuthenticatedResource::REALM . '" AccessKey realm="' . AuthenticatedResource::REALM . '"';
    }
}
