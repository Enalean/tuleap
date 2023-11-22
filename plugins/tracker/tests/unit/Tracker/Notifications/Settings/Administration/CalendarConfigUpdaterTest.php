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
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\UpdateCalendarConfigStub;
use Tuleap\Tracker\Test\Stub\Tracker\Semantic\Timeframe\BuildSemanticTimeframeStub;

final class CalendarConfigUpdaterTest extends TestCase
{
    private readonly \Tracker_Semantic_Title & \PHPUnit\Framework\MockObject\MockObject $semantic_title;
    private readonly \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker        = TrackerTestBuilder::aTracker()->build();
        $this->semantic_title = $this->createMock(\Tracker_Semantic_Title::class);
        \Tracker_Semantic_Title::setInstance($this->semantic_title, $this->tracker);
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
    }

    public function testNothingIsUpdatedWhenRequestDoesNotAskTo(): void
    {
        $update_config = UpdateCalendarConfigStub::build();

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
            ),
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
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
            ),
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
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
            ),
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

        \Tracker_Semantic_Title::setInstance(
            new \Tracker_Semantic_Title(
                $this->tracker,
                TrackerFormElementTextFieldBuilder::aTextField(1)->build()
            ),
            $this->tracker,
        );

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
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
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
            ),
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

        \Tracker_Semantic_Title::setInstance(new \Tracker_Semantic_Title($this->tracker, null), $this->tracker);

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate(
                $this->tracker,
                TrackerFormElementDateFieldBuilder::aDateField(1001)->build(),
                TrackerFormElementDateFieldBuilder::aDateField(1002)->build(),
            ),
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

        \Tracker_Semantic_Title::setInstance(
            new \Tracker_Semantic_Title(
                $this->tracker,
                TrackerFormElementTextFieldBuilder::aTextField(1)->build()
            ),
            $this->tracker,
        );

        $updater = new CalendarConfigUpdater(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
            $update_config,
            BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured($this->tracker),
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
