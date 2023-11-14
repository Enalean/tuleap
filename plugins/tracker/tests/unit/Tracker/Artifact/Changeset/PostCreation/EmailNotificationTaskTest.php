<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_MailGateway_Recipient;
use Tuleap\Mail\MailAttachment;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\ProvideEmailNotificationAttachmentStub;

class EmailNotificationTaskTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $logger;
    private $mail_gateway_config;
    private $config_notification_assigned_to;
    private $mail_gateway_recipient_factory;
    private $user_helper;
    private $custom_email_sender;

    private $tracker;
    private $artifact;
    private $changeset;

    protected function setUp(): void
    {
        $this->logger                          = \Mockery::spy(LoggerInterface::class);
        $this->mail_gateway_config             = \Mockery::spy(MailGatewayConfig::class);
        $this->config_notification_assigned_to = \Mockery::spy(\ConfigNotificationAssignedTo::class);
        $this->mail_gateway_recipient_factory  = \Mockery::spy(\Tracker_Artifact_MailGateway_RecipientFactory::class);
        $this->user_helper                     = \Mockery::spy(\UserHelper::class);
        $this->custom_email_sender             = \Mockery::mock(ConfigNotificationEmailCustomSender::class);

        $this->custom_email_sender->shouldReceive('getCustomSender')->andReturns(['format' => '', 'enabled' => 0]);

        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $this->tracker  = \Mockery::spy(\Tracker::class);
        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->artifact->shouldReceive('fetchMailTitle')->andReturn('The title in the mail');
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->changeset->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->changeset->shouldReceive('getArtifact')->andReturns($this->artifact);
        $this->changeset->shouldReceive('getSubmitter')->andReturns(
            UserTestBuilder::anActiveUser()->withTimezone('Europe/Paris')->withLanguage($language)->build()
        );
    }

    public function testNotify()
    {
        $recipients_manager = \Mockery::mock(RecipientsManager::class);
        $recipients_manager->shouldReceive('getRecipients')->andReturns([
            'a_user'            => true,
            'email@example.com' => true,
            'comment1'          => true,
        ]);
        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');
        $user_1 = \Mockery::mock(\PFUser::class);
        $user_1->shouldReceive('getEmail')->andReturns('a_user');
        $user_1->shouldReceive('getLanguage')->andReturns($language);
        $user_1->shouldReceive('getPreference')->andReturns('');
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('a_user')->andReturns($user_1);
        $user_2 = \Mockery::mock(\PFUser::class);
        $user_2->shouldReceive('getEmail')->andReturns('email@example.com');
        $user_2->shouldReceive('getLanguage')->andReturns($language);
        $user_2->shouldReceive('getPreference')->andReturns('');
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('email@example.com')->andReturns($user_2);
        $user_3 = \Mockery::mock(\PFUser::class);
        $user_3->shouldReceive('getEmail')->andReturns('comment1');
        $user_3->shouldReceive('getLanguage')->andReturns($language);
        $user_3->shouldReceive('getPreference')->andReturns('');
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('comment1')->andReturns($user_3);

        $this->tracker->shouldReceive('isNotificationStopped')->andReturns(false);
        $this->tracker->shouldReceive('getItemName')->andReturns('story');
        $this->artifact->shouldReceive('getId')->andReturns(666);

        $attachment1 = new MailAttachment('text/plain', 'doc.txt', 'Lorem');
        $attachment2 = new MailAttachment('text/plain', 'another.txt', 'Ipsum');

        $mail_sender = \Mockery::mock(MailSender::class);
        $mail_sender->shouldReceive('send')->withArgs([
            $this->changeset,
            [ // recipients
                'a_user',
                'email@example.com',
                'comment1',
            ],
            [],              // headers
            \Mockery::any(), // from
            '[story #666] The title in the mail', // subject
            \Mockery::any(), // html body
            \Mockery::any(), // text body
            \Mockery::any(),  // msg id,
            [$attachment1, $attachment2],
        ])->once();

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $recipients_manager,
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $mail_sender,
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withAttachments($attachment1, $attachment2),
        );
        $mail_notification_task->execute($this->changeset, true);
    }

    public function testNotifyStopped()
    {
        $this->tracker->shouldReceive('isNotificationStopped')->andReturns(true);

        $mail_sender = \Mockery::mock(MailSender::class);
        $mail_sender->shouldReceive('send')->never();

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            \Mockery::mock(RecipientsManager::class),
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $mail_sender,
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );
        $mail_notification_task->execute($this->changeset, true);
    }

    public function testWithoutNotifications()
    {
        $this->tracker->shouldReceive('isNotificationStopped')->andReturns(false);

        $mail_sender = \Mockery::mock(MailSender::class);
        $mail_sender->shouldReceive('send')->never();

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            \Mockery::mock(RecipientsManager::class),
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $mail_sender,
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );
        $mail_notification_task->execute($this->changeset, false);
    }

    public function testChangesetShouldUseUserLanguageInGetBody()
    {
        $user_language = \Mockery::mock(\BaseLanguage::class);
        $user_language->shouldReceive('getText')->andReturn('Foo')->atLeast(1);

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            \Mockery::mock(RecipientsManager::class),
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            \Mockery::mock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );
        $body_text              = $mail_notification_task->getBodyText(
            $this->changeset,
            false,
            \Mockery::mock(\PFUser::class),
            $user_language,
            false
        );
        self::assertNotEmpty($body_text);
    }

    public function testChangesetShouldUseUserLanguageInBuildMessage()
    {
        $user_language = \Mockery::mock(\BaseLanguage::class);
        $user_language->shouldReceive('getText')->andReturn('')->atLeast()->once();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getPreference')->with('text')->andReturns(['user_tracker_mailformat']);
        $user->shouldReceive('getLanguage')->andReturns($user_language);

        $recipients_manager = \Mockery::mock(RecipientsManager::class);
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('user01')->andReturns($user);

        $this->mail_gateway_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $recipients_manager,
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            \Mockery::mock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );
        $res                    = $mail_notification_task->buildOneMessageForMultipleRecipients($this->changeset, ['user01' => false], true, $this->logger);

        self::assertNotEmpty($res);
    }

    public function testItSendsOneMailPerRecipient()
    {
        $recipients_manager = \Mockery::mock(RecipientsManager::class);

        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');
        $user_1 = \Mockery::spy(\PFUser::class);
        $user_1->shouldReceive('getLanguage')->andReturns($language);
        $user_1->shouldReceive('getPreference')->with('text')->andReturns(['user_tracker_mailformat']);
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('user01')->andReturns($user_1);
        $user_2 = \Mockery::spy(\PFUser::class);
        $user_2->shouldReceive('getLanguage')->andReturns($language);
        $user_2->shouldReceive('getPreference')->with('text')->andReturns(['user_tracker_mailformat']);
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('user02')->andReturns($user_2);
        $user_3 = \Mockery::spy(\PFUser::class);
        $user_3->shouldReceive('getLanguage')->andReturns($language);
        $user_3->shouldReceive('getPreference')->with('text')->andReturns(['user_tracker_mailformat']);
        $recipients_manager->shouldReceive('getUserFromRecipientName')->with('user03')->andReturns($user_3);

        $this->mail_gateway_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);

        $recipient_1 = \Mockery::mock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_1->shouldReceive('getEmail')->andReturns('email1');
        $this->mail_gateway_recipient_factory->shouldReceive('getFromUserAndChangeset')->withArgs([$user_1, \Mockery::any()])->andReturns($recipient_1);
        $recipient_2 = \Mockery::mock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_2->shouldReceive('getEmail')->andReturns('email2');
        $this->mail_gateway_recipient_factory->shouldReceive('getFromUserAndChangeset')->withArgs([$user_2, \Mockery::any()])->andReturns($recipient_2);
        $recipient_3 = \Mockery::mock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_3->shouldReceive('getEmail')->andReturns('email3');
        $this->mail_gateway_recipient_factory->shouldReceive('getFromUserAndChangeset')->withArgs([$user_3, \Mockery::any()])->andReturns($recipient_3);

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $recipients_manager,
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            \Mockery::mock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );

        $recipients = [
            'user01' => false,
            'user02' => false,
            'user03' => false,
        ];

        $messages = $mail_notification_task->buildAMessagePerRecipient($this->changeset, $recipients, true, $this->logger);

        $this->assertCount(3, $messages);
    }
}
