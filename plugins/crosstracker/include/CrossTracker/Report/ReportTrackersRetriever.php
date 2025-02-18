<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report;

use LogicException;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidFromCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidFromProjectCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidFromTrackerCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\WidgetInProjectChecker;
use Tuleap\CrossTracker\SearchCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\RetrieveTracker;

final readonly class ReportTrackersRetriever implements RetrieveReportTrackers
{
    public function __construct(
        private ExpertQueryValidator $expert_query_validator,
        private ParserCacheProxy $parser,
        private FromBuilderVisitor $from_builder,
        private RetrieveUserPermissionOnTrackers $trackers_permissions,
        private CrossTrackerExpertQueryReportDao $expert_query_dao,
        private RetrieveTracker $tracker_factory,
        private WidgetInProjectChecker $in_project_checker,
        private SearchCrossTrackerWidget $widget_retriever,
        private ProjectByIDFactory $project_factory,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function getReportTrackers(CrossTrackerQuery $report, PFUser $current_user, int $limit): array
    {
        return $this->retrieveForExpertReport($report, $current_user, $limit);
    }

    /**
     * @return Tracker[]
     * @throws SyntaxError
     * @throws FromIsInvalidException
     * @throws MissingFromException
     */
    private function retrieveForExpertReport(CrossTrackerQuery $report, PFUser $current_user, int $limit): array
    {
        $query = $this->parser->parse($report->getQuery());

        $this->expert_query_validator->validateFromQuery(
            $query,
            new InvalidFromCollectionBuilder(
                new InvalidFromTrackerCollectorVisitor($this->in_project_checker),
                new InvalidFromProjectCollectorVisitor(
                    $this->in_project_checker,
                    $this->widget_retriever,
                    $this->project_factory,
                    $this->event_dispatcher,
                ),
                $report->getWidgetId(),
            ),
            $current_user,
        );

        assert($query->getFrom() !== null); // From part is checked for expert query, so it cannot be null
        $additional_from = $this->from_builder->buildFromWhere($query->getFrom(), $report->getWidgetId(), $current_user);
        $trackers        = $this->trackers_permissions->retrieveUserPermissionOnTrackers(
            $current_user,
            $this->getTrackers(array_map(
                static fn(array $row): int => $row['id'],
                $this->expert_query_dao->searchTrackersIdsMatchingQuery($additional_from, $limit),
            )),
            TrackerPermissionType::PERMISSION_VIEW,
        )->allowed;
        if ($trackers === []) {
            throw new FromIsInvalidException([dgettext('tuleap-crosstracker', 'No tracker found')]);
        }
        return $trackers;
    }

    /**
     * @param int[] $trackers_ids
     * @return Tracker[]
     */
    private function getTrackers(array $trackers_ids): array
    {
        $trackers = [];
        foreach ($trackers_ids as $id) {
            $tracker = $this->tracker_factory->getTrackerById($id);
            if ($tracker === null) {
                throw new LogicException("Tracker #$id found in db but unable to find it again");
            }
            $trackers[] = $tracker;
        }
        return $trackers;
    }
}
