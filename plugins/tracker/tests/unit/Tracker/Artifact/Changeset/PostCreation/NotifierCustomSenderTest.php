<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

require_once __DIR__ . '/../../../../bootstrap.php';

use ColinODell\PsrTestLogger\TestLogger;
use ConfigNotificationAssignedTo;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderFormatter;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\ProvideEmailNotificationAttachmentStub;
use UserHelper;

class NotifierCustomSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var String
     * */
    private $default_format;
    private $recipients_manager;
    private $mail_gateway_config;
    private $recipient_factory;
    private $mail_sender;
    private $custom_email_sender;
    private $mail_notification_task;
    private $user_realname;
    private $default_format_var;
    private $default_format_value;

    public function setUp(): void
    {
        parent::setUp();

        $logger                   = \Mockery::mock(LoggerInterface::class);
        $this->recipients_manager = \Mockery::mock(RecipientsManager::class);

        $this->mail_gateway_config = \Mockery::mock(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig::class);
        $this->mail_gateway_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturn(false);
        $this->mail_gateway_config->shouldReceive('isInsecureEmailgatewayEnabled')->andReturn(false);

        $config_notification_assigned_to = \Mockery::mock(ConfigNotificationAssignedTo::class);
        $config_notification_assigned_to->shouldReceive('isAssignedToSubjectEnabled')->andReturn(false);

        $this->recipient_factory   = \Mockery::mock(Tracker_Artifact_MailGateway_RecipientFactory::class);
        $user_helper               = \Mockery::spy(UserHelper::class);
        $this->mail_sender         = \Mockery::mock(MailSender::class);
        $this->custom_email_sender = \Mockery::mock(\Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender::class);

        $this->mail_notification_task = new EmailNotificationTask(
            $logger,
            $user_helper,
            $this->recipients_manager,
            $this->recipient_factory,
            $this->mail_gateway_config,
            $this->mail_sender,
            $config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );

        $this->user_realname        = "J. Doe";
        $this->default_format_var   = 'realname';
        $this->default_format       = '%' . $this->default_format_var . ' from Tuleap';
        $this->default_format_value = $this->user_realname;
    }

    private function getMessagesForRecipients($custom_sender_enabled)
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturn(66);
        $changeset->shouldReceive('mailDiffToPrevious')->andReturn(false);

        $changeset->shouldReceive('getComment')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset_Comment::class));

        $this->recipients_manager->shouldReceive('getRecipients')->andReturn([
            'a_user' => true,
            'email@example.com' => true,
            'comment1' => true,
        ]);

        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $example = \Mockery::spy(PFUser::class);
        $example->shouldReceive('toRow')->andReturn(
            [
                'user_name' => 'abc',
                'realname' => $this->user_realname,
                'language' => $language,
                'email' => 'email@example.com',
            ]
        );
        $example->shouldReceive('getRealname')->andReturn($this->user_realname);
        $example->shouldReceive('getEmail')->andReturn('email@example.com');
        $example->shouldReceive('getLanguage')->andReturn($language);
        $example->shouldReceive('getTimezone')->andReturn('Europe/Paris');

        $changeset->shouldReceive('getSubmitter')->andReturn($example);
        $this->recipients_manager->shouldReceive('getUserFromRecipientName')->andReturn($example);

        $tracker = \Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getItemName')->andReturn('story');

        $changeset->shouldReceive('getTracker')->andReturn($tracker);

        $artifact = \Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(666);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('fetchMailTitle')->andReturn('The title in the mail');

        $changeset->shouldReceive('getArtifact')->andReturn($artifact);

        $this->custom_email_sender->shouldReceive('getCustomSender')->andReturn(
            [
                'format' => $this->default_format,
                'enabled' => $custom_sender_enabled,
            ]
        );

        return $this->mail_notification_task->buildOneMessageForMultipleRecipients($changeset, $this->recipients_manager->getRecipients($changeset, true, new NullLogger()), false, new TestLogger());
    }

    public function testFetchesTheCorrectlyFormattedSenderFieldWhenEnabled()
    {
        $messages  = $this->getMessagesForRecipients(true);
        $formatter = new ConfigNotificationEmailCustomSenderFormatter([$this->default_format_var => $this->default_format_value]);
        foreach ($messages as $message) {
            $this->assertNotEquals(strpos($message['from'], $formatter->formatString($this->default_format)), false);
        }
    }

    public function testDoesNotFetchCustomSendersWhenDisabled()
    {
        $messages  = $this->getMessagesForRecipients(false);
        $formatter = new ConfigNotificationEmailCustomSenderFormatter([$this->default_format_var => $this->default_format_value]);
        foreach ($messages as $message) {
            $this->assertEquals(strpos($message['from'], $formatter->formatString($this->default_format)), false);
        }
    }
}
