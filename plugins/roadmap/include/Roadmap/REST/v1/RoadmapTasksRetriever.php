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
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use URLVerification;
use UserManager;

final class RoadmapTasksRetriever
{
    /**
     * @var RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var URLVerification
     */
    private $url_verification;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var IRetrieveDependencies
     */
    private $dependencies_retriever;
    /**
     * @var RoadmapTasksOutOfDateFilter
     */
    private $tasks_filter;
    /**
     * @var SemanticProgressBuilder
     */
    private $progress_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RoadmapWidgetDao $dao,
        \ProjectManager $project_manager,
        UserManager $user_manager,
        URLVerification $url_verification,
        TrackerFactory $tracker_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        \Tracker_ArtifactFactory $artifact_factory,
        IRetrieveDependencies $dependencies_retriever,
        RoadmapTasksOutOfDateFilter $tasks_filter,
        SemanticProgressBuilder $progress_builder,
        LoggerInterface $logger
    ) {
        $this->dao                        = $dao;
        $this->project_manager            = $project_manager;
        $this->user_manager               = $user_manager;
        $this->url_verification           = $url_verification;
        $this->tracker_factory            = $tracker_factory;
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->artifact_factory           = $artifact_factory;
        $this->dependencies_retriever     = $dependencies_retriever;
        $this->tasks_filter               = $tasks_filter;
        $this->progress_builder           = $progress_builder;
        $this->logger                     = $logger;
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

        $tracker = $this->tracker_factory->getTrackerById($widget_row['tracker_id']);
        if (! $tracker || ! $tracker->isActive() || ! $tracker->userCanView($user)) {
            throw $this->get404();
        }
        $this->checkTrackerHasTitleSemantic($tracker, $user);

        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($tracker);
        $this->checkTrackerHasTimeframeSemantic($semantic_timeframe, $user);

        $representation_builder = new TaskRepresentationBuilderForTracker(
            $tracker,
            $semantic_timeframe->getTimeframeCalculator(),
            $this->dependencies_retriever,
            $this->progress_builder,
            $this->logger
        );

        $paginated_artifacts = $this->artifact_factory->getPaginatedArtifactsByTrackerId(
            $tracker->getId(),
            $limit,
            $offset,
            false
        );

        $filtered_artifacts = $this->tasks_filter->filterOutOfDateArtifacts(
            $paginated_artifacts->getArtifacts(),
            new DateTimeImmutable(),
            $user
        );

        $representations = [];
        foreach ($filtered_artifacts as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $parent = $artifact->getParent($user);
            if ($parent && $parent->getTracker()->getId() === $tracker->getId()) {
                continue;
            }

            $representations[] = $representation_builder->buildRepresentation($artifact, $user);
        }

        return new PaginatedCollectionOfTaskRepresentations($representations, $paginated_artifacts->getTotalSize());
    }

    private function get404(): RestException
    {
        return new I18NRestException(404, dgettext('tuleap-roadmap', 'The roadmap cannot be found.'));
    }

    /**
     *
     * @throws I18NRestException
     */
    private function checkTrackerHasTitleSemantic(\Tracker $tracker, \PFUser $user): void
    {
        $title_field = $tracker->getTitleField();
        if (! $title_field || ! $title_field->userCanRead($user)) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-roadmap',
                    'The tracker does not have a "title" field, or you are not allowed to see it.'
                )
            );
        }
    }

    /**
     *
     * @throws I18NRestException
     */
    private function checkTrackerHasTimeframeSemantic(SemanticTimeframe $semantic_timeframe, \PFUser $user): void
    {
        $error_message = dgettext(
            'tuleap-roadmap',
            'The tracker does not have a timeframe defined, or you are not allowed to see it.'
        );

        if (! $semantic_timeframe->isDefined()) {
            throw new I18NRestException(400, $error_message);
        }

        $start_date_field = $semantic_timeframe->getStartDateField();
        if ($start_date_field && ! $start_date_field->userCanRead($user)) {
            throw new I18NRestException(400, $error_message);
        }

        $end_date_field = $semantic_timeframe->getEndDateField();
        if ($end_date_field && ! $end_date_field->userCanRead($user)) {
            throw new I18NRestException(400, $error_message);
        }

        $duration_field = $semantic_timeframe->getDurationField();
        if ($duration_field && ! $duration_field->userCanRead($user)) {
            throw new I18NRestException(400, $error_message);
        }
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
