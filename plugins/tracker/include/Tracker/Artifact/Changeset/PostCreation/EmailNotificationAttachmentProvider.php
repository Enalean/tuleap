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

use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Properties\TextProperty;
use Tuleap\Mail\MailAttachment;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\ServerHostname;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\BuildCalendarEventData;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\CalendarEventData;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventSummary;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;

final class EmailNotificationAttachmentProvider implements ProvideEmailNotificationAttachment
{
    public function __construct(
        private readonly CheckEventShouldBeSentInNotification $config,
        private readonly BuildCalendarEventData $event_data_builder,
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
            ->andThen(fn (string $summary) => $this->event_data_builder->getCalendarEventData($summary, $changeset, $recipient, $logger, $should_check_permissions))
            ->andThen(fn (CalendarEventData $event_data) => $this->getCalendarEventAsAttachments($event_data, $changeset, $logger))
            ->match(
                static fn (array $attachments) => $attachments,
                static function (string $debug_message) use ($logger): array {
                    $logger->debug($debug_message);

                    return [];
                }
            );
    }

    /**
     * @return Ok<list{MailAttachment}>|Err<non-empty-string>
     */
    private function getCalendarEventAsAttachments(
        CalendarEventData $event_data,
        \Tracker_Artifact_Changeset $changeset,
        \Psr\Log\LoggerInterface $logger,
    ): Ok|Err {
        $logger->debug('Found a calendar event for this changeset');

        $event = Event::create($event_data->summary)
            ->uniqueIdentifier('tracker-artifact-' . $changeset->getArtifact()->getId() . '@' . ServerHostname::rawHostname())
            ->startsAt((new \DateTimeImmutable())->setTimestamp($event_data->start))
            ->endsAt((new \DateTimeImmutable())->setTimestamp($event_data->end))
            ->fullDay()
            ->appendProperty(TextProperty::create('SEQUENCE', (string) $changeset->getId()));

        $calendar = Calendar::create()
            ->event($event);

        if ($event_data->start === 0 && $event_data->end === 0) {
            $calendar->appendProperty(TextProperty::create('METHOD', 'CANCEL'));
        } else {
            $calendar->appendProperty(TextProperty::create('METHOD', 'REQUEST'));
        }

        return Result::ok([
            new MailAttachment(
                'text/calendar',
                'event.ics',
                $calendar->get(),
            ),
        ]);
    }
}
