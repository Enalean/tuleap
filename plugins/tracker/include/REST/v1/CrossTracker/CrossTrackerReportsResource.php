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
use PFUser;
use ProjectManager;
use TrackerFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Tracker\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\Tracker\CrossTracker\CrossTrackerReport;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportDao;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportFactory;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportNotFoundException;
use URLVerification;
use UserManager;

class CrossTrackerReportsResource extends AuthenticatedResource
{
    const MAX_LIMIT = 50;
    /**
     * @var JsonDecoder
     */
    private $json_decoder;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CrossTrackerArtifactReportFactory
     */
    private $cross_tracker_artifact_factory;
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
        $this->json_decoder   = new JsonDecoder();
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $this->report_factory  = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );

        $this->cross_tracker_dao              = new CrossTrackerReportDao();
        $this->cross_tracker_extractor        = new CrossTrackerReportExtractor(TrackerFactory::instance());
        $this->cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            \Tracker_ArtifactFactory::instance()
        );
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
    public function getId($id)
    {
        $this->checkAccess();
        try {
            $report         = $this->getReport($id);
            $representation = $this->getReportRepresentation($report);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }

        $this->sendAllowHeaders();

        return $representation;
    }

    /**
     * Get cross artifacts linked to tracker report
     *
     * /!\ route under construction
     * Get open artifacts linked to given trackers.
     * <br>
     * <br>
     * ?query is optional. When filled, it is a json object:
     * <ul>
     *   <li>With a property "trackers_id" to search artifacts presents in given trackers.
     *     Example: <pre>{"trackers_id": [1,2,3]}</pre>
     *   </li>
     * </ul>
     * @url GET {id}/content
     * @access hybrid
     *
     * @param int    $id Id of the report
     * @param string $query With a property "trackers_id" to search artifacts presents in given trackers. {@required false}
     * @param int    $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return CrossTrackerArtifactReportRepresentation[]
     *
     * @throws 404
     */
    public function getIdContent($id, $query, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $report       = $this->getReport($id);

            $artifacts = $this->cross_tracker_artifact_factory->getArtifactsFromGivenTrackers(
                $this->getTrackersFromRoute($query, $report),
                $current_user,
                $limit,
                $offset
            );
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize());

        return array("artifacts" => $artifacts->getArtifacts());
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
            $this->getReport($id);
            $trackers = $this->cross_tracker_extractor->extractTrackers($trackers_id);
            $this->cross_tracker_dao->updateReport($id, $trackers);

            $report = $this->getReport($id);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
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

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @param                    $query
     * @param CrossTrackerReport $report
     *
     * @return array
     */
    private function getTrackersFromRoute($query, CrossTrackerReport $report)
    {
        $query      = trim($query);
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (count($json_query) === 0) {
            return $report->getTrackers();
        }

        if (! isset($json_query['trackers_id'])) {
            throw new RestException(400, "Missing trackers_id entry in query parameter");
        }

        if (! is_array($json_query['trackers_id'])) {
            throw new RestException(400, "trackers_id must be an array of int.");
        }

        $only_numeric_trackers = array_filter($json_query['trackers_id'], 'is_int');
        if ($only_numeric_trackers !== $json_query['trackers_id']) {
            throw new RestException(400, "trackers_id must be an array of int.");
        }

        return $this->cross_tracker_extractor->extractTrackers($json_query['trackers_id']);
    }

    private function checkUserIsAllowedToSeeReport(PFUser $user, CrossTrackerReport $report)
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetByCrossTrackerReportId($report->getId());
        if ($widget['dashboard_type'] === 'user' && $widget['user_id'] !== $user->getId()) {
            throw new RestException(403);
        }

        if ($widget['dashboard_type'] === 'project') {
            $project = $this->project_manager->getProject($widget['project_id']);
            ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        }
    }

    /**
     * @param $id
     *
     * @return \Tuleap\Tracker\CrossTracker\CrossTrackerReport
     */
    private function getReport($id)
    {
        $current_user = $this->user_manager->getCurrentUser();
        $report       = $this->report_factory->getById($id, $current_user);
        $this->checkUserIsAllowedToSeeReport($current_user, $report);

        return $report;
    }
}
