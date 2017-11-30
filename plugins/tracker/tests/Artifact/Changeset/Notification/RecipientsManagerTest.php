<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class RecipientsManagerTest extends \TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $this->user_manager        = mock('\UserManager');
        $this->formelement_factory = mock('\Tracker_FormElementFactory');
        $this->recipients_manager  = new RecipientsManager(
            $this->formelement_factory,
            $this->user_manager
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

        $artifact = stub('\Tracker_Artifact')->getCommentators()->returns(array());
        stub($artifact)->getUnsubscribersIds()->returns(array());
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
        stub($artifact)->getUnsubscribersIds()->returns(array());
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
        stub($artifact)->getUnsubscribersIds()->returns(array());
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
        stub($artifact)->getUnsubscribersIds()->returns(array());
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
        stub($artifact)->getUnsubscribersIds()->returns(array(102));
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
}
