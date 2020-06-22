<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SystemEvent\REST\v1;

use Tuleap\REST\AuthenticatedResource;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use UserManager;
use SystemEventDao;
use SystemEventManager;
use User_ForgeUserGroupPermissionsManager;
use User_ForgeUserGroupPermissionsDao;
use Tuleap\User\ForgeUserGroupPermission\RetrieveSystemEventsInformationApi;
use PFUser;

class SystemEventResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 100;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var PaginatedSystemEventRepresentationsBuilder
     */
    private $representation_builder;

    public function __construct()
    {
        $this->user_manager           = UserManager::instance();
        $this->representation_builder = new PaginatedSystemEventRepresentationsBuilder(
            new SystemEventDao(),
            SystemEventManager::instance()
        );

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * Get system events
     *
     * Get all the system events
     *
     * @url GET
     * @access protected
     *
     * @param string $status Number of elements displayed per page {@from path} {@choice new,done,warning,error,running}
     * @param int    $limit  Number of elements displayed per page {@from path} {@min 0} {@max 100}
     * @param int    $offset Position of the first element to display {@from path} {@min 0}
     *
     * @throw 403
     *
     * @return array {@type Tuleap\SystemEvent\REST\v1\SystemEventRepresentation}
     */
    protected function get($status = null, $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->checkUserIsAllowedToSeeSystemEvents();

        $paginated_representation = $this->representation_builder->getAllMatchingEvents($status, $limit, $offset);

        $this->sendAllowHeadersForProject();
        $this->sendPaginationHeaders($limit, $offset, $paginated_representation->getTotalSize());

        return $paginated_representation->getSystemEventRepresentations();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeadersForProject()
    {
        Header::allowOptionsGet();
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeadersForProject();
    }

    /**
     *
     * @throws RestException
     */
    private function checkUserIsAllowedToSeeSystemEvents()
    {
        $user = $this->user_manager->getCurrentUser();

        if (! $user->isSuperUser() && ! $this->isUserIsAllowedToSeeSystemEventThroughTheApi($user)) {
            throw new RestException(403, 'User is not allowed to see system events');
        }
    }

    private function isUserIsAllowedToSeeSystemEventThroughTheApi(PFUser $user)
    {
        $permission = new RetrieveSystemEventsInformationApi();

        return $this->forge_ugroup_permissions_manager->doesUserHavePermission($user, $permission);
    }
}
