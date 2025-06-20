<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationLevelExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsDefaultValueWhenValueIsNotAPossibleValueForNotificationLevel(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('notifications_level', '900')
            ->build();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification                 = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsDefaultValueWhenDefaultValueIsProvided(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('notifications_level', Tracker::NOTIFICATIONS_LEVEL_DEFAULT)
            ->build();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification                 = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsNotificationLevelWhenStatusChangeValueIsProvided(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('notifications_level', Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE)
            ->build();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification                 = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE, $notification);
    }

    public function testItReturnsNotificationLevelDisabledWhenProvidedInRequest(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('disable_notifications', '1')
            ->build();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification                 = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DISABLED, $notification);
    }

    public function testItReturnsNotificationDefaultLevelWhenNotificationAreEnabled(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('enable_notifications', '1')
            ->build();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification                 = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }
}
