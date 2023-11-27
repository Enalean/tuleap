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

use PFUser;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;
use Tuleap\Tracker\Test\Stub\Tracker\Semantic\Timeframe\BuildSemanticTimeframeStub;

final class EventDatesRetrieverTest extends TestCase
{
    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private \Tracker_FormElement_Field_Date $start_field;
    private \Tracker_FormElement_Field_Date $end_field;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();
        $this->logger    = new NullLogger();

        $this->start_field = TrackerFormElementDateFieldBuilder::aDateField(1)->build();
        $this->end_field   = TrackerFormElementDateFieldBuilder::aDateField(2)->build();
    }

    public function testErrorWhenTimeframeSemanticIsNotConfigured(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured(
                $this->changeset->getTracker(),
            ),
        );

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'Time period error: Semantic Timeframe is not configured for tracker bug.',
            (string) $result->error,
        );
    }

    public function testErrorWhenTimeframeSemanticIsInvalid(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeSemanticConfigInvalid(
                $this->changeset->getTracker(),
            ),
        );

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'Time period error: It is inherited from a tracker of another project, this is not allowed',
            (string) $result->error,
        );
    }

    public function testErrorWhenTimeframeSemanticIsImpliedFromAnotherTracker(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                $this->createMock(TimeframeImpliedFromAnotherTracker::class),
            ),
        );

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'Timeframe semantic is inherited from another tracker, we cannot build calendar event to be sent by email',
            (string) $result->error,
        );
    }

    /**
     * @testWith [null, 123,  "No start date, we cannot build calendar event"]
     *           [0,    123,  "No start date, we cannot build calendar event"]
     *           [123,  null, "No end date, we cannot build calendar event"]
     *           [123,  0,    "No end date, we cannot build calendar event"]
     *           [123,  120,  "End date < start date, we cannot build calendar event"]
     */
    public function testErrorWhenDatesAreConsideredInvalid(?int $start, ?int $end, string $expected_message): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                IComputeTimeframesStub::fromStartAndEndDates(
                    DatePeriodWithoutWeekEnd::buildFromEndDate($start, $end, new NullLogger()),
                    $this->start_field,
                    $this->end_field,
                )
            ),
        );

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            $expected_message,
            (string) $result->error,
        );
    }

    public function testDatesAreReturnedEvenIfUserCannotReadDateFields(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                new TimeframeWithEndDate(
                    $this->start_field,
                    $this->end_field,
                )
            ),
        );

        $this->start_field->setUserCanRead($this->recipient, false);
        $this->end_field->setUserCanRead($this->recipient, false);

        $this->changeset->setFieldValue($this->start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->start_field
        )->withTimestamp(1234567890)->build());
        $this->changeset->setFieldValue($this->end_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->end_field
        )->withTimestamp(1324567890)->build());

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            false,
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new CalendarEventData('Christmas Party', '', 1234567890, 1324567890, null),
            $result->value,
        );
    }

    /**
     * @testWith [false, false, false, "end"]
     *           [false, false, true, "start"]
     *           [false, true, false, "end"]
     *           [false, true, true, "end"]
     *           [true, false, false, "start"]
     *           [true, false, true, "start"]
     *           [true, true, false, "start"]
     *           [true, true, true, "start"]
     */
    public function testItReturnsErrWhenDateFieldIsZeroAndCheckPerms(bool $start_date, bool $can_read, bool $check_permissions, string $error_message): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                new TimeframeWithEndDate(
                    $this->start_field,
                    $this->end_field,
                )
            ),
        );

        $this->start_field->setUserCanRead($this->recipient, $can_read);
        $this->end_field->setUserCanRead($this->recipient, $can_read);

        $this->changeset->setFieldValue($this->start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->start_field
        )->withTimestamp($start_date ? 0 : 1234567890)->build());
        $this->changeset->setFieldValue($this->end_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->end_field
        )->withTimestamp($start_date ? 1324567890 : 0)->build());

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            $check_permissions,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            "No $error_message date, we cannot build calendar event",
            (string) $result->error
        );
    }

    public function testErrorWhenUserCannotReadFields(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                new TimeframeWithEndDate(
                    $this->start_field,
                    $this->end_field,
                )
            ),
        );

        $this->start_field->setUserCanRead($this->recipient, false);
        $this->end_field->setUserCanRead($this->recipient, false);

        $this->changeset->setFieldValue($this->start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->start_field
        )->withTimestamp(1234567890)->build());
        $this->changeset->setFieldValue($this->end_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->end_field
        )->withTimestamp(1324567890)->build());

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'No start date, we cannot build calendar event',
            (string) $result->error,
        );
    }

    public function testDatesAreReturnedEvenIfTimeframeIsZero(): void
    {
        $duration_field = TrackerFormElementIntFieldBuilder::anIntField(3)->build();
        $builder        = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                new TimeframeWithDuration(
                    $this->start_field,
                    $duration_field,
                )
            ),
        );

        $this->changeset->setFieldValue($this->start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $this->changeset,
            $this->start_field
        )->withTimestamp(0)->build());
        $this->changeset->setFieldValue($duration_field, ChangesetValueIntegerTestBuilder::aValue(
            1,
            $this->changeset,
            $this->end_field
        )->withValue(0)->build());

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            false,
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new CalendarEventData('Christmas Party', '', 0, 0, null),
            $result->value,
        );
    }

    public function testDatesAreReturnedWhenEverythingIsFine(): void
    {
        $builder = new EventDatesRetriever(
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                $this->changeset->getTracker(),
                IComputeTimeframesStub::fromStartAndEndDates(
                    DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                    $this->start_field,
                    $this->end_field,
                )
            ),
        );

        $result = $builder->retrieveEventDates(
            CalendarEventData::fromSummary('Christmas Party')
                ->withDescription('Ho ho ho, Merry Christmas!'),
            $this->changeset,
            $this->recipient,
            $this->logger,
            true,
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new CalendarEventData('Christmas Party', 'Ho ho ho, Merry Christmas!', 1234567890, 1324567890, null),
            $result->value,
        );
    }
}
