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

namespace Tuleap\Tracker\Notifications\Settings;

require_once __DIR__ . '/../../../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Notifications\GlobalNotification;

class UserNotificationSettingsTest extends TestCase
{
    /**
     * @dataProvider notificationModeProvider
     */
    public function testNotificationSettingsMode(
        $has_unsubscribed,
        $is_only_on_status_update,
        array $global_notifications,
        $expected_no_notification_at_all_mode,
        $expected_no_global_notification_mode,
        $expected_notify_on_artifact_creation_mode,
        $expected_notify_on_every_change_mode
    ) {
        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            ...$global_notifications
        );

        $this->assertEquals($expected_no_notification_at_all_mode, $notification_settings->isInNoNotificationAtAllMode());
        $this->assertEquals($expected_no_global_notification_mode, $notification_settings->isInNoGlobalNotificationMode());
        $this->assertEquals($expected_notify_on_artifact_creation_mode, $notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertEquals($expected_notify_on_every_change_mode, $notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertEquals($is_only_on_status_update, $notification_settings->isInNotifyOnStatusChange());
    }

    public function notificationModeProvider()
    {
        $global_notification = \Mockery::mock(GlobalNotification::class);
        $global_notification->shouldReceive('isOnAllUpdates')->andReturn(false);
        $global_notification_all_updates = \Mockery::mock(GlobalNotification::class);
        $global_notification_all_updates->shouldReceive('isOnAllUpdates')->andReturn(true);

        return [
            [true, false, [$global_notification], true, false, false, false],
            [false, false, [], false, true, false, false],
            [false, false, [$global_notification], false, false, true, false],
            [false, false, [$global_notification, $global_notification_all_updates], false, false, false, true],
            [false, false, [$global_notification, $global_notification_all_updates], false, false, false, true],
            [false, true, [], false, true, false, false],
        ];
    }
}
