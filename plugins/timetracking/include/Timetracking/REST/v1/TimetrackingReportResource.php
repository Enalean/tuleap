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
use Tuleap\REST\UserManager;
use Tuleap\Timetracking\Exceptions\TimetrackingReportNotFoundException;
use Tuleap\Timetracking\Time\TimetrackingReport;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Timetracking\Time\TimetrackingReportFactory;
use Tuleap\Tracker\Report\TrackerDuplicateException;
use Tuleap\Tracker\Report\TrackerNotFoundException;
use Tuleap\Tracker\Report\TrackerReportExtractor;

class TimetrackingReportResource extends AuthenticatedResource
{
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

    public function __construct()
    {
        $this->rest_user_manager = UserManager::build();
        $this->report_dao        = new TimetrackingReportDao();
        $this->report_factory    = new TimetrackingReportFactory($this->report_dao, \TrackerFactory::instance());
        $this->extractor         = new TrackerReportExtractor(\TrackerFactory::instance());
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
