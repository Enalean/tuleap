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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use BaseLanguage;
use ConfigNotificationAssignedTo;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_Recipient;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\Mail\MailAttachment;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Test\Stub\Artifact\Changeset\PostCreation\ProvideEmailNotificationAttachmentStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Tracker;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EmailNotificationTaskTest extends TestCase
{
    private LoggerInterface $logger;
    private MailGatewayConfig&MockObject $mail_gateway_config;
    private ConfigNotificationAssignedTo&MockObject $config_notification_assigned_to;
    private Tracker_Artifact_MailGateway_RecipientFactory&MockObject $mail_gateway_recipient_factory;
    private UserHelper&MockObject $user_helper;
    private ConfigNotificationEmailCustomSender&MockObject $custom_email_sender;
    private Tracker&MockObject $tracker;
    private Artifact&MockObject $artifact;
    private Tracker_Artifact_Changeset $changeset;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger              = new NullLogger();
        $this->mail_gateway_config = $this->createMock(MailGatewayConfig::class);
        $this->mail_gateway_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->mail_gateway_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->config_notification_assigned_to = $this->createMock(ConfigNotificationAssignedTo::class);
        $this->config_notification_assigned_to->method('isAssignedToSubjectEnabled')->willReturn(false);
        $this->mail_gateway_recipient_factory = $this->createMock(Tracker_Artifact_MailGateway_RecipientFactory::class);
        $this->user_helper                    = $this->createMock(UserHelper::class);
        $this->user_helper->method('getDisplayNameFromUserId')->willReturn('User Display Name');
        $this->custom_email_sender = $this->createMock(ConfigNotificationEmailCustomSender::class);

        $this->custom_email_sender->method('getCustomSender')->willReturn(['format' => '', 'enabled' => 0]);

        $language = $this->createStub(BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getItemName')->willReturn('story');
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getId')->willReturn(666);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('fetchMailTitle')->willReturn('The title in the mail');
        $this->artifact->method('isFirstChangeset')->willReturn(false);
        $this->artifact->method('fetchMail')->willReturn('');
        $this->changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->changeset->method('getId')->willReturn(741);
        $this->changeset->method('getTracker')->willReturn($this->tracker);
        $this->changeset->method('getArtifact')->willReturn($this->artifact);
        $this->changeset->method('getSubmittedBy')->willReturn(987);
        $this->changeset->method('getSubmittedOn')->willReturn(1);
        $this->changeset->method('mailDiffToPrevious')->willReturn('');
        $this->changeset->method('diffToPrevious')->willReturn('');
        $this->changeset->method('getComment')->willReturn(null);
        $this->changeset->method('getSubmitter')->willReturn(
            UserTestBuilder::anActiveUser()->withTimezone('Europe/Paris')->withLanguage($language)->build()
        );
    }

    public function testNotify(): void
    {
        $recipients_manager = $this->createMock(RecipientsManager::class);
        $recipients_manager->method('getRecipients')->willReturn([
            'a_user'            => true,
            'email@example.com' => true,
            'comment1'          => true,
        ]);
        $language = $this->createStub(BaseLanguage::class);
        $language->method('getText')->willReturn('');
        $user_1 = UserTestBuilder::aUser()->withEmail('a_user')->withLanguage($language)->build();
        $user_2 = UserTestBuilder::aUser()->withEmail('email@example.com')->withLanguage($language)->build();
        $user_3 = UserTestBuilder::aUser()->withEmail('comment1')->withLanguage($language)->build();
        $recipients_manager->method('getUserFromRecipientName')->willReturnCallback(static fn(string $email) => match ($email) {
            'a_user'            => $user_1,
            'email@example.com' => $user_2,
            'comment1'          => $user_3,
        });

        $this->tracker->method('isNotificationStopped')->willReturn(false);
        $this->tracker->method('getItemName')->willReturn('story');
        $this->artifact->method('getId')->willReturn(666);

        $attachment1 = new MailAttachment('text/plain', 'doc.txt', 'Lorem');
        $attachment2 = new MailAttachment('text/plain', 'another.txt', 'Ipsum');

        $mail_sender = $this->createMock(MailSender::class);
        $mail_sender->expects($this->once())->method('send')->with(
            $this->changeset,
            [ // recipients
                'a_user',
                'email@example.com',
                'comment1',
            ],
            [],              // headers
            self::anything(), // from
            '[story #666] The title in the mail', // subject
            self::anything(), // html body
            self::anything(), // text body
            self::anything(),  // msg id,
            [$attachment1, $attachment2],
        );

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
            RetrieveSemanticDescriptionFieldStub::build(),
        );
        $mail_notification_task->execute($this->changeset, new PostCreationTaskConfiguration(true, []));
    }

    public function testNotifyAlwaysStopped(): void
    {
        $this->tracker->method('isNotificationStopped')->willReturn(true);

        $mail_sender = $this->createMock(MailSender::class);
        $mail_sender->expects($this->never())->method('send');

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $this->createMock(RecipientsManager::class),
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $mail_sender,
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
            RetrieveSemanticDescriptionFieldStub::build(),
        );
        $mail_notification_task->execute($this->changeset, new PostCreationTaskConfiguration(true, [UserTestBuilder::anActiveUser()->withUserName('peralta')->build()]));
    }

    public function testChangesetShouldUseUserLanguageInGetBody(): void
    {
        $user_language = $this->createMock(BaseLanguage::class);
        $user_language->expects($this->atLeastOnce())->method('getText')->willReturn('Foo');

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $this->createMock(RecipientsManager::class),
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $this->createMock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
            RetrieveSemanticDescriptionFieldStub::build(),
        );
        $body_text              = $mail_notification_task->getBodyText(
            $this->changeset,
            false,
            UserTestBuilder::buildWithDefaults(),
            $user_language,
            false
        );
        self::assertNotEmpty($body_text);
    }

    public function testChangesetShouldUseUserLanguageInBuildMessage(): void
    {
        $user_language = $this->createMock(BaseLanguage::class);
        $user_language->expects($this->atLeastOnce())->method('getText')->willReturn('');

        $preferences = new StoreUserPreferenceStub();
        $preferences->set(302, 'text', 'user_tracker_mailformat');
        $user = UserTestBuilder::aUser()->withId(302)->withLanguage($user_language)->withPreferencesStore($preferences)->build();

        $recipients_manager = $this->createMock(RecipientsManager::class);
        $recipients_manager->method('getUserFromRecipientName')->with('user01')->willReturn($user);

        $this->mail_gateway_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $recipients_manager,
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $this->createMock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
            RetrieveSemanticDescriptionFieldStub::build(),
        );
        $res                    = $mail_notification_task->buildOneMessageForMultipleRecipients($this->changeset, ['user01' => false], true, $this->logger);

        self::assertNotEmpty($res);
    }

    public function testItSendsOneMailPerRecipient()
    {
        $recipients_manager = $this->createMock(RecipientsManager::class);

        $language = $this->createStub(BaseLanguage::class);
        $language->method('getText')->willReturn('');
        $preferences = new StoreUserPreferenceStub();
        $preferences->set(302, 'text', 'user_tracker_mailformat');
        $user_1 = UserTestBuilder::aUser()->withEmail('email1')->withPreferencesStore($preferences)->withLanguage($language)->build();
        $user_2 = UserTestBuilder::aUser()->withEmail('email2')->withPreferencesStore($preferences)->withLanguage($language)->build();
        $user_3 = UserTestBuilder::aUser()->withEmail('email3')->withPreferencesStore($preferences)->withLanguage($language)->build();
        $recipients_manager->method('getUserFromRecipientName')->willReturnCallback(static fn(string $email) => match ($email) {
            'user01' => $user_1,
            'user02' => $user_2,
            'user03' => $user_3,
        });

        $this->mail_gateway_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);

        $recipient_1 = $this->createMock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_1->method('getEmail')->willReturn('email1');
        $recipient_2 = $this->createMock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_2->method('getEmail')->willReturn('email2');
        $recipient_3 = $this->createMock(Tracker_Artifact_MailGateway_Recipient::class);
        $recipient_3->method('getEmail')->willReturn('email3');
        $this->mail_gateway_recipient_factory->method('getFromUserAndChangeset')->willReturnCallback(static fn(PFUser $user) => match ($user) {
            $user_1 => $recipient_1,
            $user_2 => $recipient_2,
            $user_3 => $recipient_3,
        });

        $mail_notification_task = new EmailNotificationTask(
            $this->logger,
            $this->user_helper,
            $recipients_manager,
            $this->mail_gateway_recipient_factory,
            $this->mail_gateway_config,
            $this->createMock(MailSender::class),
            $this->config_notification_assigned_to,
            $this->custom_email_sender,
            ProvideEmailNotificationAttachmentStub::withoutAttachments(),
            RetrieveSemanticDescriptionFieldStub::build(),
        );

        $recipients = [
            'user01' => false,
            'user02' => false,
            'user03' => false,
        ];

        $messages = $mail_notification_task->buildAMessagePerRecipient($this->changeset, $recipients, true, $this->logger);

        self::assertCount(3, $messages);
    }
}
