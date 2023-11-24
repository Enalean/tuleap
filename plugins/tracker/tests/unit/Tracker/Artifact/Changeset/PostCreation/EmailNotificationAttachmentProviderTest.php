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

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventDatesStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventDescriptionStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventOrganizerStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventSummaryStub;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;

final class EmailNotificationAttachmentProviderTest extends TestCase
{
    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private readonly TestLogger $logger;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();
        $this->logger    = new TestLogger();
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testNoAttachmentsWhenTrackerIsNotConfiguredTo(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            RetrieveEventSummaryStub::shouldNotBeCalled(),
            RetrieveEventDescriptionStub::shouldNotBeCalled(),
            RetrieveEventDatesStub::shouldNotBeCalled(),
            RetrieveEventOrganizerStub::shouldNotBeCalled(),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertEmpty($attachements);
        self::assertFalse($this->logger->hasDebugRecords());
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testNoAttachmentsWhenRetrievalOfSummaryIsInError(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withError('Error retrieving summary'),
            RetrieveEventDescriptionStub::shouldNotBeCalled(),
            RetrieveEventDatesStub::shouldNotBeCalled(),
            RetrieveEventOrganizerStub::shouldNotBeCalled(),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Error retrieving summary',
        );
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testNoAttachmentsWhenRetrievalOfDatesIsInError(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withError('Error building calendar data'),
            RetrieveEventOrganizerStub::shouldNotBeCalled(),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Error building calendar data',
        );
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentWhenEverythingIsAwesome(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(1234567890, 1324567890),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply')
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachements);
        self::assertSame('event.ics', $attachements[0]->filename);
        self::assertSame('text/calendar', $attachements[0]->mime_type);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Found a calendar event for this changeset',
        );
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentContainsAnEventSummary(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(1234567890, 1324567890),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachements);
        self::assertStringContainsString('SUMMARY:Christmas Party', $attachements[0]->content);
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentContainsAnEventStartAndEndDateIgnoringTimezoneForNow(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(1234567890, 1324567890),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachements);
        $begining_of_file = '%a';
        $end_of_file      = '%a';
        $timezone         = '%S';
        self::assertStringMatchesFormat("${begining_of_file}DTSTART;${timezone}VALUE=DATE:20090214${end_of_file}", $attachements[0]->content);
        self::assertStringMatchesFormat("${begining_of_file}DTEND;${timezone}VALUE=DATE:20111222${end_of_file}", $attachements[0]->content);
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentDoesNotContainMethodRequestBecauseYesMaybeNoActionsWillSendAnEmailToTheNoreplyOrganizerWhichCanEndUpInErrorInSomeEnvironment(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(1234567890, 1324567890),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachements);
        self::assertStringNotContainsString('METHOD:REQUEST', $attachements[0]->content);
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentsMethodCancelWhenStartDateAndEndDateAreZero(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(0, 0),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply'),
        );

        $attachments = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachments);
        self::assertStringContainsString('METHOD:CANCEL', $attachments[0]->content);
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testAttachmentsContainsAnOrganizer(bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
            RetrieveEventDescriptionStub::withDescription('Ho ho ho, Merry Christmas!'),
            RetrieveEventDatesStub::withDates(1234567890, 1324567890),
            RetrieveEventOrganizerStub::withOrganizer('noreply@example.com', 'Noreply'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertCount(1, $attachements);
        self::assertStringContainsString('ORGANIZER;CN=Noreply:MAILTO:noreply@example.com', $attachements[0]->content);
    }

    private function assertDebugLogEquals(string $message, string ...$other_messages): void
    {
        self::assertEquals(
            array_map(
                static fn(string $message) => ['level' => 'debug', 'message' => $message, 'context' => []],
                [$message, ...$other_messages]
            ),
            $this->logger->records,
        );
    }
}
