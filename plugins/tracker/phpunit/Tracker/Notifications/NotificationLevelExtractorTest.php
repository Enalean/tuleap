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

use PHPUnit\Framework\TestCase;
use Tracker;

class NotificationLevelExtractorTest extends TestCase
{
    public function testItReturnsDefaultValueWhenValueIsNotAPossibleValueForNotificationLevel()
    {
        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel(900);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsDefaultValueWhenDefaultValueIsProvide()
    {
        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel(Tracker::NOTIFICATIONS_LEVEL_DEFAULT);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DEFAULT, $notification);
    }

    public function testItReturnsNotificationLevelWhenCorrectValueIsProvided()
    {
        $notification_level_extractor = new NotificationLevelExtractor();
        $notification = $notification_level_extractor->extractNotificationLevel(Tracker::NOTIFICATIONS_LEVEL_DISABLED);

        $this->assertEquals(Tracker::NOTIFICATIONS_LEVEL_DISABLED, $notification);
    }
}
