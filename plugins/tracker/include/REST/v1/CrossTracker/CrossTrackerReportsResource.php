<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\CrossTracker;

use Luracast\Restler\RestException;
use TrackerFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportDao;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportFactory;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportNotFoundException;
use UserManager;

class CrossTrackerReportsResource extends AuthenticatedResource
{
    /**
     * @var CrossTrackerReportExtractor
     */
    private $cross_tracker_extractor;
    /**
     * @var CrossTrackerReportDao
     */
    private $cross_tracker_dao;
    /**
     * @var CrossTrackerReportFactory
     */
    private $report_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager   = UserManager::instance();
        $this->report_factory = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );

        $this->cross_tracker_dao       = new CrossTrackerReportDao();
        $this->cross_tracker_extractor = new CrossTrackerReportExtractor(TrackerFactory::instance());
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the report
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get cross tracker report
     *
     * Get the definition of the given report id
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the report
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws 404
     */
    protected function getId($id)
    {
        $this->checkAccess();
        try {
            $current_user   = $this->user_manager->getCurrentUser();
            $report         = $this->report_factory->getById($id, $current_user);
            $representation = $this->getReportRepresentation($report);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }

        $this->sendAllowHeaders();

        return $representation;
    }

    /**
     * Update a cross tracker report
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"trackers_id": [1, 2, 3]<br>
     * }<br>
     * </pre>
     *
     * @url PUT {id}
     *
     * @param int $id Id of the report
     * @param array {@max 10}  $trackers_id  Tracker id to link to report
     *
     * @status 201
     * @access hybrid
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws 400
     * @throws 404
     */
    protected function put($id, array $trackers_id)
    {
        $this->sendAllowHeaders();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $trackers     = $this->cross_tracker_extractor->extractTrackers($trackers_id);

            $this->cross_tracker_dao->updateReport($id, $trackers);
            $report = $this->report_factory->getById($id, $current_user);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $this->getReportRepresentation($report);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPut();
    }

    /**
     * @param $report
     *
     * @return CrossTrackerReportRepresentation
     */
    private function getReportRepresentation($report)
    {
        $representation = new CrossTrackerReportRepresentation();
        $representation->build($report);

        return $representation;
    }
}
