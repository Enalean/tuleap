<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Semantic\Timeframe\BuildSemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;

final class EventDatesRetriever implements RetrieveEventDates
{
    public function __construct(
        private readonly BuildSemanticTimeframe $semantic_timeframe_builder,
    ) {
    }

    /**
     * @return Ok<CalendarEventData>|Err<non-falsy-string>
     */
    #[\Override]
    public function retrieveEventDates(
        CalendarEventData $calendar_event_data,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        \Psr\Log\LoggerInterface $logger,
        bool $should_check_permissions,
    ): Ok|Err {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($changeset->getTracker());

        $timeframe_calculator = $semantic_timeframe->getTimeframeCalculator();

        if ($timeframe_calculator instanceof TimeframeImpliedFromAnotherTracker) {
            return Result::err('Timeframe semantic is inherited from another tracker, we cannot build calendar event to be sent by email');
        }

        $permission_user = $should_check_permissions ? $recipient : new \Tracker_UserWithReadAllPermission($recipient);
        $time_period     = $timeframe_calculator->buildDatePeriodWithoutWeekendForChangeset(
            $changeset,
            $permission_user,
            $logger
        );
        $error_message   = $time_period->getErrorMessage();
        if ($error_message) {
            return Result::err('Time period error: ' . $error_message);
        }

        if ($timeframe_calculator->isAllSetToZero($changeset, $permission_user, $logger)) {
            return Result::ok($calendar_event_data->withDates(0, 0));
        }

        $start = $time_period->getStartDate();
        $end   = $time_period->getEndDate();
        if (! $start) {
            return Result::err('No start date, we cannot build calendar event');
        }

        if (! $end) {
            return Result::err('No end date, we cannot build calendar event');
        }

        if ($end < $start) {
            return Result::err('End date < start date, we cannot build calendar event');
        }

        $is_time_displayed = $timeframe_calculator->isTimeDisplayedForEvent();

        return Result::ok($calendar_event_data->withDates($start, $end)->withTimeDisplayed($is_time_displayed));
    }
}
