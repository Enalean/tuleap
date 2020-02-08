<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications\Settings;

require_once __DIR__ . '/../../../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Tracker\Notifications\GlobalNotification;

class UserNotificationSettingsTest extends TestCase
{

    public function testNoNotificationWhenTrackerModeIsNoNotifications(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DISABLED;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNoNotification($notification_settings);
    }

    public function testNoNotificationWhenUserSelectNoNotifications(): void
    {
        $has_unsubscribed = true;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNoNotification($notification_settings);
    }

    public function testNotificationIsInvolvedWhenUserSelectedIsInvolved(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = true;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationIsInvolved($notification_settings);
    }

    public function testNotificationIsInvolvedWhenTrackerDefaultModeIsInvolvedAndUserHasNoChoice(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationIsInvolved($notification_settings);
    }

    public function testNotificationOnStatusUpdateWhenUserSelectedStatusUpdate(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = true;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationStatusUpdate($notification_settings);
    }

    public function testNotificationOnStatusUpdateWhenTrackerDefaultModeIsStatusUpdateAndUserHasNoChoice(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
        $global_notifications = [];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationStatusUpdate($notification_settings);
    }

    public function testNotificationOnAllUpdatesWhenUserSelectedAllUpdates(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [new GlobalNotification(true)];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationAllUpdates($notification_settings);
    }

    public function testNotificationOnCreationWhenUserSelectedAllUpdates(): void
    {
        $has_unsubscribed = false;
        $is_only_on_status_update = false;
        $is_involved = false;
        $tracker_notification_level = Tracker::NOTIFICATIONS_LEVEL_DEFAULT;
        $global_notifications = [new GlobalNotification(false)];

        $notification_settings = new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker_notification_level,
            ...$global_notifications
        );

        $this->assertNotificationOnCreate($notification_settings);
    }

    private function assertNoNotification(UserNotificationSettings $notification_settings): void
    {
        $this->assertTrue($notification_settings->isInNoNotificationAtAllMode());
        $this->assertFalse($notification_settings->isInNoGlobalNotificationMode());
        $this->assertFalse($notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertFalse($notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertFalse($notification_settings->isInNotifyOnStatusChange());
    }

    private function assertNotificationIsInvolved(UserNotificationSettings $notification_settings): void
    {
        $this->assertFalse($notification_settings->isInNoNotificationAtAllMode());
        $this->assertTrue($notification_settings->isInNoGlobalNotificationMode());
        $this->assertFalse($notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertFalse($notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertFalse($notification_settings->isInNotifyOnStatusChange());
    }

    private function assertNotificationStatusUpdate(UserNotificationSettings $notification_settings): void
    {
        $this->assertFalse($notification_settings->isInNoNotificationAtAllMode());
        $this->assertFalse($notification_settings->isInNoGlobalNotificationMode());
        $this->assertFalse($notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertFalse($notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertTrue($notification_settings->isInNotifyOnStatusChange());
    }

    private function assertNotificationAllUpdates(UserNotificationSettings $notification_settings): void
    {
        $this->assertFalse($notification_settings->isInNoNotificationAtAllMode());
        $this->assertFalse($notification_settings->isInNoGlobalNotificationMode());
        $this->assertFalse($notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertTrue($notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertFalse($notification_settings->isInNotifyOnStatusChange());
    }

    private function assertNotificationOnCreate(UserNotificationSettings $notification_settings): void
    {
        $this->assertFalse($notification_settings->isInNoNotificationAtAllMode());
        $this->assertFalse($notification_settings->isInNoGlobalNotificationMode());
        $this->assertTrue($notification_settings->isInNotifyOnArtifactCreationMode());
        $this->assertFalse($notification_settings->isInNotifyOnEveryChangeMode());
        $this->assertFalse($notification_settings->isInNotifyOnStatusChange());
    }
}
