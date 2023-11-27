<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Mail;

use Codendi_Mail;
use ForgeConfig;
use MailBuilder;
use MailEnhancer;
use PHPUnit\Framework\MockObject\MockObject;
use TemplateRenderer;
use Tuleap\Notification\Notification;
use UserManager;

final class MailBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    /** @var MailBuilder&MockObject */
    private $builder;

    /** @var Notification */
    private $notification;

    /** @var TemplateRenderer&MockObject */
    private $renderer;

    /** @var MailEnhancer&MockObject */
    private $mail_enhancer;

    /** @var MailFilter&MockObject */
    private $mail_filter;

    /**
     * @var Codendi_Mail&MockObject
     */
    private $codendi_mail;

    protected function setUp(): void
    {
        parent::setUp();

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getAllUsersByEmail')->willReturn([]);
        UserManager::setInstance($user_manager);

        $this->renderer   = $this->createMock(\TemplateRenderer::class);
        $template_factory = $this->createMock(\TemplateRendererFactory::class);
        $template_factory->method('getRenderer')->willReturn($this->renderer);

        $this->mail_enhancer = $this->createMock(\MailEnhancer::class);
        $this->mail_enhancer->method('enhanceMail');
        $this->mail_filter = $this->createMock(MailFilter::class);

        $this->builder = $this->createPartialMock(\MailBuilder::class, [
            'getMailSender',
        ]);
        $this->builder->__construct($template_factory, $this->mail_filter);
        $this->codendi_mail = $this->createMock(\Codendi_Mail::class);
        $this->codendi_mail->method('setFrom');
        $this->codendi_mail->method('setTo');
        $this->codendi_mail->method('setSubject');
        $this->codendi_mail->method('setBodyHtml');
        $this->codendi_mail->method('setBodyText');
        $this->codendi_mail->method('getCc');
        $this->codendi_mail->method('getBcc');

        $emails         = ['a@example.com', 'b@example.com'];
        $subject        = 'This is an awesome subject';
        $full_body_html = 'Body in <b> HTML </b>';
        $full_body_text = 'Body in plain/text';
        $goto_link      = 'https://tuleap.example.com/goto?key=release&value=116&group_id=116';
        $service_name   = 'Files';

        $this->notification = new Notification(
            $emails,
            $subject,
            $full_body_html,
            $full_body_text,
            $goto_link,
            $service_name
        );
        $this->mail_filter->method('filter')->willReturn($this->notification->getEmails());

        ForgeConfig::set('sys_default_domain', '');
        $GLOBALS['HTML'] = $this->createMock(\Layout::class);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        unset($GLOBALS['HTML']);

        parent::tearDown();
    }

    public function testItBuildsAndSendsATruncatedEmailIfProjectUsesTruncatedEmail(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getTruncatedEmailsUsage')->willReturn(true);
        $this->codendi_mail->method('send')->willReturn(true);
        $this->codendi_mail->method('getTo');
        $this->builder->method('getMailSender')->willReturn($this->codendi_mail);

        $this->renderer->expects(self::exactly(4))->method('renderToString');
        $this->mail_enhancer->expects(self::never())->method('enhanceMail');

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        self::assertTrue($sent);
    }

    public function testItBuildsAndSendsAClassicEmailIfProjectDoesNotUseTruncatedEmail(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getTruncatedEmailsUsage')->willReturn(false);
        $this->codendi_mail->method('send')->willReturn(true);
        $this->codendi_mail->method('getTo');
        $this->builder->method('getMailSender')->willReturn($this->codendi_mail);

        $this->renderer->expects(self::never())->method('renderToString');
        $this->mail_enhancer->expects(self::exactly(2))->method('enhanceMail');

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        self::assertTrue($sent);
    }

    public function testItDoesNotStopIfAMailIsNotSent(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getTruncatedEmailsUsage')->willReturn(false);
        $codendi_mail_2 = $this->createMock(\Codendi_Mail::class);
        $codendi_mail_2->expects(self::once())->method('send')->willReturn(true);
        $this->codendi_mail->expects(self::once())->method('send')->willReturn(false);
        $this->codendi_mail->method('getTo')->willReturn('user1@example.com');
        $codendi_mail_2->method('getTo')->willReturn('user2@example.com');
        $codendi_mail_2->method('setFrom');
        $codendi_mail_2->method('setTo');
        $codendi_mail_2->method('setBodyHtml');
        $codendi_mail_2->method('setBodyText');
        $codendi_mail_2->method('setSubject');

        $this->builder->expects(self::exactly(2))->method('getMailSender')->willReturnOnConsecutiveCalls($this->codendi_mail, $codendi_mail_2);

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );

        self::assertFalse($sent);
    }

    public function testItDoesNotTryToSendAMailIfNotRecipientHasBeenSet(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getTruncatedEmailsUsage')->willReturn(false);
        $this->builder->method('getMailSender')->willReturn($this->codendi_mail);
        $this->codendi_mail->expects(self::never())->method('send');
        $this->codendi_mail->method('getTo');

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );
        self::assertTrue($sent);
    }
}
