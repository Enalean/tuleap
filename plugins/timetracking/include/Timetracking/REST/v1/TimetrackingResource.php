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

use DateTime;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\REST\UserManager;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeChecker;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use Tuleap\Timetracking\Time\TimeUpdater;

class TimetrackingResource extends AuthenticatedResource
{
    const DEFAULT_OFFSET  = 0;
    const MAX_LIMIT       = 50;
    const MAX_TIMES_BATCH = 100;

    /**
     * @var UserManager
     */
    private $rest_user_manager;

    /**
     * @var TimetrackingRepresentationBuilder
     */
    private $representation_builder;

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    /**
     * @var TimeUpdater
     */
    private $time_updater;

    public function __construct()
    {
        $this->representation_builder = new TimetrackingRepresentationBuilder();
        $time_dao                     = new TimeDao();
        $permissionsRetriever         = new PermissionsRetriever((
        new TimetrackingUgroupRetriever(
            new TimetrackingUgroupDao()
        )
        ));
        $this->time_retriever         = new TimeRetriever(
            $time_dao,
            $permissionsRetriever
        );
        $this->time_updater           = new TimeUpdater($time_dao, new TimeChecker(), $permissionsRetriever);
        $this->rest_user_manager      = UserManager::build();
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
     * @url GET
     * @access protected
     *
     * @param string $query JSON object of search criteria properties {@from query}
     * @param int    $limit     Number of elements displayed per page {@from path}{@min 1}{@max 100}
     * @param int    $offset    Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type TimetrackingRepresentation}
     * @throws RestException
     */
    protected function get($query, $limit = self::MAX_TIMES_BATCH, $offset = self::DEFAULT_OFFSET)
    {
        $this->checkAccess();

        $this->sendAllowHeaders();

        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());

        try {
            $start_date = $query_parameter_parser->getString($query, 'start_date');
            $end_date   = $query_parameter_parser->getString($query, 'end_date');
        } catch (QueryParameterException $ex) {
            throw new RestException(400, $ex->getMessage());
        }

        $this->checkTimePeriodIsValid($start_date, $end_date);

        $current_user = $this->rest_user_manager->getCurrentUser();

        $paginated_times = $this->time_retriever->getPaginatedTimesForUserInTimePeriodByArtifact(
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

        return $this->representation_builder->buildPaginatedTimes($paginated_times->getTimes());
    }

    /**
     * Add a Time
     *
     * Add a time in Timetracking modal
     *
     * <br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>You do not have the obligation to fill in the step field </li>
     *  <li>A time needs to respect the format "11:11" </li>
     *  <li>Exemple of date "2018-01-01"</li>
     *  <li>artifact_id is an integer like 1</li>
     * </ol>
     *
     * @url POST
     * @access protected
     *
     * @status 201
     * @param TimetrackingPOSTRepresentation $item_representation The created Time {@from body} {@type Tuleap\Timetracking\REST\v1\TimetrackingPOSTRepresentation}
     * @return TimetrackingRepresentation
     *
     * @throws 400
     * @throws 401
     * @throws 404
     */
    protected function addTime(TimetrackingPOSTRepresentation $item)
    {
        $this->checkAccess();

        $this->sendAllowHeaders();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $artifact = $this->getArtifact($current_user, $item->artifact_id);

        try {
            $time_representation = new TimetrackingRepresentation();
            $this->time_updater->addTimeForUserInArtifact($current_user, $artifact, $item->date_time, $item->time_value, $item->step);
            $time_representation->build($this->time_retriever->getLastTime($current_user, $artifact));
            return $time_representation;
        } catch (TimeTrackingBadTimeFormatException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (TimeTrackingMissingTimeException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (TimeTrackingNotAllowedToAddException $e) {
            throw new RestException(401, $e->getMessage());
        } catch (TimeTrackingBadDateFormatException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    /**
     * Update a Time
     *
     * Update a time in Timetracking modal
     *
     * <br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>You do not have the obligation to fill in the step field </li>
     *  <li>A time needs to respect the format "11:11" </li>
     *  <li>Exemple of date "2018-01-01"</li>
     *  <li>time_id is an integers or 602</li>
     * </ol>
     *
     * @url PUT {id}
     * @status 201
     * @param int $time_id Id of the time
     * @param TimetrackingPUTRepresentation $item_representation The edited Time {@from body} {@type Tuleap\Timetracking\REST\v1\TimetrackingPUTRepresentation}
     *
     * @return TimetrackingRepresentation
     *
     * @throws 400
     * @throws 401
     * @throws 404
     */
    protected function updateTime($id, TimetrackingPUTRepresentation $item)
    {
        $this->checkAccess();

        $this->sendAllowHeaders();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $time = $this->time_retriever->getTimeByIdForUser($current_user, $id);
        if (! $time) {
            throw new RestException(404, dgettext('tuleap-timetracking', "This time does not exist"));
        }
        $artifact = $this->getArtifact($current_user, $time->getArtifactId());

        try {
            $time_representation = new TimetrackingRepresentation();
            $this->time_updater->updateTime($current_user, $artifact, $time, $item->date_time, $item->time_value, $item->step);
            $time_representation->build($this->time_retriever->getTimeByIdForUser($current_user, $id));
            return $time_representation;
        } catch (TimeTrackingBadTimeFormatException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (TimeTrackingMissingTimeException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (TimeTrackingNotAllowedToEditException $e) {
            throw new RestException(401, $e->getMessage());
        } catch (TimeTrackingBadDateFormatException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPutPost();
    }

    private function checkTimePeriodIsValid($start_date, $end_date)
    {
        $period_start = DateTime::createFromFormat(DateTime::ISO8601, $start_date);
        $period_end   = DateTime::createFromFormat(DateTime::ISO8601, $end_date);

        if (! $period_start || ! $period_end) {
            throw new RestException(400, "Please provide valid ISO-8601 dates");
        }

        $period_length = $period_start->diff($period_end);

        if ($period_length->days < 1) {
            throw new RestException(400, 'There must be one day offset between the both dates');
        }
        if ($period_start > $period_end) {
            throw new RestException(400, "end_date must be greater than start_date");
        }
    }

    private function getArtifact(\PFUser $user, $artifact_id)
    {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByIdUserCanView($user, $artifact_id);
        if (! $artifact) {
            throw new RestException(404, dgettext('tuleap-timetracking', "Please add the time on an existing artifact"));
        }
        return $artifact;
    }
}
