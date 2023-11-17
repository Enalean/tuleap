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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tuleap\Mail\MailAttachment;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventSummary;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;
use Tuleap\Tracker\Semantic\Timeframe\BuildSemanticTimeframe;

final class EmailNotificationAttachmentProvider implements ProvideEmailNotificationAttachment
{
    public function __construct(
        private readonly CheckEventShouldBeSentInNotification $config,
        private readonly BuildSemanticTimeframe $semantic_timeframe_builder,
        private readonly RetrieveEventSummary $event_summary_retriever,
    ) {
    }

    /**
     * @return MailAttachment[]
     */
    public function getAttachments(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        \Psr\Log\LoggerInterface $logger,
        bool $should_check_permissions,
    ): array {
        if (! $this->config->shouldSendEventInNotification($changeset->getTracker()->getId())) {
            return [];
        }

        $logger->debug('Tracker is configured to send calendar events alongside notification');

        return $this->event_summary_retriever->getEventSummary($changeset, $recipient, $should_check_permissions)
            ->andThen(fn (string $summary) => $this->getCalendarEventData($summary, $changeset, $recipient, $logger))
            ->andThen(fn (CalendarEventData $event_data) => $this->getCalendarEventAsAttachments($event_data, $logger))
            ->match(
                static fn (array $attachments) => $attachments,
                static function (string $debug_message) use ($logger): array {
                    $logger->debug($debug_message);

                    return [];
                }
            );
    }

    /**
     * @return Ok<MailAttachment[]>|Err<non-empty-string>
     */
    private function getCalendarEventAsAttachments(CalendarEventData $event_data, \Psr\Log\LoggerInterface $logger): Ok|Err
    {
        $logger->debug('Found a calendar event for this changeset');

        return Result::ok([]);
    }

    /**
     * @return Ok<CalendarEventData>|Err<non-falsy-string>
     */
    private function getCalendarEventData(
        string $summary,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        \Psr\Log\LoggerInterface $logger,
    ): Ok|Err {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($changeset->getTracker());

        $timeframe_calculator = $semantic_timeframe->getTimeframeCalculator();

        $time_period   = $timeframe_calculator->buildDatePeriodWithoutWeekendForChangeset($changeset, $recipient, $logger);
        $error_message = $time_period->getErrorMessage();
        if ($error_message) {
            return Result::err('Time period error: ' . $error_message);
        }

        $start = $time_period->getStartDate();
        if (! $start) {
            return Result::err('No start date, we cannot build calendar event');
        }

        $end = $time_period->getEndDate();
        if (! $end) {
            return Result::err('No end date, we cannot build calendar event');
        }

        if ($end < $start) {
            return Result::err('End date < start date, we cannot build calendar event');
        }

        return Result::ok(new CalendarEventData($summary, $start, $end));
    }
}
