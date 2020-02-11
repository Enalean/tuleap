<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Timetracking\REST\v1\User;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Timetracking\REST\v1\TimetrackingRepresentation;
use Tuleap\Timetracking\REST\v1\UserResource;

final class TimetrackingUserResource extends AuthenticatedResource
{
    private const MAX_TIMES_BATCH = 100;
    private const DEFAULT_OFFSET  = 0;

    /**
     * @url OPTIONS /{id}/timetracking
     * @access protected
     */
    protected function optionsGetUserTimes(int $id, string $query, int $limit = self::MAX_TIMES_BATCH, int $offset = self::DEFAULT_OFFSET): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get Timetracking times
     *
     * Get the times in all projects for the current user and a given time period
     *
     * <br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>You have to specify a start_date and an end_date</li>
     *  <li>One day minimum between the two dates</li>
     *  <li>end_date must be greater than start_date</li>
     *  <li>Dates must be in ISO date format</li>
     * </ol>
     *
     * Example of query:
     * <br><br>
     * {
     *   "start_date": "2018-03-01T00:00:00+01",
     *   "end_date"  : "2018-03-31T00:00:00+01"
     * }
     * @url GET /{id}/timetracking
     * @access protected
     *
     * @param int $id user's id
     * @param string $query JSON object of search criteria properties {@from query}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 100}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return TimetrackingRepresentation[][]
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function getUserTimes(int $id, string $query, int $limit = self::MAX_TIMES_BATCH, int $offset = self::DEFAULT_OFFSET): array
    {
        $this->checkAccess();

        $this->optionsGetUserTimes($id, $query, $limit, $offset);

        $user_resource = new UserResource();

        return $user_resource->getUserTimes(
            $id,
            $query,
            $limit,
            $offset
        );
    }
}
