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

use Override;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CrossTracker\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromCollectionBuilder;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromProjectCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromTrackerCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\CrossTrackerTQLQueryDao;
use Tuleap\CrossTracker\Query\Advanced\WidgetInProjectChecker;
use Tuleap\CrossTracker\Widget\RetrieveCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Tracker;

final readonly class QueryTrackersRetriever implements RetrieveQueryTrackers
{
    public function __construct(
        private ExpertQueryValidator $expert_query_validator,
        private FromBuilderVisitor $from_builder,
        private RetrieveUserPermissionOnTrackers $trackers_permissions,
        private CrossTrackerTQLQueryDao $tql_query_dao,
        private WidgetInProjectChecker $in_project_checker,
        private ProjectByIDFactory $project_factory,
        private EventDispatcherInterface $event_dispatcher,
        private TrackersListAllowedByPlugins $trackers_list_allowed_by_plugins,
    ) {
    }

    #[Override]
    public function getQueryTrackers(RetrieveCrossTrackerWidget $retriever, ParsedCrossTrackerQuery $query, PFUser $current_user, int $limit): array
    {
        return $this->retrieveForQuery($retriever, $query, $current_user, $limit);
    }

    /**
     * @return Tracker[]
     * @throws SyntaxError
     * @throws FromIsInvalidException
     * @throws MissingFromException
     */
    private function retrieveForQuery(RetrieveCrossTrackerWidget $retriever, ParsedCrossTrackerQuery $query, PFUser $current_user, int $limit): array
    {
        $this->expert_query_validator->validateFromQuery(
            $query->parsed_query,
            new InvalidFromCollectionBuilder(
                new InvalidFromTrackerCollectorVisitor($this->in_project_checker),
                new InvalidFromProjectCollectorVisitor(
                    $this->in_project_checker,
                    $this->project_factory,
                    $this->event_dispatcher,
                    $retriever
                ),
                $query->getWidgetId(),
            ),
            $current_user,
        );

        assert($query->parsed_query->getFrom() !== null); // From part is checked for expert query, so it cannot be null
        $additional_from = $this->from_builder->buildFromWhere($query->parsed_query->getFrom(), $query->getWidgetId(), $current_user);
        $trackers        = $this->trackers_permissions->retrieveUserPermissionOnTrackers(
            $current_user,
            $this->trackers_list_allowed_by_plugins->getTrackers(array_map(
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
}
