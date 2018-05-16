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

require_once __DIR__.'/../../../bootstrap.php';

use BaseLanguage;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker;
use Tracker_Artifact;
use UserHelper;
use BaseLanguageFactory;
use TestHelper;
use Tracker_Artifact_MailGateway_RecipientFactory;
use ConfigNotificationAssignedTo;
use Logger;
use Codendi_HTMLPurifier;

class NotifierTest extends \TuleapTestCase
{
    /**
     * @var Tracker_Artifact_MailGateway_RecipientFactory
     */
    private $recipient_factory;
    /**
     * @var Notifier
     */
    private $changeset_notifications;

    /**
     * @var \Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig
     */
    private $mail_gateway_config;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var RecipientsManager
     */
    private $recipients_manager;

    /**
     * @var Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var \Tuleap\Tracker\Artifact\Changeset\Notification\MailSender
     */
    private $mail_sender;

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['Language'] = mock('BaseLanguage');

        $this->user = aUser()->withId(114)->build();

        $logger                          = mock('Logger');
        $this->recipients_manager        = mock('\Tuleap\Tracker\Artifact\Changeset\Notification\RecipientsManager');
        $this->mail_gateway_config       = mock('\Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');
        $config_notification_assigned_to = mock('ConfigNotificationAssignedTo');
        $this->recipient_factory         = mock('Tracker_Artifact_MailGateway_RecipientFactory');
        $user_helper                     = mock('UserHelper');
        $this->mail_sender               = mock('\Tuleap\Tracker\Artifact\Changeset\Notification\MailSender');

        $this->changeset = stub('Tracker_Artifact_Changeset')
            ->getArtifact()
            ->returns(
                aMockArtifact()
                    ->withId(111)
                    ->withTracker(
                        aMockTracker()->build()
                    )
                    ->build()
            );

        $this->changeset_notifications = new Notifier(
            $logger,
            $this->mail_gateway_config,
            $config_notification_assigned_to,
            $this->recipient_factory,
            $user_helper,
            $this->recipients_manager,
            $this->mail_sender,
            mock('\Tuleap\Tracker\Artifact\Changeset\Notification\NotifierDao')
        );
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testNotify()
    {
        $changeset = mock('Tracker_Artifact_Changeset');
        stub($changeset)->getId()->returns(66);

        stub($this->recipients_manager)->getRecipients()->returns(
            array(
                'a_user' => true,
                'email@example.com'=> true,
                'comment1' => true,
            )
        );
        $language = mock('BaseLanguage');
        stub($this->recipients_manager)->getUserFromRecipientName('a_user')->returns(aUser()->withEmail('a_user')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('email@example.com')->returns(aUser()->withEmail('email@example.com')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('comment1')->returns(aUser()->withEmail('comment1')->withLanguage($language)->build());

        $tracker = aMockTracker()->withItemName('story')->build();
        $tracker->setReturnValue('isNotificationStopped', false);
        stub($changeset)->getTracker()->returns($tracker);

        $artifact = aMockArtifact()->withId(666)->withTracker($tracker)->build();

        stub($changeset)->getArtifact()->returns($artifact);

        expect($this->mail_sender)->send(
            $changeset,
            array( // recipients
                'a_user',
                'email@example.com',
                'comment1',
            ),
            array(),         // headers
            '*',             // from
            '[story #666] ', // subject
            '*',             // html body
            '*',             // text body
            '*'              // msg id
        )->once();

        $this->changeset_notifications->notify($changeset);
    }

    public function testNotifyStopped()
    {
        expect($this->mail_sender)->send()->never();

        $changeset = stub('Tracker_Artifact_Changeset')->getTracker()->returns(
            stub('Tracker')->isNotificationStopped()->returns(true)
        );
        $this->changeset_notifications->notify($changeset);
    }


    public function testChangesetShouldUseUserLanguageInGetBody()
    {
        $userLanguage = mock('BaseLanguage');
        $GLOBALS['Language']->expectNever('getText');
        $userLanguage->expectAtLeastOnce('getText');

        $this->changeset_notifications->getBodyText($this->changeset, false, $this->user, $userLanguage, false);
    }

    public function testChangesetShouldUseUserLanguageInBuildMessage()
    {
        $GLOBALS['Language']->expectNever('getText');
        $userLanguage = mock('BaseLanguage');
        $userLanguage->expectAtLeastOnce('getText');

        $user = mock('PFUser');
        $user->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user->setReturnValue('getLanguage', $userLanguage);

        stub($this->recipients_manager)->getUserFromRecipientName('user01')->returns($user);

        stub($this->mail_gateway_config)->isTokenBasedEmailgatewayEnabled()->returns(true);

        $recipients = array(
            'user01' => false
        );

        $this->changeset_notifications->buildOneMessageForMultipleRecipients($this->changeset, $recipients, true);
    }

    public function testItSendsOneMailPerRecipient()
    {
        $userLanguage = mock('BaseLanguage');

        $user1 = mock('PFUser');
        $user1->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user1->setReturnValue('getLanguage', $userLanguage);
        stub($user1)->getId()->returns(102);

        $user2 = mock('PFUser');
        $user2->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user2->setReturnValue('getLanguage', $userLanguage);
        stub($user2)->getId()->returns(103);

        $user3 = mock('PFUser');
        $user3->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user3->setReturnValue('getLanguage', $userLanguage);
        stub($user3)->getId()->returns(104);

        stub($this->recipients_manager)->getUserFromRecipientName('user01')->returns($user1);
        stub($this->recipients_manager)->getUserFromRecipientName('user02')->returns($user2);
        stub($this->recipients_manager)->getUserFromRecipientName('user03')->returns($user3);

        stub($this->mail_gateway_config)->isTokenBasedEmailgatewayEnabled()->returns(true);

        $recipient1 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email1');
        $recipient2 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email2');
        $recipient3 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email3');

        stub($this->recipient_factory)->getFromUserAndChangeset($user1, '*')->returns($recipient1);
        stub($this->recipient_factory)->getFromUserAndChangeset($user2, '*')->returns($recipient2);
        stub($this->recipient_factory)->getFromUserAndChangeset($user3, '*')->returns($recipient3);

        $recipients = array(
            'user01' => false,
            'user02' => false,
            'user03' => false
        );

        $messages = $this->changeset_notifications->buildAMessagePerRecipient($this->changeset, $recipients, true);

        $this->assertEqual(count($messages), 3);
    }
}
