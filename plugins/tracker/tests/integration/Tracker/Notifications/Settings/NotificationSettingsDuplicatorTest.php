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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Notifications\ConfigNotificationAssignedToDao;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\GlobalNotificationDuplicationDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDuplicationDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDuplicationDao;
use Tuleap\Tracker\TrackerDuplicationUserGroupMapping;

class NotificationSettingsDuplicatorTest extends TestIntegrationTestCase
{
    private NotificationSettingsDuplicator $duplicator;
    private \ParagonIE\EasyDB\EasyDB $db;

    public function setUp(): void
    {
        $db_connection    = DBFactory::getMainTuleapDBConnection();
        $this->db         = $db_connection->getDB();
        $this->duplicator = new NotificationSettingsDuplicator(
            new DBTransactionExecutorWithConnection($db_connection),
            new GlobalNotificationDuplicationDao(),
            new UsersToNotifyDuplicationDao(),
            new UgroupsToNotifyDuplicationDao(),
            new ConfigNotificationAssignedToDao(),
            new ConfigNotificationEmailCustomSenderDao(),
            new DateReminderDao(),
            new CalendarEventConfigDao(),
        );
    }

    public function tearDown(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=1'); // because plugin_tracker_notification_email_custom_sender_format has a constraint we don't want to resolve in tests
    }

    public function testDuplicationOfNotificationsWithRawEmails(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $this->db->insert('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'bob@example.com, foo@example.com', 'all_updates' => 0, 'check_permissions' => 1]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            [],
        );

        $notifications = $this->db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
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

        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => '', 'all_updates' => 1, 'check_permissions' => 0]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            [],
        );

        $notifications = $this->db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $notifications);
        self::assertEmpty($notifications[0]['addresses']);
        self::assertEquals(1, $notifications[0]['all_updates']);
        self::assertEquals(0, $notifications[0]['check_permissions']);

        $ugroups = $this->db->run('SELECT * FROM tracker_global_notification_ugroups WHERE notification_id = ?', $notifications[0]['id']);
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

        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'one_static_ugroup', 'all_updates' => 1, 'check_permissions' => 1]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);
        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'one_dynamic_ugroup', 'all_updates' => 1, 'check_permissions' => 1]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 4]);
        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'both', 'all_updates' => 1, 'check_permissions' => 1]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 3]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 144]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            [],
        );

        // 1. One static user group
        $new_notification_id = $this->db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "one_static_ugroup"', [$new_tracker_id]);
        $ugroups             = $this->db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([333], $ugroups);

        // 2. One dynamic user group
        $new_notification_id = $this->db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "one_dynamic_ugroup"', [$new_tracker_id]);
        $ugroups             = $this->db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([4], $ugroups);

        // 3. both
        $new_notification_id = $this->db->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ? AND addresses = "both"', [$new_tracker_id]);
        $ugroups             = $this->db->column('SELECT ugroup_id FROM tracker_global_notification_ugroups WHERE notification_id = ?', $new_notification_id);
        self::assertEqualsCanonicalizing([555, 3], $ugroups);
    }

    public function testDuplicationOfNotificationsWithUsersFromOneProjectToAnother(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;
        $ugroup_mapping      = [
            122 => 333,
        ];

        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => '', 'all_updates' => 1, 'check_permissions' => 1]);
        $this->db->insert('tracker_global_notification_users', ['notification_id' => $notification_id, 'user_id' => 777]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            [],
        );

        $new_notification_id = $this->db->column('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', [$new_tracker_id]);
        $users               = $this->db->safeQuery('SELECT * FROM tracker_global_notification_users WHERE notification_id = ?', $new_notification_id);
        self::assertIsArray($users);
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

        $notification_id = $this->db->insertReturnId('tracker_global_notification', ['tracker_id' => $template_tracker_id, 'addresses' => 'foo@example.com', 'all_updates' => 1, 'check_permissions' => 1]);
        $this->db->insert('tracker_global_notification_users', ['notification_id' => $notification_id, 'user_id' => 777]);
        $this->db->insert('tracker_global_notification_ugroups', ['notification_id' => $notification_id, 'ugroup_id' => 122]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            [],
        );

        $notifications = $this->db->run('SELECT * FROM tracker_global_notification WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $notifications);
        self::assertEquals('foo@example.com', $notifications[0]['addresses']);

        $ugroups = $this->db->run('SELECT * FROM tracker_global_notification_ugroups WHERE notification_id = ?', $notifications[0]['id']);
        self::assertCount(1, $ugroups);
        self::assertEquals(333, $ugroups[0]['ugroup_id']);

        $users = $this->db->run('SELECT * FROM tracker_global_notification_users WHERE notification_id = ?', $notifications[0]['id']);
        self::assertCount(1, $users);
        self::assertEquals(777, $users[0]['user_id']);
    }

    public function testDuplicationOfAssignedToWhenTemplateUsesIt(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;

        $this->db->insert('plugin_tracker_notification_assigned_to', ['tracker_id' => $template_tracker_id]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            [],
        );

        self::assertTrue($this->db->exists('SELECT 1 FROM plugin_tracker_notification_assigned_to WHERE tracker_id = ?', $new_tracker_id));
    }

    public function testDuplicationOfAssignedToWhenTemplateDoesNotUseIt(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            [],
        );

        self::assertFalse($this->db->exists('SELECT 1 FROM plugin_tracker_notification_assigned_to WHERE tracker_id = ?', $new_tracker_id));
    }

    public function testDuplicationOfExistingCustomSender(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;

        $this->db->run('SET FOREIGN_KEY_CHECKS=0'); // because plugin_tracker_notification_email_custom_sender_format has a constraint we don't want to resolve in tests
        $this->db->insert('plugin_tracker_notification_email_custom_sender_format', ['tracker_id' => $template_tracker_id, 'format' => '%realname', 'enabled' => 1]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            [],
        );

        $rows = $this->db->run('SELECT * FROM plugin_tracker_notification_email_custom_sender_format WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $rows);
        self::assertEquals('%realname', $rows[0]['format']);
        self::assertEquals(1, $rows[0]['enabled']);
    }

    public function testDuplicationOfNoCustomSender(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            [],
        );

        $rows = $this->db->run('SELECT * FROM plugin_tracker_notification_email_custom_sender_format WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(0, $rows);
    }

    public function testDuplicateDateRemindersWithGroups(): void
    {
        $template_tracker_id             = 1;
        $template_tracker_date1_field_id = 55;
        $template_tracker_date2_field_id = 56;
        $new_tracker_id                  = 2;
        $new_tracker_date1_field_id      = 66;
        $new_tracker_date2_field_id      = 67;
        $ugroup_mapping                  = [
            122 => 777,
        ];
        $field_mapping                   = [
            [
                'from'    => $template_tracker_date1_field_id,
                'to'      => $new_tracker_date1_field_id,
                'values'  => [],
                'workflow' => false,
            ],
            [
                'from'    => $template_tracker_date2_field_id,
                'to'      => $new_tracker_date2_field_id,
                'values'  => [],
                'workflow' => false,
            ],
        ];

        $this->db->insert('tracker_reminder', ['tracker_id' => $template_tracker_id, 'field_id' => $template_tracker_date1_field_id, 'ugroups' => '3, 122', 'notification_type' => 0, 'distance' => 2, 'status' => 1, 'notify_closed_artifacts' => 1]);
        $this->db->insert('tracker_reminder', ['tracker_id' => $template_tracker_id, 'field_id' => $template_tracker_date2_field_id, 'ugroups' => '4', 'notification_type' => 1, 'distance' => 3, 'status' => 0, 'notify_closed_artifacts' => 0]);
        $this->db->insert('tracker_reminder', ['tracker_id' => 858, 'field_id' => 777, 'ugroups' => '4', 'notification_type' => 1, 'distance' => 3, 'status' => 0, 'notify_closed_artifacts' => 0]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping),
            $field_mapping,
        );

        $rows = $this->db->run('SELECT * FROM tracker_reminder WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(2, $rows);

        self::assertEquals($new_tracker_date1_field_id, $rows[0]['field_id']);
        self::assertEqualsCanonicalizing(['3', '777'], explode(',', $rows[0]['ugroups']));
        self::assertEquals(0, $rows[0]['notification_type']);
        self::assertEquals(2, $rows[0]['distance']);
        self::assertEquals(1, $rows[0]['status']);
        self::assertEquals(1, $rows[0]['notify_closed_artifacts']);

        self::assertEmpty($this->db->column('SELECT role_id FROM tracker_reminder_notified_roles WHERE reminder_id = ?', [$rows[0]['reminder_id']]));

        self::assertEquals($new_tracker_date2_field_id, $rows[1]['field_id']);
        self::assertEqualsCanonicalizing(['4'], explode(',', $rows[1]['ugroups']));
        self::assertEquals(1, $rows[1]['notification_type']);
        self::assertEquals(3, $rows[1]['distance']);
        self::assertEquals(0, $rows[1]['status']);
        self::assertEquals(0, $rows[1]['notify_closed_artifacts']);

        self::assertEmpty($this->db->column('SELECT role_id FROM tracker_reminder_notified_roles WHERE reminder_id = ?', [$rows[1]['reminder_id']]));
    }

    public function testDuplicateDateRemindersWithRoles(): void
    {
        $template_tracker_id             = 1;
        $template_tracker_date1_field_id = 55;
        $new_tracker_id                  = 2;
        $new_tracker_date1_field_id      = 66;

        $field_mapping = [
            [
                'from'    => $template_tracker_date1_field_id,
                'to'      => $new_tracker_date1_field_id,
                'values'  => [],
                'workflow' => false,
            ],
        ];

        $template_reminder_id = $this->db->insertReturnId('tracker_reminder', ['tracker_id' => $template_tracker_id, 'field_id' => $template_tracker_date1_field_id, 'ugroups' => '', 'notification_type' => 0, 'distance' => 2, 'status' => 1, 'notify_closed_artifacts' => 1]);
        $this->db->insert('tracker_reminder_notified_roles', ['reminder_id' => $template_reminder_id, 'role_id' => \Tracker_DateReminder_Role_Submitter::IDENTIFIER]);
        $this->db->insert('tracker_reminder_notified_roles', ['reminder_id' => $template_reminder_id, 'role_id' => \Tracker_DateReminder_Role_Assignee::IDENTIFIER]);

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            $field_mapping,
        );

        $rows = $this->db->run('SELECT * FROM tracker_reminder WHERE tracker_id = ?', $new_tracker_id);
        self::assertCount(1, $rows);

        self::assertEquals($new_tracker_date1_field_id, $rows[0]['field_id']);
        self::assertEquals('', $rows[0]['ugroups']);
        self::assertEquals(0, $rows[0]['notification_type']);
        self::assertEquals(2, $rows[0]['distance']);
        self::assertEquals(1, $rows[0]['status']);
        self::assertEquals(1, $rows[0]['notify_closed_artifacts']);

        $role_rows = $this->db->column('SELECT role_id FROM tracker_reminder_notified_roles WHERE reminder_id = ?', [$rows[0]['reminder_id']]);
        self::assertEqualsCanonicalizing([\Tracker_DateReminder_Role_Assignee::IDENTIFIER, \Tracker_DateReminder_Role_Submitter::IDENTIFIER], $role_rows);
    }

    public function testDuplicationOfCalendarEventConfig(): void
    {
        $template_tracker_id = 1;
        $new_tracker_id      = 2;

        $this->db->insert(
            'plugin_tracker_calendar_event_config',
            ['tracker_id' => $template_tracker_id, 'should_send_event_in_notification' => 1]
        );

        $this->duplicator->duplicate(
            $template_tracker_id,
            $new_tracker_id,
            TrackerDuplicationUserGroupMapping::fromNewProjectWithMapping([]),
            [],
        );

        self::assertSame(
            1,
            $this->db->cell('SELECT should_send_event_in_notification FROM plugin_tracker_calendar_event_config WHERE tracker_id = ?', $new_tracker_id),
        );
    }
}
