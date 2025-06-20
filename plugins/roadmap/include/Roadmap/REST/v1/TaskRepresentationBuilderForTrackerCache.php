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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Tracker;

final class TaskRepresentationBuilderForTrackerCache implements ICacheTaskRepresentationBuilderForTracker
{
    /**
     * @var array<int, IBuildATaskRepresentation|null>
     */
    private $builders = [];
    /**
     * @var SemanticProgressBuilder
     */
    private $progress_builder;
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var IRetrieveDependencies
     */
    private $dependencies_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        IRetrieveDependencies $dependencies_retriever,
        SemanticProgressBuilder $progress_builder,
        LoggerInterface $logger,
    ) {
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->progress_builder           = $progress_builder;
        $this->dependencies_retriever     = $dependencies_retriever;
        $this->logger                     = $logger;
    }

    public function getRepresentationBuilderForTracker(Tracker $tracker, \PFUser $user): ?IBuildATaskRepresentation
    {
        if (! isset($this->builders[$tracker->getId()])) {
            $this->builders[$tracker->getId()] = $this->getInstance($tracker, $user);
        }

        return $this->builders[$tracker->getId()];
    }

    private function getInstance(Tracker $tracker, \PFUser $user): ?TaskRepresentationBuilderForTracker
    {
        $title_field = $tracker->getTitleField();
        if (! $title_field || ! $title_field->userCanRead($user)) {
            return null;
        }
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($tracker);
        if (! $semantic_timeframe->isDefined()) {
            return null;
        }

        $start_date_field = $semantic_timeframe->getStartDateField();
        if ($start_date_field && ! $start_date_field->userCanRead($user)) {
            return null;
        }

        $end_date_field = $semantic_timeframe->getEndDateField();
        if ($end_date_field && ! $end_date_field->userCanRead($user)) {
            return null;
        }

        $duration_field = $semantic_timeframe->getDurationField();
        if ($duration_field && ! $duration_field->userCanRead($user)) {
            return null;
        }

        return new TaskRepresentationBuilderForTracker(
            $tracker,
            $semantic_timeframe->getTimeframeCalculator(),
            $this->dependencies_retriever,
            $this->progress_builder,
            $this->logger
        );
    }
}
