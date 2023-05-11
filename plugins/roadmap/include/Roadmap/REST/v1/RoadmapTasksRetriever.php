<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use DateTimeImmutable;
use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use TrackerFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Roadmap\RetrieveReportToFilterArtifacts;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use URLVerification;
use UserManager;

final class RoadmapTasksRetriever
{
    public function __construct(
        private readonly RoadmapWidgetDao $dao,
        private readonly \ProjectManager $project_manager,
        private readonly UserManager $user_manager,
        private readonly URLVerification $url_verification,
        private readonly TrackerFactory $tracker_factory,
        private readonly SemanticTimeframeBuilder $semantic_timeframe_builder,
        private readonly \Tracker_ArtifactFactory $artifact_factory,
        private readonly IRetrieveDependencies $dependencies_retriever,
        private readonly RoadmapTasksOutOfDateFilter $tasks_filter,
        private readonly SemanticProgressBuilder $progress_builder,
        private readonly LoggerInterface $logger,
        private readonly RetrieveReportToFilterArtifacts $report_to_filter_retriever,
    ) {
    }

    /**
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getTasks(int $id, int $limit, int $offset): PaginatedCollectionOfTaskRepresentations
    {
        $widget_row = $this->dao->searchById($id);
        if (! $widget_row) {
            throw $this->get404();
        }

        $user = $this->user_manager->getCurrentUser();
        $this->checkUserCanAccessProject($widget_row['owner_id'], $user);

        $representation_builders_by_tracker_id = $this->getRepresentationBuildersIndexedByTrackerId($widget_row['id'], $user);

        $tracker_ids = array_keys($representation_builders_by_tracker_id);

        $report = $this->report_to_filter_retriever->getReportToFilterArtifacts($id, $user);
        if ($report) {
            $matching            = $report->getMatchingIds(null, true);
            $ids                 = $matching['id'] ? array_map('intval', explode(',', $matching['id'])) : [];
            $paginated_artifacts = $this->artifact_factory->getPaginatedArtifactsByListOfArtifactIds(
                $ids,
                $limit,
                $offset
            );
        } else {
            $paginated_artifacts = $this->artifact_factory->getPaginatedArtifactsByListOfTrackerIds(
                $tracker_ids,
                $limit,
                $offset
            );
        }

        $trackers_with_unreadable_status_collection = new TrackersWithUnreadableStatusCollection($this->logger);

        $filtered_artifacts = $this->tasks_filter->filterOutOfDateArtifacts(
            $paginated_artifacts->getArtifacts(),
            new DateTimeImmutable(),
            $user,
            $trackers_with_unreadable_status_collection,
        );

        $representations = [];
        foreach ($filtered_artifacts as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $parent = $artifact->getParent($user);
            if ($parent && in_array($parent->getTracker()->getId(), $tracker_ids, true)) {
                continue;
            }

            $tracker_id = $artifact->getTracker()->getId();
            if (! isset($representation_builders_by_tracker_id[$tracker_id])) {
                throw new \RuntimeException("Unable to find representation builder");
            }

            $representations[] = $representation_builders_by_tracker_id[$tracker_id]->buildRepresentation($artifact, $user);
        }

        $trackers_with_unreadable_status_collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();

        return new PaginatedCollectionOfTaskRepresentations($representations, $paginated_artifacts->getTotalSize());
    }

    /**
     * @psalm-return array<int, TaskRepresentationBuilderForTracker>
     */
    private function getRepresentationBuildersIndexedByTrackerId(int $id, \PFUser $user): array
    {
        $selected_trackers = $this->dao->searchSelectedTrackers($id);
        if (! $selected_trackers) {
            return [];
        }

        $readable_trackers = [];
        foreach ($selected_trackers as $tracker_id) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if (! $tracker || ! $tracker->isActive() || ! $tracker->userCanView($user)) {
                continue;
            }

            $readable_trackers[] = $tracker;
        }

        if (! $readable_trackers) {
            throw $this->get404();
        }

        $trackers_with_title_semantics = [];
        foreach ($readable_trackers as $tracker) {
            $title_field = $tracker->getTitleField();
            if (! $title_field || ! $title_field->userCanRead($user)) {
                continue;
            }
            $trackers_with_title_semantics[] = $tracker;
        }

        if (! $trackers_with_title_semantics) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-roadmap',
                    'The tracker does not have a "title" field, or you are not allowed to see it.'
                )
            );
        }

        $representations_builder = [];
        foreach ($trackers_with_title_semantics as $tracker) {
            $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($tracker);
            if (! $this->doesTrakerHaveTrackerSemantic($semantic_timeframe, $user)) {
                continue;
            }

            $representations_builder[$tracker->getId()] = new TaskRepresentationBuilderForTracker(
                $tracker,
                $semantic_timeframe->getTimeframeCalculator(),
                $this->dependencies_retriever,
                $this->progress_builder,
                $this->logger
            );
        }

        if (! $representations_builder) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-roadmap',
                    'The tracker does not have a timeframe defined, or you are not allowed to see it.'
                )
            );
        }

        return $representations_builder;
    }

    private function get404(): RestException
    {
        return new I18NRestException(404, dgettext('tuleap-roadmap', 'The roadmap cannot be found.'));
    }

    private function doesTrakerHaveTrackerSemantic(SemanticTimeframe $semantic_timeframe, \PFUser $user): bool
    {
        if (! $semantic_timeframe->isDefined()) {
            return false;
        }

        $start_date_field = $semantic_timeframe->getStartDateField();
        if ($start_date_field && ! $start_date_field->userCanRead($user)) {
            return false;
        }

        $end_date_field = $semantic_timeframe->getEndDateField();
        if ($end_date_field && ! $end_date_field->userCanRead($user)) {
            return false;
        }

        $duration_field = $semantic_timeframe->getDurationField();
        if ($duration_field && ! $duration_field->userCanRead($user)) {
            return false;
        }

        return true;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function checkUserCanAccessProject(int $project_id, \PFUser $user): void
    {
        $project = $this->project_manager->getProject($project_id);
        ProjectAuthorization::userCanAccessProject($user, $project, $this->url_verification);
    }
}
