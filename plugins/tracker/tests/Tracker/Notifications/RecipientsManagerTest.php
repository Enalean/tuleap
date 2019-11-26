<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Mockery;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettings;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;

require_once __DIR__.'/../../bootstrap.php';

class RecipientsManagerTest extends \TuleapTestCase
{
    /**
     * @var UserNotificationOnlyStatusChangeDAO
     */
    private $user_status_change_only_dao;

    /**
     * @var UserNotificationSettingsRetriever
     */
    private $notification_settings_retriever;
    /**
     * @var RecipientsManager
     */
    private $recipients_manager;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var UnsubscribersNotificationDAO
     */
    private $unsubscribers_notification_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserNotificationSettings
     */
    private $user_notification_settings;

    public function setUp()
    {
        parent::setUp();

        $this->user_manager                    = mock('\UserManager');
        $this->formelement_factory             = mock('\Tracker_FormElementFactory');
        $this->unsubscribers_notification_dao  = \Mockery::mock(UnsubscribersNotificationDAO::class);
        $this->notification_settings_retriever = mock(UserNotificationSettingsRetriever::class);
        $this->user_status_change_only_dao     = \Mockery::spy(UserNotificationOnlyStatusChangeDAO::class);
        $this->recipients_manager              = new RecipientsManager(
            $this->formelement_factory,
            $this->user_manager,
            $this->unsubscribers_notification_dao,
            $this->notification_settings_retriever,
            $this->user_status_change_only_dao
        );

        stub($this->user_manager)->getUserByUserName('recipient1')->returns(
            aUser()->withId(101)->build()
        );
        stub($this->user_manager)->getUserByUserName('recipient2')->returns(
            aUser()->withId(102)->build()
        );
        stub($this->user_manager)->getUserByUserName('recipient3')->returns(
            aUser()->withId(103)->build()
        );

        $this->user_notification_settings = Mockery::mock(UserNotificationSettings::class);
        stub($this->notification_settings_retriever)->getUserNotificationSettings()->returns($this->user_notification_settings);
    }

    public function itReturnsRecipientsFromField(): void
    {
        $field = mock(Tracker_FormElement_Field_Selectbox::class);
        stub($field)->isNotificationsSupported()->returns(true);
        stub($field)->hasNotifications()->returns(true);
        stub($field)->getRecipients()->returns(['recipient1']);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient1' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itReturnsRecipientsFromCommentators(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            ['recipient2'],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient2' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itReturnsRecipientsFromTrackerConfig(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itCleansUserFromRecipientsWhenUserCantReadAtLeastOneChangedField(): void
    {
        $this->mockADateField(false, false);

        $changeset = $this->getAMockedChangeset(
            true,
            ['recipient2'],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            true,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            [],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itCleansUserFromRecipientsWhenUserHasUnsubscribedFromArtifact(): void
    {
        $field = mock('\Tracker_FormElement_Field_SelectBox');
        stub($field)->isNotificationsSupported()->returns(true);
        stub($field)->hasNotifications()->returns(true);
        stub($field)->getRecipients()->returns(array('recipient1'));
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = $this->getAMockedChangeset(
            true,
            ['recipient2'],
            [102],
            'On going',
            'Review',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient1' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterWhenTrackerIsNotInModeStatusUpdateOnly(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilerWhenStatusChangedAndTrackerIsInStatusChangeOnlyMode(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterAtArtifactCreation(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            ['recipient3'],
            [],
            '',
            '',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => []
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            null
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, false)
        );
    }

    public function itDoesNotFilterUsersWhoAreInGlobalNotificationWithNotificationInEveryStatusChangeChecked(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'On going',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterUsersWhoAreInInvolvedNotificationWithNotificationInEveryStatusChangeChecked(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'On going',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNoGlobalNotificationMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itFilterUsersWhoAreInGlobalNotification(): void
    {
        $this->mockADateField(false, true);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(
            [
                1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
            ]
        );

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(['recipient3']);
        $previous_changeset = mock(Tracker_Artifact_Changeset::class);
        stub($artifact)->getPreviousChangeset()->returns($previous_changeset);
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );
        stub($artifact)->getStatusForChangeset()->returns('On going');
        stub($artifact)->getStatus()->returns('On going');

        $tracker = Mock(Tracker::class);
        stub($tracker)->getRecipients()->returns(
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => []
                ]
            ]
        );
        stub($tracker)->getNotificationsLevel()->returns(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE);
        stub($changeset)->getTracker()->returns($tracker);

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnFalse();
        $this->user_notification_settings->shouldReceive('isInNoGlobalNotificationMode')->andReturnFalse();

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            [],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterIfNoStatusChangeAndTrackerIsInStatusChangeOnlyAndUserSubscribeAllNotifications(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'On going',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itFilterUsersWhoOnlyWantSeeStatusChangeWhenStatusIsNotUpdated(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'On going',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient2', 'recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(102, 36)->returns(true);
        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(103, 36)->returns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterWhenStatusIsUpdated(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient2', 'recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(102, 36)->returns(true);
        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(103, 36)->returns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        $this->assertEqual(
            ['recipient2' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itFiltersUsersWhoOnlyWantSeeNewArtifactsWhenArtifactIsUpdatedAndUserIsInvolved(): void
    {
        $this->mockADateField(false, true);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'On going',
            [
                [
                    'on_updates'        => true,
                    'check_permissions' => true,
                    'recipients'        => ['recipient2', 'recipient3']
                ]
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            mock(Tracker_Artifact_Changeset::class)
        );

        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(102, 36)->returns(false);
        stub($this->user_status_change_only_dao)->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(103, 36)->returns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturn(true, false);

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    private function mockADateField($is_notification_supported, $user_can_read)
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns($is_notification_supported);
        stub($field)->userCanRead()->returns($user_can_read);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);
    }

    private function getAMockedChangeset(
        $has_changed,
        array $artifact_commentators,
        array $notifications_unsubscribers,
        $previeous_changeset_status,
        $artifact_status,
        array $tracker_recipients,
        $tracker_notification_level,
        $has_empty_body,
        $previous_changeset
    ) {
        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(
            [
                1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns($has_changed),
            ]
        );

        $artifact           = stub('\Tracker_Artifact')->getCommentators()->returns($artifact_commentators);
        stub($artifact)->getPreviousChangeset()->returns($previous_changeset);
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns($notifications_unsubscribers);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );
        stub($artifact)->getStatusForChangeset()->returns($previeous_changeset_status);
        stub($artifact)->getStatus()->returns($artifact_status);

        $tracker = Mock(Tracker::class);
        stub($tracker)->getId()->returns(36);
        stub($tracker)->getRecipients()->returns($tracker_recipients);
        stub($tracker)->getNotificationsLevel()->returns($tracker_notification_level);
        stub($changeset)->getTracker()->returns($tracker);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns($has_empty_body)
        );
        return $changeset;
    }

    public function itBuildsAListOfUserIdsFromTheirTrackerNotificationsSettings(): void
    {
        $user_recipients_from_tracker = [
            [
                'recipients'        =>
                    [
                        'noctali@example.com',
                        'aquali@example.com'
                    ],
                'on_updates'        => false,
                'check_permissions' => true
            ]
        ];

        $tracker = mock(\Tracker::class);
        stub($tracker)->getRecipients()->returns($user_recipients_from_tracker);
        stub($this->user_manager)->getUserByEmail('noctali@example.com')->returns(aUser()->withId(101)->build());
        stub($this->user_manager)->getUserByEmail('aquali@example.com')->returns(aUser()->withId(102)->build());

        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerID()->returns([103, 104]);

        stub($this->user_status_change_only_dao)->searchUserIdsHavingSubscribedForTrackerStatusChangedOnly()->returns([105, 106]);

        $all_user_ids = [101, 102, 103, 104, 105, 106];

        $this->assertEqual($all_user_ids, $this->recipients_manager->getAllRecipientsWhoHaveCustomSettingsForATracker($tracker));
    }
}
