<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV;

use HTTPRequest;
use PFUser;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectManager;
use Tuleap\CrossTracker\CrossTrackerDefaultReport;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\CrossTrackerReportNotFoundException;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedException;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsMatcher;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\AccessNotActiveException;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\Header;
use Tuleap\Tracker\Report\Query\Advanced\Errors\QueryErrorsTranslator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectException;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use URLVerification;

class CSVExportController implements DispatchableWithRequest
{
    public const MAX_LIMIT = 50;
    /**
     * @var CrossTrackerReportFactory
     */
    private $report_factory;
    /**
     * @var CrossTrackerArtifactReportFactory
     */
    private $artifact_report_factory;
    /**
     * @var CSVRepresentationFactory
     */
    private $csv_representation_factory;
    /**
     * @var CrossTrackerReportDao
     */
    private $cross_tracker_dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CrossTrackerPermissionGate
     */
    private $cross_tracker_permission_gate;
    /**
     * @var SimilarFieldsMatcher
     */
    private $similar_fields_matcher;

    public function __construct(
        CrossTrackerReportFactory $report_factory,
        CrossTrackerArtifactReportFactory $artifact_report_factory,
        CSVRepresentationFactory $csv_representation_factory,
        CrossTrackerReportDao $cross_tracker_dao,
        ProjectManager $project_manager,
        CrossTrackerPermissionGate $cross_tracker_permission_gate,
        SimilarFieldsMatcher $similar_fields_matcher,
    ) {
        $this->report_factory                = $report_factory;
        $this->artifact_report_factory       = $artifact_report_factory;
        $this->csv_representation_factory    = $csv_representation_factory;
        $this->cross_tracker_dao             = $cross_tracker_dao;
        $this->project_manager               = $project_manager;
        $this->cross_tracker_permission_gate = $cross_tracker_permission_gate;
        $this->similar_fields_matcher        = $similar_fields_matcher;
    }

    /**
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        session_write_close();
        $current_user = $request->getCurrentUser();

        $report_id = $variables['report_id'];
        try {
            [$limit, $offset] = $this->getPaginationParameters($request);
            $representations  = $this->buildRepresentations($current_user, $report_id, $limit, $offset);
            Header::sendPaginationHeaders($limit, $offset, $representations->getTotalSize(), self::MAX_LIMIT);
            header('Content-Type: text/csv; charset=utf-8');
            echo $representations;
        } catch (BadRequestException $e) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo $e->getMessage();
        } catch (ForbiddenException $e) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo $e->getMessage();
        } catch (NotFoundException $e) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo $e->getMessage();
        }
    }

    /**
     * @param int    $report_id
     * @param int    $limit
     * @param int    $offset
     * @return PaginatedCollectionOfCSVRepresentations
     * @throws NotFoundException
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    private function buildRepresentations(PFUser $current_user, $report_id, $limit, $offset)
    {
        try {
            $report = $this->report_factory->getById($report_id);
            if ($report->isExpert()) {
                throw new BadRequestException(dgettext('tuleap-crosstracker', 'CSV export of expert report is not supported'));
            }

            $this->checkUserIsAllowedToSeeReport($current_user, $report);
            $collection = $this->artifact_report_factory->getArtifactsMatchingReport(
                $report,
                $current_user,
                $limit,
                $offset,
            );
            assert($collection instanceof ArtifactMatchingReportCollection);
            assert($report instanceof CrossTrackerDefaultReport);
            $similar_fields = $this->similar_fields_matcher->getSimilarFieldsCollection($report, $current_user);
            return $this->csv_representation_factory->buildRepresentations(
                $collection,
                $current_user,
                $similar_fields
            );
        } catch (CrossTrackerReportNotFoundException $e) {
            throw new NotFoundException(
                sprintf(dgettext('tuleap-crosstracker', 'Report with id %d not found'), $report_id)
            );
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $e) {
            throw new BadRequestException($e->getMessage());
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException $e) {
            throw new BadRequestException($e->getI18NExceptionMessage());
        } catch (LimitSizeIsExceededException | InvalidSelectException | SelectLimitExceededException | SelectablesMustBeUniqueException | MissingFromException $exception) {
            throw new BadRequestException(QueryErrorsTranslator::translateException($exception));
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * @throws BadRequestException
     * @return array
     */
    private function getPaginationParameters(HTTPRequest $request)
    {
        if (! $request->existAndNonEmpty('limit') || ! $request->existAndNonEmpty('offset')) {
            throw new BadRequestException(
                dgettext(
                    'tuleap-crosstracker',
                    "'limit' and 'offset' are required parameters. Please add them in the query."
                )
            );
        }

        if (! is_numeric($request->get('limit'))) {
            throw new BadRequestException(
                dgettext('tuleap-crosstracker', "Invalid value for 'limit'. It must be an integer")
            );
        }
        if (! is_numeric($request->get('offset'))) {
            throw new BadRequestException(
                dgettext(
                    'tuleap-crosstracker',
                    "Invalid value for 'offset'. It must be an integer"
                )
            );
        }
        $limit  = (int) $request->get('limit');
        $offset = (int) $request->get('offset');

        if ($limit > self::MAX_LIMIT) {
            throw new BadRequestException(
                sprintf(dgettext('tuleap-crosstracker', "The maximum value for 'limit' is %d"), self::MAX_LIMIT)
            );
        }

        return [$limit, $offset];
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    private function checkUserIsAllowedToSeeReport(PFUser $user, CrossTrackerReport $report): void
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetByCrossTrackerReportId($report->getId());
        if (
            $widget !== null
            && $widget['dashboard_type'] === UserDashboardController::DASHBOARD_TYPE
            && $widget['user_id'] !== (int) $user->getId()
        ) {
            throw new ForbiddenException();
        }

        if ($widget !== null && $widget['dashboard_type'] === ProjectDashboardController::DASHBOARD_TYPE) {
            $project = $this->project_manager->getProject($widget['project_id']);
            try {
                $url_verification = new URLVerification();
                $url_verification->userCanAccessProject($user, $project);
            } catch (Project_AccessProjectNotFoundException | AccessNotActiveException) {
                throw new NotFoundException(dgettext('tuleap-crosstracker', 'Project not found'));
            } catch (Project_AccessException $exception) {
                throw new ForbiddenException(dgettext('tuleap-crosstracker', "You don't have permission to access this project"));
            }
        }

        try {
            $this->cross_tracker_permission_gate->check($user, $report);
        } catch (CrossTrackerUnauthorizedException $exception) {
            throw new ForbiddenException($exception->getMessage());
        }
    }
}
