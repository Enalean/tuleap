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

namespace Tuleap\Tracker\Notifications\Settings\Administration;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;
use Tuleap\Tracker\Test\Stub\Notifications\Settings\UpdateCalendarConfigStub;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CalendarConfigUpdaterTest extends TestCase
{
    private readonly \Tuleap\Tracker\Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(83)->build();
    }

    public function testNothingIsUpdatedWhenRequestDoesNotAskTo(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build(),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->build(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertFalse($result->value);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }

    public function testDeactivation(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build(),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '0')->build(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertTrue($update_config->hasDeactivateBeenCalled());
    }

    public function testNothingIsUpdatedWhenAlreadyDeactivated(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build(),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '0')->build(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertFalse($result->value);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }

    public function testActivation(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build()->withTitleField(
                StringFieldBuilder::aStringField(1)->inTracker($this->tracker)->build()
            ),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '1')->build(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value);
        self::assertTrue($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }

    public function testNothingIsUpdatedWhenAlreadyActivated(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build(),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '1')->build(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertFalse($result->value);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }

    public function testErrorWhenTryingToActivateWithoutTitle(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                DateFieldBuilder::aDateField(1001)->build(),
                DateFieldBuilder::aDateField(1002)->build(),
            ),
            RetrieveSemanticTitleFieldStub::build(),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '1')->build(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertSame('Semantic title is required for calendar events', $result->error);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }

    public function testErrorWhenTryingToActivateWithoutTimeframe(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured($this->tracker),
            RetrieveSemanticTitleFieldStub::build()->withTitleField(
                StringFieldBuilder::aStringField(1)->inTracker($this->tracker)->build()
            ),
        );

        $result = $updater->updateConfigAccordingToRequest(
            $this->tracker,
            HTTPRequestBuilder::get()->withParam('enable-calendar-events', '1')->build(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertSame('Semantic timeframe is required for calendar events', $result->error);
        self::assertFalse($update_config->hasActivateBeenCalled());
        self::assertFalse($update_config->hasDeactivateBeenCalled());
    }
}
