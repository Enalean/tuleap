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

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use UserManager;

class UserResource extends AuthenticatedResource
{
    public const MAX_TIMES_BATCH = 100;

    /** @var UserManager */
    private $user_manager;


    public function __construct()
    {
        $this->user_manager = UserManager::instance();
    }

    /**
     * @param $id
     * @param String $query
     * @param int $limit
     * @param int $offset
     * @return TimetrackingRepresentation[][]
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function getUserTimes($id, $query, $limit, $offset)
    {
        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());
        $query_checker = new TimetrackingQueryChecker();

        try {
            $start_date = $query_parameter_parser->getString($query, 'start_date');
            $end_date   = $query_parameter_parser->getString($query, 'end_date');
        } catch (QueryParameterException $ex) {
            throw new RestException(400, $ex->getMessage());
        }
        $representation_builder = new TimetrackingRepresentationBuilder();
        $query_checker->checkTimePeriodIsValid($start_date, $end_date);

        if ($id != $this->user_manager->getCurrentUser()->getId()) {
            throw new RestException(403, 'You can only access to your own preferences');
        }

        $current_user = $this->user_manager->getCurrentUser();

        $time_retriever = new TimeRetriever(
            new TimeDao(),
            new PermissionsRetriever((
            new TimetrackingUgroupRetriever(
                new TimetrackingUgroupDao()
            )
            )),
            new AdminDao(),
            \ProjectManager::instance()
        );

        $paginated_times = $time_retriever->getPaginatedTimesForUserInTimePeriodByArtifact(
            $current_user,
            $start_date,
            $end_date,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders(
            $limit,
            $offset,
            $paginated_times->getTotalSize(),
            self::MAX_TIMES_BATCH
        );

        return $representation_builder->buildPaginatedTimes($paginated_times->getTimes());
    }
}
