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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use BaseLanguage;
use ConfigNotificationAssignedTo;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderFormatter;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\ProvideEmailNotificationAttachmentStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\SendMailStub;
use UserHelper;

final class NotifierCustomSenderTest extends TestCase
{
    use GlobalLanguageMock;

    private string $default_format;
    private RecipientsManager&MockObject $recipients_manager;
    private ConfigNotificationEmailCustomSender&MockObject $custom_email_sender;
    private EmailNotificationTask $mail_notification_task;
    private string $user_realname;
    private string $default_format_var;
    private string $default_format_value;

    public function setUp(): void
    {
        $this->recipients_manager = $this->createMock(RecipientsManager::class);

        $mail_gateway_config = $this->createMock(MailGatewayConfig::class);
        $mail_gateway_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $mail_gateway_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);

        $config_notification_assigned_to = $this->createMock(ConfigNotificationAssignedTo::class);
        $config_notification_assigned_to->method('isAssignedToSubjectEnabled')->willReturn(false);

        $this->custom_email_sender = $this->createMock(ConfigNotificationEmailCustomSender::class);

        $this->mail_notification_task = new EmailNotificationTask(
            new NullLogger(),
            $this->createMock(UserHelper::class),
            $this->recipients_manager,
            $this->createMock(Tracker_Artifact_MailGateway_RecipientFactory::class),
            $mail_gateway_config,
            SendMailStub::build(),
            $config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
        );

        $this->user_realname        = 'J. Doe';
        $this->default_format_var   = 'realname';
        $this->default_format       = '%' . $this->default_format_var . ' from Tuleap';
        $this->default_format_value = $this->user_realname;
    }

    private function getMessagesForRecipients(bool $custom_sender_enabled): array
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn(66);
        $changeset->method('mailDiffToPrevious')->willReturn(false);
        $changeset->method('getComment')->willReturn($this->createMock(Tracker_Artifact_Changeset_Comment::class));

        $this->recipients_manager->method('getRecipients')->willReturn([
            'a_user'            => true,
            'email@example.com' => true,
            'comment1'          => true,
        ]);

        $language = $this->createStub(BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $user = UserTestBuilder::anActiveUser()
            ->withUserName('abc')
            ->withRealName($this->user_realname)
            ->withLanguage($language)
            ->withEmail('email@example.com')
            ->withTimezone('Europe/Paris')
            ->build();

        $changeset->method('getSubmitter')->willReturn($user);
        $this->recipients_manager->method('getUserFromRecipientName')->willReturn($user);

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->withName('story')->build();

        $changeset->method('getTracker')->willReturn($tracker);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(666);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('fetchMailTitle')->willReturn('The title in the mail');

        $changeset->method('getArtifact')->willReturn($artifact);

        $this->custom_email_sender->method('getCustomSender')->willReturn([
            'format'  => $this->default_format,
            'enabled' => $custom_sender_enabled,
        ]);

        $logger = new NullLogger();
        return $this->mail_notification_task->buildOneMessageForMultipleRecipients($changeset, $this->recipients_manager->getRecipients($changeset, true, $logger), false, $logger);
    }

    public function testFetchesTheCorrectlyFormattedSenderFieldWhenEnabled(): void
    {
        $messages  = $this->getMessagesForRecipients(true);
        $formatter = new ConfigNotificationEmailCustomSenderFormatter([$this->default_format_var => $this->default_format_value]);
        foreach ($messages as $message) {
            self::assertNotEquals(false, strpos($message['from'], $formatter->formatString($this->default_format)));
        }
    }

    public function testDoesNotFetchCustomSendersWhenDisabled(): void
    {
        $messages  = $this->getMessagesForRecipients(false);
        $formatter = new ConfigNotificationEmailCustomSenderFormatter([$this->default_format_var => $this->default_format_value]);
        foreach ($messages as $message) {
            self::assertFalse(strpos($message['from'], $formatter->formatString($this->default_format)));
        }
    }
}
