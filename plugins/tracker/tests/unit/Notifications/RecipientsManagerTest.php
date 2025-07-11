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

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientWhenTheyAreInCreationOnlyMode;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettings;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Test\Builders\ChangesetCommentTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[CoversClass(RemoveRecipientWhenTheyAreInCreationOnlyMode::class)]
final class RecipientsManagerTest extends TestCase
{
    private UserNotificationOnlyStatusChangeDAO&MockObject $user_status_change_only_dao;
    private RecipientsManager $recipients_manager;
    private UserManager&MockObject $user_manager;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private UnsubscribersNotificationDAO&MockObject $unsubscribers_notification_dao;
    private UserNotificationSettings&MockObject $user_notification_settings;

    protected function setUp(): void
    {
        $this->user_manager                   = $this->createMock(UserManager::class);
        $this->formelement_factory            = $this->createMock(Tracker_FormElementFactory::class);
        $this->unsubscribers_notification_dao = $this->createMock(UnsubscribersNotificationDAO::class);
        $notification_settings_retriever      = $this->createMock(UserNotificationSettingsRetriever::class);
        $this->user_status_change_only_dao    = $this->createMock(UserNotificationOnlyStatusChangeDAO::class);
        $user_preference_store                = new StoreUserPreferenceStub();
        $this->recipients_manager             = new RecipientsManager(
            $this->formelement_factory,
            $this->user_manager,
            $this->unsubscribers_notification_dao,
            $notification_settings_retriever,
            $this->user_status_change_only_dao,
            new NotificationOnAllUpdatesRetriever($user_preference_store),
            new NotificationOnOwnActionRetriever($user_preference_store),
            new MentionedUserInTextRetriever($this->user_manager),
        );

        $this->user_manager->method('getUserByUserName')->willReturnCallback(static fn(string $username) => match ($username) {
            'recipient1' => UserTestBuilder::anActiveUser()->withId(101)->withUserName('recipient1')->build(),
            'recipient2' => UserTestBuilder::anActiveUser()->withId(102)->withUserName('recipient2')->build(),
            'recipient3' => UserTestBuilder::anActiveUser()->withId(103)->withUserName('recipient3')->build(),
            default      => throw new LogicException("Should not be called with '$username'"),
        });

        $this->user_notification_settings = $this->createMock(UserNotificationSettings::class);
        $notification_settings_retriever->method('getUserNotificationSettings')->willReturn($this->user_notification_settings);
    }

    public function testItReturnsRecipientsFromField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field->method('isNotificationsSupported')->willReturn(true);
        $field->method('hasNotifications')->willReturn(true);
        $field->method('getRecipients')->willReturn(['recipient1']);
        $field->method('userCanRead')->willReturn(true);
        $this->formelement_factory->method('getFieldById')->with(1)->willReturn($field);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient1' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromCommentators(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            ['recipient2'],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient2' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromMentionedUsers(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            '@recipient1 @recipient2',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturnCallback(
            function (\PFUser $user): bool {
                return $user->getUserName() === 'recipient1';
            }
        );

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient1' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromTrackerConfig(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItReturnsRecipientsFromMentionedUsersButSkipSubscribedUsersWhenNotificationsAreDisabled(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient2'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            '@recipient1',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient1' => true],
            $this->recipients_manager->getRecipients($changeset, true, false, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenTheyCannotReadTheArtifact(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            ['recipient2'],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(false);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenUserCantReadAtLeastOneChangedField(): void
    {
        $this->mockADateField(false);

        $changeset = $this->getAMockedChangeset(
            ['recipient2'],
            [],
            'On going',
            'Review',
            [],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            '',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItCleansUserFromRecipientsWhenUserHasUnsubscribedFromArtifact(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field->method('isNotificationsSupported')->willReturn(true);
        $field->method('hasNotifications')->willReturn(true);
        $field->method('getRecipients')->willReturn(['recipient1']);
        $field->method('userCanRead')->willReturn(true);
        $this->formelement_factory->method('getFieldById')->with(1)->willReturn($field);

        $changeset = $this->getAMockedChangeset(
            ['recipient2'],
            [102],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient1' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterWhenTrackerIsNotInModeStatusUpdateOnly(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );
        $artifact  = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilerWhenStatusChangedAndTrackerIsInStatusChangeOnlyMode(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterAtArtifactCreation(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            ['recipient3'],
            [],
            '',
            '',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => [],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            'some text',
            null
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnEveryChangeMode')->willReturn(false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, false, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterUsersWhoAreInGlobalNotificationWithNotificationInEveryStatusChangeChecked(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'On going',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnEveryChangeMode')->willReturn(true);
        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);
        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification');

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterUsersWhoAreInInvolvedNotificationWithNotificationInEveryStatusChangeChecked(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'On going',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNoGlobalNotificationMode')->willReturn(true);
        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);
        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification');

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItFilterUsersWhoAreInGlobalNotification(): void
    {
        $this->mockADateField(true);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([
            1 => $this->createMock(
                Tracker_Artifact_ChangesetValue_List::class
            )->method('hasChanged')->willReturn(true),
        ]);
        $changeset->method('forceFetchAllValues');
        $changeset->method('getId');

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getCommentators')->willReturn(['recipient3']);
        $previous_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $artifact->method('getPreviousChangeset')->willReturn($previous_changeset);
        $this->unsubscribers_notification_dao->method('searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID')->willReturn([]);
        $changeset->method('getArtifact')->willReturn($artifact);
        $artifact->method('getStatusForChangeset')->willReturn('On going');
        $artifact->method('getStatus')->willReturn('On going');

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(888);
        $tracker->method('getRecipients')->willReturn([[
            'on_updates'        => true,
            'check_permissions' => true,
            'recipients'        => [],
        ],
        ]);
        $tracker->method('getNotificationsLevel')->willReturn(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE);
        $changeset->method('getTracker')->willReturn($tracker);

        $this->user_notification_settings->method('isInNotifyOnEveryChangeMode')->willReturn(false);
        $this->user_notification_settings->method('isInNoGlobalNotificationMode')->willReturn(false);

        $changeset->method('getComment')->willReturn(
            ChangesetCommentTestBuilder::aComment()->build()
        );

        self::assertSame(
            [],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterIfNoStatusChangeAndTrackerIsInStatusChangeOnlyAndUserSubscribeAllNotifications(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'On going',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_notification_settings->method('isInNotifyOnEveryChangeMode')->willReturn(true);
        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);
        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification');

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItFilterUsersWhoOnlyWantSeeStatusChangeWhenStatusIsNotUpdated(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'On going',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient2', 'recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')
            ->with(self::anything(), 36)
            ->willReturnCallback(static fn(int $user_id, int $tracker_id) => $user_id === 102);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItDoesNotFilterWhenStatusIsUpdated(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'Review',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient2', 'recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(102, 36)->willReturn(true);
        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')->with(103, 36)->willReturn(false);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(false);

        self::assertSame(
            ['recipient2' => true, 'recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    public function testItFiltersUsersWhoOnlyWantSeeNewArtifactsWhenArtifactIsUpdatedAndUserIsInvolved(): void
    {
        $this->mockADateField(true);

        $changeset = $this->getAMockedChangeset(
            [],
            [],
            'On going',
            'On going',
            [[
                'on_updates'        => true,
                'check_permissions' => true,
                'recipients'        => ['recipient2', 'recipient3'],
            ],
            ],
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            'some text',
            ChangesetTestBuilder::aChangeset(1)->build(),
        );

        $artifact = $changeset->getArtifact();
        self::assertTrue($artifact instanceof Artifact && $artifact instanceof MockObject);
        $artifact->method('userCanView')->willReturn(true);

        $this->user_status_change_only_dao->method('doesUserIdHaveSubscribeOnlyForStatusChangeNotification')
            ->with(self::anything(), 36)
            ->willReturn(false);

        $this->user_notification_settings->method('isInNotifyOnArtifactCreationMode')->willReturn(true, false);

        self::assertSame(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true, true, new NullLogger())
        );
    }

    private function mockADateField(bool $user_can_read): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Date::class);
        $field->method('isNotificationsSupported')->willReturn(false);
        $field->method('userCanRead')->willReturn($user_can_read);
        $this->formelement_factory->method('getFieldById')->with(1)->willReturn($field);
    }

    private function getAMockedChangeset(
        array $artifact_commentators,
        array $notifications_unsubscribers,
        ?string $previeous_changeset_status,
        string $artifact_status,
        array $tracker_recipients,
        int $tracker_notification_level,
        string $comment_body,
        ?Tracker_Artifact_Changeset $previous_changeset,
    ): Tracker_Artifact_Changeset&MockObject {
        $changeset       = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->method('hasChanged')->willReturn(true);
        $changeset_value->method('getField');
        $changeset->method('getValues')->willReturn([1 => $changeset_value]);
        $changeset->method('getId')->willReturn(1);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getCommentators')->willReturn($artifact_commentators);
        $artifact->method('getPreviousChangeset')->willReturn($previous_changeset);
        $this->unsubscribers_notification_dao->method('searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID')->willReturn($notifications_unsubscribers);
        $changeset->method('getArtifact')->willReturn($artifact);
        $artifact->method('getStatusForChangeset')->willReturn($previeous_changeset_status);
        $artifact->method('getStatus')->willReturn($artifact_status);
        $artifact->method('getId')->willReturn(125);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(36);
        $tracker->method('getRecipients')->willReturn($tracker_recipients);
        $tracker->method('getNotificationsLevel')->willReturn($tracker_notification_level);
        $changeset->method('getTracker')->willReturn($tracker);

        $comment_changeset = ChangesetCommentTestBuilder::aComment()->withCommentBody(
            $comment_body
        )->build();
        $changeset->method('getComment')->willReturn($comment_changeset);

        $changeset->method('getSubmitter')->willReturn(UserTestBuilder::aRandomActiveUser()->build());
        $changeset->method('forceFetchAllValues');

        return $changeset;
    }

    public function testItBuildsAListOfUserIdsFromTheirTrackerNotificationsSettings(): void
    {
        $user_recipients_from_tracker = [[
            'recipients'        =>
                [
                    'noctali@example.com',
                    'aquali@example.com',
                ],
            'on_updates'        => false,
            'check_permissions' => true,
        ],
        ];

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(888);
        $tracker->method('getRecipients')->willReturn($user_recipients_from_tracker);
        $this->user_manager->method('getUserByEmail')->willReturnCallback(static fn(string $email) => match ($email) {
            'noctali@example.com' => UserTestBuilder::buildWithId(101),
            'aquali@example.com'  => UserTestBuilder::buildWithId(102),
            default               => throw new LogicException("Should not be called with '$email'"),
        });

        $this->unsubscribers_notification_dao->method('searchUserIDHavingUnsubcribedFromNotificationByTrackerID')->willReturn([103, 104]);

        $this->user_status_change_only_dao->method('searchUserIdsHavingSubscribedForTrackerStatusChangedOnly')->willReturn([105, 106]);

        $all_user_ids = [101, 102, 103, 104, 105, 106];

        self::assertSame($all_user_ids, $this->recipients_manager->getAllRecipientsWhoHaveCustomSettingsForATracker($tracker));
    }
}
