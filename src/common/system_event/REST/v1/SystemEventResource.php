<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class SystemEventResource extends AuthenticatedResource
{
    const MAX_LIMIT = 100;

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

        parent::__construct();
    }

    /**
     * Get system events
     *
     * Get all the system events
     *
     * @url GET
     * @access protected
     *
     * @throw 403
     * @throw 406
     *
     * @return array {@type Tuleap\SystemEvent\REST\v1\SystemEventRepresentation}
     */
    protected function get($limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->checkUserIsSuperAdmin();

        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $paginated_representation = $this->representation_builder->getAllMatchingEvents($limit, $offset);

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

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
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
    private function checkUserIsSuperAdmin()
    {
        $user = $this->user_manager->getCurrentUser();

        if (! $user->isSuperUser()) {
            throw new RestException(403, 'User is not super user');
        }
    }
}
