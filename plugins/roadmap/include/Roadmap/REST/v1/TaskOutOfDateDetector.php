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
use Psr\Log\LoggerInterface;
use Tracker_Semantic_Status;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class TaskOutOfDateDetector implements IDetectIfArtifactIsOutOfDate
{
    /**
     * @var SemanticStatusRetriever
     */
    private $semantic_status_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    public function __construct(
        SemanticStatusRetriever $semantic_status_retriever,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger,
    ) {
        $this->semantic_status_retriever  = $semantic_status_retriever;
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->logger                     = $logger;
    }

    public function isArtifactOutOfDate(
        Artifact $artifact,
        DateTimeImmutable $now,
        \PFUser $user,
        TrackersWithUnreadableStatusCollection $trackers_with_unreadable_status_collection,
    ): bool {
        $semantic_status    = $this->semantic_status_retriever->retrieveSemantic($artifact->getTracker());
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());

        $status_field = $semantic_status->getField();
        if ($status_field === null || $semantic_status->isOpen($artifact)) {
            return false;
        }

        return $this->hasBeenClosedMoreThanOneYearAgo(
            $artifact,
            $semantic_status,
            $status_field,
            $now,
            $user,
            $trackers_with_unreadable_status_collection
        )
            || $this->isEndDateMoreThanOneYearAgo($semantic_timeframe, $artifact, $user, $now);
    }

    private function hasBeenClosedMoreThanOneYearAgo(
        Artifact $artifact,
        \Tracker_Semantic_Status $semantic_status,
        \Tracker_FormElement_Field_List $status_field,
        DateTimeImmutable $now,
        \PFUser $user,
        TrackersWithUnreadableStatusCollection $trackers_with_unreadable_status_collection,
    ): bool {
        if (! $status_field->userCanRead($user)) {
            $trackers_with_unreadable_status_collection->add($artifact->getTracker());

            return true;
        }

        $changesets = array_reverse($artifact->getChangesets());
        foreach ($changesets as $changeset) {
            if (! $changeset->canHoldValue()) {
                continue;
            }

            $status_value = $changeset->getValue($status_field);
            if ($status_value === null) {
                continue;
            }

            if (! $status_value->hasChanged()) {
                continue;
            }

            $value = $status_value->getValue();
            if (empty($value)) {
                continue;
            }

            if ($this->isAnOpenValue((int) $value[0], $semantic_status)) {
                continue;
            }

            $close_date   = new DateTimeImmutable('@' . $changeset->getSubmittedOn());
            $closed_since = $now->diff($close_date);
            if ($closed_since->days > 365) {
                return true;
            }

            return false;
        }

        $this->logger->error(
            sprintf(
                "[Roadmap widget] Artifact #%s is closed but we can't find the changeset where the action has been performed. Hence, it won't be displayed.",
                $artifact->getId()
            )
        );

        return true;
    }

    private function isEndDateMoreThanOneYearAgo(
        SemanticTimeframe $semantic_timeframe,
        Artifact $artifact,
        \PFUser $user,
        DateTimeImmutable $now,
    ): bool {
        $timeframe_calculator = $semantic_timeframe->getTimeframeCalculator();
        $date_period          = $timeframe_calculator->buildDatePeriodWithoutWeekendForArtifactForREST($artifact, $user, $this->logger);
        $task_end_date        = $this->getDateTheTaskEnds($date_period);

        if ($task_end_date === null) {
            return false;
        }

        if ($task_end_date > $now) {
            return false;
        }

        $diff_from_now = $now->diff($task_end_date);

        return $diff_from_now->days > 365;
    }

    private function isAnOpenValue(int $value_id, Tracker_Semantic_Status $semantic_status): bool
    {
        return in_array($value_id, $semantic_status->getOpenValues());
    }

    private function getDateTheTaskEnds(DatePeriodWithoutWeekEnd $date_period): ?DateTimeImmutable
    {
        $start_date = $date_period->getStartDate();
        $end_date   = $date_period->getEndDate();

        if ($start_date === null && $end_date === null) {
            return null;
        }

        if ($end_date === null) {
            return new DateTimeImmutable('@' . $start_date);
        }

        return new DateTimeImmutable('@' . $end_date);
    }
}
