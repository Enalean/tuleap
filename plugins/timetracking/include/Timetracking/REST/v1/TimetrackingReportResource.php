<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Timetracking\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterParser;
use Tuleap\REST\UserManager;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Exceptions\TimetrackingReportNotFoundException;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimetrackingReport;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Timetracking\Time\TimetrackingReportFactory;
use Tuleap\Tracker\Report\TrackerDuplicateException;
use Tuleap\Tracker\Report\TrackerNotFoundException;
use Tuleap\Tracker\Report\TrackerReportExtractor;

class TimetrackingReportResource extends AuthenticatedResource
{
    const DEFAULT_OFFSET  = 0;
    const MAX_LIMIT       = 50;

    /**
     * @var UserManager
     */
    private $rest_user_manager;

    /**
     * @var TimetrackingReportDao
     */
    private $report_dao;

    /**
     * @var TimetrackingReportFactory
     */
    private $report_factory;

    /**
     * @var TrackerReportExtractor
     */
    private $extractor;

    /**
     * @var TrackerRepresentationFactory
     */
    private $tracker_representation_factory;

    public function __construct()
    {
        $this->rest_user_manager              = UserManager::build();
        $this->report_dao                     = new TimetrackingReportDao();
        $this->report_factory                 = new TimetrackingReportFactory(
            $this->report_dao,
            \TrackerFactory::instance()
        );
        $this->extractor                      = new TrackerReportExtractor(\TrackerFactory::instance());
        $this->tracker_representation_factory = new TrackerRepresentationFactory(
            new TimeDao(),
            new PermissionsRetriever(new TimetrackingUgroupRetriever(new TimetrackingUgroupDao())),
            \TrackerFactory::instance(),
            \Tracker_ArtifactFactory::instance()
        );
    }

    /**
     * @url OPTIONS {id}
     */
    public function options($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get timetracking report
     *
     * Get the definition of the given report id
     *
     * @url    GET {id}
     * @access protected
     *
     * @param int $id Id of the report
     *
     * @status 200
     *
     * @return TimetrackingReportRepresentation
     *
     * @throws 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        try {
            $report         = $this->getReport($id);
            $representation = $this->getReportRepresentation($report);
            return $representation;
        } catch (TimetrackingReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }
    }

    /**
     * Get times of the report's trackers
     *
     * Get times af a selected report
     * Times are grouped by trackers
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
     *  Example of query:
     * <br><br>
     * {"trackers_id":[16,22],"start_date":"2019-01-01T00:00:00+01","end_date":"2019-01-20T00:00:00+01"}
     *
     * @url GET {id}/times
     *
     * @status 200
     *
     * @access protected
     *
     * @param int $id Id of the report
     * @param string $query With a property "trackers_id","start_date" and "end_date" to search trackers' times. {@from path}
     * @param int $limit
     * @param int $offset
     *
     * @return TimetrackingTrackerReportRepresentation[]
     *
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function getIdTimes(int $id, string $query, int $limit = self::MAX_LIMIT, int $offset = self::DEFAULT_OFFSET)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();
        try {
            $current_user = $this->rest_user_manager->getCurrentUser();
            $json_decoder = new JsonDecoder();
            $json_query   = $json_decoder->decodeAsAnArray('query', $query);

            $checker = new TimetrackingQueryChecker();
            $checker->checkQuery($json_query);

            $start_date = $json_query[ "start_date" ];
            $end_date   = $json_query[ "end_date" ];

            $report = $this->getReport($id);

            $trackers = [];
            foreach ($this->getTrackersFromRoute($query, $report) as $tracker) {
                $trackers[ $tracker->getId() ] = $tracker;
            }

            return $this->tracker_representation_factory->getTrackersRepresentationWithTimes($trackers, $start_date, $end_date, $current_user, $limit, $offset);
        } catch (TimetrackingReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }
    }

    /**
     * Update a timetracking report
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"trackers_id": [1, 2, 3],<br>
     * }<br>
     * </pre>
     *
     * @url PUT {id}
     *
     * @param int   $id Id of the report
     * @param int[] $trackers_id Tracker id to link to report
     *
     * @status 200
     * @access protected
     *
     * @return TimetrackingReportRepresentation
     *
     * @throws 400
     * @throws 404
     */
    protected function put($id, array $trackers_id)
    {
        $this->sendAllowHeaders();

        try {
            $trackers = $this->extractor->extractTrackers($trackers_id);

            $report          = $this->getReport($id);
            $expected_report = new TimetrackingReport($report->getId(), $trackers);
            $this->report_dao->updateReport($id, $trackers);
        } catch (TimetrackingReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $this->getReportRepresentation($expected_report);
    }

    /**
     * @param $id
     *
     * @return TimetrackingReport
     * @throws TimetrackingReportNotFoundException
     * @throws 403
     */
    private function getReport(int $id)
    {
        $report       = $this->report_factory->getReportById($id);
        $current_user = $this->rest_user_manager->getCurrentUser();

        $this->checkUserIsAllowedToSeeReport($current_user, $report);

        return $report;
    }

    /**
     * @param $report TimetrackingReport
     *
     * @return TimetrackingReportRepresentation
     */
    private function getReportRepresentation(TimetrackingReport $report)
    {
        $representation = new TimetrackingReportRepresentation();
        $representation->build($report);

        return $representation;
    }

    /**
     * @param                    $query
     * @param TimetrackingReport $report
     *
     * @throws 400
     *
     * @return array
     */
    private function getTrackersFromRoute($query, TimetrackingReport $report) : array
    {
        $query_parser = new QueryParameterParser(new JsonDecoder());

        $query = trim($query);

        try {
            $trackers_id = $query_parser->getArrayOfInt($query, 'trackers_id');

            if (empty($trackers_id)) {
                return $report->getTrackers();
            }

            return $this->extractor->extractTrackers($trackers_id);
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @param PFUser             $user
     * @param TimetrackingReport $report
     *
     * @throws RestException 403
     **/
    private function checkUserIsAllowedToSeeReport(PFUser $user, TimetrackingReport $report)
    {
        $widget = $this->report_dao->searchTimetrackingWidgetByTimetrackingReportId($report->getId());

        if ($widget['user_id'] !== (int) $user->getId()) {
            throw new RestException(403, 'You can only see your own reports');
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPut();
    }
}
