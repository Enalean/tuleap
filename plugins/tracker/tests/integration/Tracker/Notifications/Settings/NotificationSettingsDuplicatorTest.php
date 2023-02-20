<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Notifications\Settings;

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\GlobalNotificationDuplicationDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDuplicationDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDuplicationDao;
use Tuleap\Tracker\TrackerDuplicationUserGroupMapping;

class NotificationSettingsDuplicatorTest extends TestCase
{
    private NotificationSettingsDuplicator $duplicator;

    public function setUp(): void
    {
        $this->duplicator = new NotificationSettingsDuplicator(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new GlobalNotificationDuplicationDao(),
            new UsersToNotifyDuplicationDao(),
            new UgroupsToNotifyDuplicationDao(),
        );
    }

    public function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->query('TRUNCATE TABLE tracker_global_notification_ugroups');
        $db->query('TRUNCATE TABLE tracker_global_notification_users');
        $db->query('TRUNCATE TABLE tracker_global_notification');
    }

    public function testDuplicationOfNotificationsWithRawEmails(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'bob@example.com, foo@example.com', 'all_updates' => 0, 'check_permissions' => 1]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping)
        );

        $notifications = $db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $notifications);
        self::assertEquals('bob@example.com, foo@example.com', $notifications[0]['addresses']);
        self::assertEquals(0, $notifications[0]['all_updates']);
        self::assertEquals(1, $notifications[0]['check_permissions']);
    }

    public function testDuplicationOfNotificationsWithUserGroupsFromOneProjectToAnother(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => '', 'all_updates' => 1, 'check_permissions' => 0]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping)
        );

        $notifications = $db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $notifications);
        self::assertEmpty($notifications[0]['addresses']);
        self::assertEquals(1, $notifications[0]['all_updates']);
        self::assertEquals(0, $notifications[0]['check_permissions']);

        $ugroups = $db->run('SELECT * FROM tracker_global_notification_ugroups WHERE notification_id = ?', $notifications[0]['id']);
        self::assertCount(1, $ugroups);
        self::assertEquals(333, $ugroups[0]['ugroup_id']);
    }

    public function testDuplicationOfNotificationsWithDynamicAndStaticUserGroupsFromOneProjectToAnother(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
            133 => 444,
            144 => 555,
        ];

        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'one_static_ugroup', 'all_updates' => 1, 'check_permissions' => 1]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'one_dynamic_ugroup', 'all_updates' => 1, 'check_permissions' => 1]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 4]);
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'both', 'all_updates' => 1, 'check_permissions' => 1]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 3]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 144]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping)
        );

        // 1. One static user group
        $new_notification_id = $db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "one_static_ugroup"', [$new_tracker_id]);
        $ugroups             = $db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([333], $ugroups);

        // 2. One dynamic user group
        $new_notification_id = $db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "one_dynamic_ugroup"', [$new_tracker_id]);
        $ugroups             = $db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([4], $ugroups);

        // 3. both
        $new_notification_id = $db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "both"', [$new_tracker_id]);
        $ugroups             = $db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([555, 3], $ugroups);
    }

    public function testDuplicationOfNotificationsWithUsersFromOneProjectToAnother(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => '', 'all_updates' => 1, 'check_permissions' => 1]);
        $db->insert('tracker_global_notification_users', ['notification_id' => $notification_id, 'user_id' => 777]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping)
        );

        $new_notification_id = $db->column('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', [$new_tracker_id]);
        $users               = $db->safeQuery('SELECT * FROM tracker_global_notification_users WHERE notification_id = ?', $new_notification_id);
        self::assertCount(1, $users);
        self::assertEquals(777, $users[0]['user_id']);
    }

    public function testDuplicationOfOneNotificationWithEmailUserAndUserGroup(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $notification_id = $db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'foo@example.com', 'all_updates' => 1, 'check_permissions' => 1]);
        $db->insert('tracker_global_notification_users', ['notification_id' => $notification_id, 'user_id' => 777]);
        $db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping)
        );

        $notifications = $db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $notifications);
        self::assertEquals('foo@example.com', $notifications[0]['addresses']);

        $ugroups = $db->run('SELECT * FROM tracker_global_notification_ugroups WHERE notification_id = ?', $notifications[0]['id']);
        self::assertCount(1, $ugroups);
        self::assertEquals(333, $ugroups[0]['ugroup_id']);

        $users = $db->run('SELECT * FROM tracker_global_notification_users WHERE notification_id = ?', $notifications[0]['id']);
        self::assertCount(1, $users);
        self::assertEquals(777, $users[0]['user_id']);
    }
}
