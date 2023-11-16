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
use Tracker;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;

final class TaskRepresentationBuilderForTracker implements IBuildATaskRepresentation
{
    /**
     * @var IComputeTimeframes
     */
    private $timeframe_calculator;
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
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Tracker $tracker,
        IComputeTimeframes $timeframe_calculator,
        IRetrieveDependencies $dependencies_retriever,
        SemanticProgressBuilder $progress_builder,
        LoggerInterface $logger,
    ) {
        $this->tracker                = $tracker;
        $this->timeframe_calculator   = $timeframe_calculator;
        $this->dependencies_retriever = $dependencies_retriever;
        $this->progress_calculator    = $progress_builder->getSemantic($tracker)->getComputationMethod();
        $this->logger                 = $logger;
    }

    public function buildRepresentation(Artifact $artifact, \PFUser $user): TaskRepresentation
    {
        if ($this->tracker->getId() !== $artifact->getTracker()->getId()) {
            throw new \RuntimeException("Given artifact is not part of the tracker");
        }

        $date_period = $this->timeframe_calculator->buildDatePeriodWithoutWeekendForChangesetForREST($artifact->getLastChangeset(), $user, $this->logger);
        $start_date  = $date_period->getStartDate();
        $start       = $start_date ? (new \DateTimeImmutable())->setTimestamp($start_date) : null;
        $end_date    = $date_period->getEndDate();
        $end         = $end_date ? (new \DateTimeImmutable())->setTimestamp($end_date) : null;

        $progress_result   = $this->progress_calculator->computeProgression($artifact, $user);
        $are_dates_implied = $this->timeframe_calculator->getName() === TimeframeImpliedFromAnotherTracker::getName();

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
            $are_dates_implied,
            $artifact->isOpen(),
            $date_period->getErrorMessage(),
            $this->dependencies_retriever->getDependencies($artifact),
            new ProjectReference($artifact->getTracker()->getProject()),
        );
    }
}
