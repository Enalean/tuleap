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

namespace Tuleap\CrossTracker\Query;

use LogicException;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CrossTracker\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromCollectionBuilder;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromProjectCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromTrackerCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\CrossTrackerTQLQueryDao;
use Tuleap\CrossTracker\Query\Advanced\WidgetInProjectChecker;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Tracker;

final readonly class QueryTrackersRetriever implements RetrieveQueryTrackers, InstantiateRetrievedQueryTrackerIds
{
    public function __construct(
        private ExpertQueryValidator $expert_query_validator,
        private FromBuilderVisitor $from_builder,
        private RetrieveUserPermissionOnTrackers $trackers_permissions,
        private CrossTrackerTQLQueryDao $tql_query_dao,
        private RetrieveTracker $tracker_factory,
        private WidgetInProjectChecker $in_project_checker,
        private SearchCrossTrackerWidget $widget_retriever,
        private ProjectByIDFactory $project_factory,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function getQueryTrackers(ParsedCrossTrackerQuery $query, PFUser $current_user, int $limit): array
    {
        return $this->retrieveForQuery($query, $current_user, $limit);
    }

    /**
     * @return Tracker[]
     * @throws SyntaxError
     * @throws FromIsInvalidException
     * @throws MissingFromException
     */
    private function retrieveForQuery(ParsedCrossTrackerQuery $query, PFUser $current_user, int $limit): array
    {
        $this->expert_query_validator->validateFromQuery(
            $query->parsed_query,
            new InvalidFromCollectionBuilder(
                new InvalidFromTrackerCollectorVisitor($this->in_project_checker),
                new InvalidFromProjectCollectorVisitor(
                    $this->in_project_checker,
                    $this->widget_retriever,
                    $this->project_factory,
                    $this->event_dispatcher,
                ),
                $query->getWidgetId(),
            ),
            $current_user,
        );

        assert($query->parsed_query->getFrom() !== null); // From part is checked for expert query, so it cannot be null
        $additional_from = $this->from_builder->buildFromWhere($query->parsed_query->getFrom(), $query->getWidgetId(), $current_user);
        $trackers        = $this->trackers_permissions->retrieveUserPermissionOnTrackers(
            $current_user,
            $this->getTrackers(array_map(
                static fn(array $row): int => $row['id'],
                $this->tql_query_dao->searchTrackersIdsMatchingQuery($additional_from, $limit),
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
    public function getTrackers(array $trackers_ids): array
    {
        $event = $this->event_dispatcher->dispatch(new RetrievedQueryTrackerIds($trackers_ids));

        $trackers = [];
        foreach ($event->getTrackerIds() as $id) {
            $tracker = $this->tracker_factory->getTrackerById($id);
            if ($tracker === null) {
                throw new LogicException("Tracker #$id found in db but unable to find it again");
            }
            $trackers[] = $tracker;
        }
        return $trackers;
    }
}
