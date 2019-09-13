<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

class UsersArtifactsResource extends AuthenticatedResource
{
    /**
     * Get user's artifacts
     *
     * There are 2 types of "user's artifact":
     * * The artifact the user submitted (query={submitted_by: true})
     * * The artifact the user is assigned to (query={assigned_to: true})
     * And you can combine both.
     *
     * @url GET {id}/artifacts
     *
     * @param string $id Id of the desired user, as of today only `self` is allowed to get current user's artifacts
     * @param string $query What artifacts to retrieve {@required}
     * @param int $offset Offset in the collection
     * @param int $limit Limit of the collection being returned
     *
     * @throws RestException 401
     * @throws RestException 400
     * @throws RestException 403
     *
     * @return array {@type MyArtifactsRepresentation}
     */
    protected function getArtifacts(string $id, string $query, int $offset = 0, int $limit = 250): array
    {
        $this->checkAccess();

        $controller = new UsersArtifactsResourceController(\UserManager::instance(), \Tracker_ArtifactFactory::instance());
        return $controller->getArtifacts($id, $query, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/artifacts
     *
     * @param string $id Id of the user
     *
     * @access public
     */
    public function options(string $id)
    {
        Header::allowOptionsGet();
    }
}
