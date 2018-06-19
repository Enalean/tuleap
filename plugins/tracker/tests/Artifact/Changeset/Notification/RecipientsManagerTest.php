<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Notification;

use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettings;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;

require_once __DIR__.'/../../../bootstrap.php';

class RecipientsManagerTest extends \TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();

        $this->user_manager                    = mock('\UserManager');
        $this->formelement_factory             = mock('\Tracker_FormElementFactory');
        $this->unsubscribers_notification_dao  = mock(UnsubscribersNotificationDAO::class);
        $this->notification_settings_retriever = mock(UserNotificationSettingsRetriever::class);
        $this->recipients_manager              = new RecipientsManager(
            $this->formelement_factory,
            $this->user_manager,
            $this->unsubscribers_notification_dao,
            $this->notification_settings_retriever
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
    }

    public function itReturnsRecipientsFromField()
    {
        $field = mock(Tracker_FormElement_Field_Selectbox::class);
        stub($field)->isNotificationsSupported()->returns(true);
        stub($field)->hasNotifications()->returns(true);
        stub($field)->getRecipients()->returns(array('recipient1'));
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock(Tracker_Artifact_Changeset::class);
        stub($changeset)->getValues()->returns(array(
            1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
        ));

        $artifact = stub(Tracker_Artifact::class)->getCommentators()->returns([]);
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

        stub($changeset)->getTracker()->returns(
            stub('\Tracker')->getRecipients()->returns(array())
        );

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            array('recipient1' => true),
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itReturnsRecipientsFromCommentators()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(array(
            1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
        ));

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(array('recipient2'));
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

        stub($changeset)->getTracker()->returns(
            stub('\Tracker')->getRecipients()->returns(array())
        );

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            array('recipient2' => true),
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itReturnsRecipientsFromTrackerConfig()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(array(
            1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
        ));

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(array());
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

        stub($changeset)->getTracker()->returns(
            stub('\Tracker')->getRecipients()->returns(array(
                array(
                    'on_updates' => true,
                    'check_permissions' => true,
                    'recipients' => array('recipient3')
                )
            ))
        );

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            array('recipient3' => true),
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itCleansUserFromRecipientsWhenUserCantReadAtLeastOneChangedField()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(false);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(array(
            1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
        ));

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(array('recipient2'));
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

        stub($changeset)->getTracker()->returns(
            stub('\Tracker')->getRecipients()->returns(array())
        );

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(true)
        );

        $this->assertEqual(
            array(),
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itCleansUserFromRecipientsWhenUserHasUnsubscribedFromArtifact()
    {
        $field = mock('\Tracker_FormElement_Field_SelectBox');
        stub($field)->isNotificationsSupported()->returns(true);
        stub($field)->hasNotifications()->returns(true);
        stub($field)->getRecipients()->returns(array('recipient1'));
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(array(
            1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
        ));

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(array('recipient2'));
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([102]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

        stub($changeset)->getTracker()->returns(
            stub('\Tracker')->getRecipients()->returns(array(
                array(
                    'on_updates' => true,
                    'check_permissions' => true,
                    'recipients' => array('recipient3')
                )
            ))
        );

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            array('recipient1' => true, 'recipient3' => true),
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterWhenTrackerIsNotInModeStatusUpdateOnly()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(
            [
                1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
            ]
        );

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(['recipient3']);
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

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
        stub($tracker)->getNotificationsLevel()->returns(Tracker::NOTIFICATIONS_LEVEL_DEFAULT);
        stub($changeset)->getTracker()->returns($tracker);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilerWhenStatusChangedAndTrackerIsInStatusChangeOnlyMode()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

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
        stub($artifact)->getStatus()->returns('Review');

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

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterAtArtifactCreation()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(
            [
                1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
            ]
        );

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(['recipient3']);
        stub($artifact)->getPreviousChangeset()->returns(null);
        stub($this->unsubscribers_notification_dao)->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID()->returns([]);
        stub($changeset)->getArtifact()->returns(
            $artifact
        );

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

        $user_notification_settings = mock(UserNotificationSettings::class);
        stub($this->notification_settings_retriever)->getUserNotificationSettings()->returns($user_notification_settings);
        stub($user_notification_settings)->isInNotifyOnEveryChangeMode()->returns(false);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterUsersWhoAreInGlobalNotificationWithNotificationInEveryStatusChangeChecked()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

        $changeset = mock('\Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(
            [
                1 => stub('\Tracker_Artifact_ChangesetValue_List')->hasChanged()->returns(true),
            ]
        );

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns([]);
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
                    'recipients'        => ['recipient3']
                ]
            ]
        );
        stub($tracker)->getNotificationsLevel()->returns(Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE);
        stub($changeset)->getTracker()->returns($tracker);

        $user_notification_settings = mock(UserNotificationSettings::class);
        stub($this->notification_settings_retriever)->getUserNotificationSettings()->returns($user_notification_settings);
        stub($user_notification_settings)->isInNotifyOnEveryChangeMode()->returns(true);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itFilterUsersWhoAreInGlobalNotification()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

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

        $user_notification_settings = mock(UserNotificationSettings::class);
        stub($this->notification_settings_retriever)->getUserNotificationSettings()->returns($user_notification_settings);
        stub($user_notification_settings)->isInNotifyOnEveryChangeMode()->returns(false);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            [],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }

    public function itDoesNotFilterIfNoStatusChangeAndTrackerIsInStatusChangeOnlyAndUserSubscribeAllNotifications()
    {
        $field = mock('\Tracker_FormElement_Field_Date');
        stub($field)->isNotificationsSupported()->returns(false);
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFieldById(1)->returns($field);

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

        $user_notification_settings = mock(UserNotificationSettings::class);
        stub($this->notification_settings_retriever)->getUserNotificationSettings()->returns($user_notification_settings);
        stub($user_notification_settings)->isInNotifyOnEveryChangeMode()->returns(true);

        stub($changeset)->getComment()->returns(
            stub('\Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(false)
        );

        $this->assertEqual(
            ['recipient3' => true],
            $this->recipients_manager->getRecipients($changeset, true)
        );
    }
}
