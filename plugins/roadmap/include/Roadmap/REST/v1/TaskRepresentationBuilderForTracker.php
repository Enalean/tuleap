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

use Tracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class TaskRepresentationBuilderForTracker implements IBuildATaskRepresentation
{
    /**
     * @var TimeframeBuilder
     */
    private $timeframe_builder;
    /**
     * @var IRetrieveDependencies
     */
    private $dependencies_retriever;
    /**
     * @var \Tuleap\Tracker\Semantic\Progress\IComputeProgression
     */
    private $progress_calculator;
    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(
        Tracker $tracker,
        TimeframeBuilder $timeframe_builder,
        IRetrieveDependencies $dependencies_retriever,
        SemanticProgressBuilder $progress_builder
    ) {
        $this->tracker                = $tracker;
        $this->timeframe_builder      = $timeframe_builder;
        $this->dependencies_retriever = $dependencies_retriever;
        $this->progress_calculator    = $progress_builder->getSemantic($tracker)->getComputationMethod();
    }

    public function buildRepresentation(Artifact $artifact, \PFUser $user): TaskRepresentation
    {
        if ($this->tracker->getId() !== $artifact->getTracker()->getId()) {
            throw new \RuntimeException("Given artifact is not part of the tracker");
        }

        $time_period = $this->timeframe_builder->buildTimePeriodWithoutWeekendForArtifactForREST($artifact, $user);
        $start_date  = $time_period->getStartDate();
        $start       = $start_date ? (new \DateTimeImmutable())->setTimestamp($start_date) : null;
        $end_date    = $time_period->getEndDate();
        $end         = $end_date ? (new \DateTimeImmutable())->setTimestamp($end_date) : null;

        $progress_result = $this->progress_calculator->computeProgression($artifact, $user);

        return new TaskRepresentation(
            $artifact->getId(),
            $artifact->getXRef(),
            $artifact->getUri(),
            (string) $artifact->getTitle(),
            $this->tracker->getColor()->getName(),
            $progress_result->getValue(),
            $progress_result->getErrorMessage(),
            $start,
            $end,
            $this->dependencies_retriever->getDependencies($artifact),
        );
    }
}
