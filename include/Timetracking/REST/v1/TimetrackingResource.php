<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use EventManager;
use StandardPasswordHandler;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\UserManager;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use User_LoginManager;
use User_PasswordExpirationChecker;

class TimetrackingResource extends AuthenticatedResource
{
    /**
     * @var UserManager
     */
    private $rest_user_manager;

    /**
     * @var TimetrackingRepresentationBuilder
     */
    private $representation_builder;

    public function __construct()
    {
        $this->representation_builder = new TimetrackingRepresentationBuilder(
            new TimeRetriever(
                new TimeDao(),
                new PermissionsRetriever(
                    new TimetrackingUgroupRetriever(
                        new TimetrackingUgroupDao()
                    )
                )
            )
        );

        $user_manager = \UserManager::instance();

        $this->rest_user_manager = new UserManager(
            $user_manager,
            new User_LoginManager(
                EventManager::instance(),
                $user_manager,
                new User_PasswordExpirationChecker(),
                new StandardPasswordHandler()
            )
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get Timetracking times
     *
     * Get the times in all platform for user
     *
     * @url GET
     * @access protected
     *
     * @return array {@type TimetrackingRepresentation}
     */
    protected function get()
    {
        $this->checkAccess();

        $this->sendAllowHeaders();

        $current_user = $this->rest_user_manager->getCurrentUser();

        return $this->representation_builder->buildAllRepresentationsForUser($current_user);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
