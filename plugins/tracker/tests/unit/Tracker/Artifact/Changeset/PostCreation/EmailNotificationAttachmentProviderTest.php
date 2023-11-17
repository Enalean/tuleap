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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventSummaryStub;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;
use Tuleap\Tracker\Test\Stub\Tracker\Semantic\Timeframe\BuildSemanticTimeframeStub;

final class EmailNotificationAttachmentProviderTest extends TestCase
{
    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private readonly TestLogger $logger;
    private \Tracker_FormElement_Field_Date|MockObject $start_field;
    private \Tracker_FormElement_Field_Date|MockObject $end_field;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();
        $this->logger    = new TestLogger();

        $this->start_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $this->end_field   = $this->createMock(\Tracker_FormElement_Field_Date::class);
    }

    public function testNoAttachmentsWhenTrackerIsNotConfiguredTo(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->changeset->getTracker(),
                $this->start_field,
                $this->end_field,
            ),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, true);

        self::assertEmpty($attachements);
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testNoAttachmentsWhenRetrievalOfSummaryIsInError(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->changeset->getTracker(),
                $this->start_field,
                $this->end_field,
            ),
            RetrieveEventSummaryStub::withError('Error retrieving summary'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, true);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Error retrieving summary',
        );
    }

    public function testNoAttachmentsWhenTimeframeSemanticIsNotConfigured(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured(
                $this->changeset->getTracker(),
            ),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, false);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Time period error: Semantic Timeframe is not configured for tracker bug.',
        );
    }

    public function testNoAttachmentsWhenTimeframeSemanticIsInvalid(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeSemanticConfigInvalid(
                $this->changeset->getTracker(),
            ),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, false);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Time period error: It is inherited from a tracker of another project, this is not allowed',
        );
    }

    /**
     * @testWith [null, 123,  "No start date, we cannot build calendar event"]
     *           [0,    123,  "No start date, we cannot build calendar event"]
     *           [123,  null, "No end date, we cannot build calendar event"]
     *           [123,  0,    "No end date, we cannot build calendar event"]
     *           [123,  120,  "End date < start date, we cannot build calendar event"]
     */
    public function testNoAttachmentsWhenDatesAreConsideredInvalid(?int $start, ?int $end, string $expected_message): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                IComputeTimeframesStub::fromStartAndEndDates(
                    DatePeriodWithoutWeekEnd::buildFromEndDate($start, $end, new NullLogger()),
                    $this->start_field,
                    $this->end_field,
                )
            ),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, false);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            $expected_message,
        );
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [true, true]
     */
    public function testNoAttachmentsWhenEverythingIsAwesomeBecauseFeatureIsNotImplementedYet(bool $user_can_read, bool $should_check_permissions): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                IComputeTimeframesStub::fromStartAndEndDates(
                    DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                    $this->start_field,
                    $this->end_field,
                )
            ),
            RetrieveEventSummaryStub::withSummary('Christmas Party'),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger, $should_check_permissions);

        self::assertEmpty($attachements);
        $this->assertDebugLogEquals(
            'Tracker is configured to send calendar events alongside notification',
            'Found a calendar event for this changeset',
        );
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
