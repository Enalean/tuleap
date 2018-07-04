<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;

class NotificationLevelExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsDefaultValueWhenValueIsNotAPossibleValueForNotificationLevel()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('exist')->with('disable_notifications')->once()->andReturn(false);
        $request->shouldReceive('exist')->with('enable_notifications')->once()->andReturn(false);
        $request->shouldReceive('get')->with('notifications_level')->once()->andReturn('900');

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsDefaultValueWhenDefaultValueIsProvided()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('exist')->with('disable_notifications')->once()->andReturn(false);
        $request->shouldReceive('exist')->with('enable_notifications')->once()->andReturn(false);
        $request->shouldReceive('get')->with('notifications_level')->once()->andReturn(Tracker::NOTIFICATIONS_LEVEL_DEFAULT);

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsNotificationLevelWhenStatusChangeValueIsProvided()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('exist')->with('disable_notifications')->once()->andReturn(false);
        $request->shouldReceive('exist')->with('enable_notifications')->once()->andReturn(false);
        $request->shouldReceive('get')->with('notifications_level')->once()->andReturn(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE);

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE, $notification);
    }

    public function testItReturnsNotificationLevelDisabledWhenProvidedInRequest()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('exist')->with('disable_notifications')->once()->andReturn(true);
        $request->shouldReceive('exist')->with('enable_notifications')->never();
        $request->shouldReceive('get')->with('notifications_level')->never();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DISABLED, $notification);
    }

    public function testItReturnsNotificationDefaultLevelWhenNotificationAreEnabled()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('exist')->with('disable_notifications')->once()->andReturn(false);
        $request->shouldReceive('exist')->with('enable_notifications')->once()->andReturn(true);
        $request->shouldReceive('get')->with('notifications_level')->never();

        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel($request);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }
}
