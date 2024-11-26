<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
use PFUser;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Notifications\Recipient\MentionedUserInCommentRetriever;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettings;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetCommentTestBuilder;
use UserManager;

/**
 * @covers \Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInCreationOnlyMode
 */
final class RecipientsManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserNotificationOnlyStatusChangeDAO
     */
    private $user_status_change_only_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserNotificationSettingsRetriever
     */
    private $notification_settings_retriever;
    /**
     * @var RecipientsManager
     */
    private $recipients_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnsubscribersNotificationDAO
     */
    private $unsubscribers_notification_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserNotificationSettings
     */
    private $user_notification_settings;

    protected function setUp(): void
    {
        $this->user_manager                    = Mockery::mock(UserManager::class);
        $this->formelement_factory             = Mockery::spy(Tracker_FormElementFactory::class);
        $this->unsubscribers_notification_dao  = Mockery::mock(UnsubscribersNotificationDAO::class);
        $this->notification_settings_retriever = Mockery::spy(UserNotificationSettingsRetriever::class);
        $this->user_status_change_only_dao     = Mockery::spy(UserNotificationOnlyStatusChangeDAO::class);
        $this->recipients_manager              = new RecipientsManager(
            $this->formelement_factory,
            $this->user_manager,
            $this->unsubscribers_notification_dao,
            $this->notification_settings_retriever,
            $this->user_status_change_only_dao,
            new MentionedUserInCommentRetriever($this->user_manager)
        );

        $this->user_manager->shouldReceive('getUserByUserName')->with('recipient1')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 101,
            ])
        );
        $this->user_manager->shouldReceive('getUserByUserName')->with('recipient2')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 102,
            ])
        );
        $this->user_manager->shouldReceive('getUserByUserName')->with('recipient3')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 103,
            ])
        );

        $this->user_notification_settings = Mockery::mock(UserNotificationSettings::class);
        $this->notification_settings_retriever->shouldReceive('getUserNotificationSettings')->andReturns($this->user_notification_settings);
    }

    public function testItReturnsRecipientsFromField(): void
    {
        $field = $this->getSelectBox();
        $field->shouldReceive('isNotificationsSupported')->andReturns(true);
        $field->shouldReceive('hasNotifications')->andReturns(true);
        $field->shouldReceive('getRecipients')->andReturns(['recipient1']);
        $field->shouldReceive('userCanRead')->andReturns(true);
        $this->formelement_factory->shouldReceive('getFieldById')->with(1)->andReturns($field);

        $changeset = $this->getAMockedChangeset(
            true,
            [],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient1' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromCommentators(): void
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
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient2' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromTrackerConfig(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenTheyCannotReadTheArtifact(): void
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
            true,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );
        $changeset->shouldReceive('hasChanged')->andReturn(true);

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenUserCantReadAtLeastOneChangedField(): void
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
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );
        $changeset->shouldReceive('hasChanged')->andReturn(true);

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenUserHasUnsubscribedFromArtifact(): void
    {
        $field = $this->getSelectBox();
        $field->shouldReceive('isNotificationsSupported')->andReturns(true);
        $field->shouldReceive('hasNotifications')->andReturns(true);
        $field->shouldReceive('getRecipients')->andReturns(['recipient1']);
        $field->shouldReceive('userCanRead')->andReturns(true);
        $this->formelement_factory->shouldReceive('getFieldById')->with(1)->andReturns($field);

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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient1' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterWhenTrackerIsNotInModeStatusUpdateOnly(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );
        $artifact  = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilerWhenStatusChangedAndTrackerIsInStatusChangeOnlyMode(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterAtArtifactCreation(): void
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
                    'recipients'        => [],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            null
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, false, new NullLogger())
        );
    }

    public function testItDoesNotFilterUsersWhoAreInGlobalNotificationWithNotificationInEveryStatusChangeChecked(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterUsersWhoAreInInvolvedNotificationWithNotificationInEveryStatusChangeChecked(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNoGlobalNotificationMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItFilterUsersWhoAreInGlobalNotification(): void
    {
        $this->mockADateField(false, true);

        $changeset = Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValues')->andReturns([
            1 => Mockery::spy(
                Tracker_Artifact_ChangesetValue_List::class
            )->shouldReceive('hasChanged')->andReturns(true),
        ]);

        $artifact           = Mockery::spy(Artifact::class)->shouldReceive('getCommentators')->andReturns(['recipient3'])->getMock();
        $previous_changeset = Mockery::spy(Tracker_Artifact_Changeset::class);
        $artifact->shouldReceive('getPreviousChangeset')->andReturns($previous_changeset);
        $this->unsubscribers_notification_dao->shouldReceive('searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID')->andReturns([]);
        $changeset->shouldReceive('getArtifact')->andReturns($artifact);
        $artifact->shouldReceive('getStatusForChangeset')->andReturns('On going');
        $artifact->shouldReceive('getStatus')->andReturns('On going');

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturns(888);
        $tracker->shouldReceive('getRecipients')->andReturns([
            [
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => [],
            ],
        ]);
        $tracker->shouldReceive('getNotificationsLevel')->andReturns(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE);
        $changeset->shouldReceive('getTracker')->andReturns($tracker);

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnFalse();
        $this->user_notification_settings->shouldReceive('isInNoGlobalNotificationMode')->andReturnFalse();

        $changeset->shouldReceive('getComment')->andReturns(
            ChangesetCommentTestBuilder::aComment()->build()
        );

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterIfNoStatusChangeAndTrackerIsInStatusChangeOnlyAndUserSubscribeAllNotifications(): void
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
                    'recipients'        => ['recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_notification_settings->shouldReceive('isInNotifyOnEveryChangeMode')->andReturnTrue();
        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItFilterUsersWhoOnlyWantSeeStatusChangeWhenStatusIsNotUpdated(): void
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
                    'recipients'        => ['recipient2', 'recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(102, 36)->andReturns(true);
        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(103, 36)->andReturns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterWhenStatusIsUpdated(): void
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
                    'recipients'        => ['recipient2', 'recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(102, 36)->andReturns(true);
        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(103, 36)->andReturns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturnFalse();

        self::assertSame(
            ['recipient2' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    public function testItFiltersUsersWhoOnlyWantSeeNewArtifactsWhenArtifactIsUpdatedAndUserIsInvolved(): void
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
                    'recipients'        => ['recipient2', 'recipient3'],
                ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            false,
            Mockery::spy(Tracker_Artifact_Changeset::class)
        );

        $artifact = $changeset->getArtifact();
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(102, 36)->andReturns(false);
        $this->user_status_change_only_dao->shouldReceive('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(103, 36)->andReturns(false);

        $this->user_notification_settings->shouldReceive('isInNotifyOnArtifactCreationMode')->andReturn(true, false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, new NullLogger())
        );
    }

    private function mockADateField($is_notification_supported, $user_can_read)
    {
        $field = Mockery::spy(Tracker_FormElement_Field_Date::class);
        $field->shouldReceive('isNotificationsSupported')->andReturns($is_notification_supported);
        $field->shouldReceive('userCanRead')->andReturns($user_can_read);
        $this->formelement_factory->shouldReceive('getFieldById')->with(1)->andReturns($field);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private function getAMockedChangeset(
        $has_changed,
        array $artifact_commentators,
        array $notifications_unsubscribers,
        $previeous_changeset_status,
        $artifact_status,
        array $tracker_recipients,
        $tracker_notification_level,
        $has_empty_body,
        $previous_changeset,
    ) {
        $changeset       = Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('hasChanged')->andReturns($has_changed);
        $changeset->shouldReceive('getValues')->andReturns([1 => $changeset_value]);

        $artifact = Mockery::spy(Artifact::class)->shouldReceive('getCommentators')->andReturns(
            $artifact_commentators
        )->getMock();
        $artifact->shouldReceive('getPreviousChangeset')->andReturns($previous_changeset);
        $this->unsubscribers_notification_dao->shouldReceive('searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID')->andReturns($notifications_unsubscribers);
        $changeset->shouldReceive('getArtifact')->andReturns($artifact);
        $artifact->shouldReceive('getStatusForChangeset')->andReturns($previeous_changeset_status);
        $artifact->shouldReceive('getStatus')->andReturns($artifact_status);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturns(36);
        $tracker->shouldReceive('getRecipients')->andReturns($tracker_recipients);
        $tracker->shouldReceive('getNotificationsLevel')->andReturns($tracker_notification_level);
        $changeset->shouldReceive('getTracker')->andReturns($tracker);

        $comment_changeset = ChangesetCommentTestBuilder::aComment()->withCommentBody(
            $has_empty_body ? '' : 'some text'
        )->build();
        $changeset->shouldReceive('getComment')->andReturns($comment_changeset);

        $changeset->shouldReceive('getSubmitter')->andReturn(UserTestBuilder::aRandomActiveUser()->build());

        return $changeset;
    }

    public function testItBuildsAListOfUserIdsFromTheirTrackerNotificationsSettings(): void
    {
        $user_recipients_from_tracker = [
            [
                'recipients'        =>
                    [
                        'noctali@example.com',
                        'aquali@example.com',
                    ],
                'on_updates'        => false,
                'check_permissions' => true,
            ],
        ];

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturns(888);
        $tracker->shouldReceive('getRecipients')->andReturns($user_recipients_from_tracker);
        $this->user_manager->shouldReceive('getUserByEmail')->with('noctali@example.com')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 101,
            ])
        );
        $this->user_manager->shouldReceive('getUserByEmail')->with('aquali@example.com')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 102,
            ])
        );

        $this->unsubscribers_notification_dao->shouldReceive('searchUserIDHavingUnsubcribedFromNotificationByTrackerID')->andReturns([103, 104]);

        $this->user_status_change_only_dao->shouldReceive('searchUserIdsHavingSubscribedForTrackerStatusChangedOnly')->andReturns([105, 106]);

        $all_user_ids = [101, 102, 103, 104, 105, 106];

        self::assertSame($all_user_ids, $this->recipients_manager->getAllRecipientsWhoHaveCustomSettingsForATracker($tracker));
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private function getSelectBox()
    {
        return Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
    }
}
