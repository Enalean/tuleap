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

use Tuleap\Notification\Notification;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class MailBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    /** @var MailBuilder */
    private $builder;

    /** @var Notification */
    private $notification;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var MailEnhancer */
    private $mail_enhancer;

    /** @var Tuleap\Mail\MailFilter */
    private $mail_filter;

    /**
     * @var Codendi_Mail|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $codendi_mail;

    protected function setUp(): void
    {
        parent::setUp();

        $user_manager = \Mockery::spy(\UserManager::class)->shouldReceive('getAllUsersByEmail')->andReturns([])->getMock();
        UserManager::setInstance($user_manager);

        $this->renderer   = \Mockery::spy(\TemplateRenderer::class);
        $template_factory = \Mockery::spy(\TemplateRendererFactory::class)->shouldReceive('getRenderer')->andReturns($this->renderer)->getMock();

        $this->mail_enhancer = \Mockery::spy(\MailEnhancer::class);
        $this->mail_filter   =  \Mockery::spy(\Tuleap\Mail\MailFilter::class);

        $this->builder = \Mockery::mock(\MailBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->builder->__construct($template_factory, $this->mail_filter);
        $this->codendi_mail = \Mockery::spy(\Codendi_Mail::class);

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
        $this->mail_filter->shouldReceive('filter')->andReturns($this->notification->getEmails());

        ForgeConfig::set('sys_default_domain', '');
        $GLOBALS['HTML'] = \Mockery::spy(\Layout::class);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        unset($GLOBALS['HTML']);

        parent::tearDown();
    }

    public function testItBuildsAndSendsATruncatedEmailIfProjectUsesTruncatedEmail(): void
    {
        $project = \Mockery::spy(\Project::class)->shouldReceive('getTruncatedEmailsUsage')->andReturns(true)->getMock();
        $this->codendi_mail->shouldReceive('send')->andReturns(true);
        $this->builder->shouldReceive('getMailSender')->andReturns($this->codendi_mail);


        $this->renderer->shouldReceive('renderToString')->times(4);
        $this->mail_enhancer->shouldReceive('enhanceMail')->times(0);

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        $this->assertTrue($sent);
    }

    public function testItBuildsAndSendsAClassicEmailIfProjectDoesNotUseTruncatedEmail(): void
    {
        $project = \Mockery::spy(\Project::class)->shouldReceive('getTruncatedEmailsUsage')->andReturns(false)->getMock();
        $this->codendi_mail->shouldReceive('send')->andReturns(true);
        $this->builder->shouldReceive('getMailSender')->andReturns($this->codendi_mail);

        $this->renderer->shouldReceive('renderToString')->never();
        $this->mail_enhancer->shouldReceive('enhanceMail')->times(2);

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        $this->assertTrue($sent);
    }

    public function testItDoesNotStopIfAMailIsNotSent(): void
    {
        $project        = \Mockery::spy(\Project::class)->shouldReceive('getTruncatedEmailsUsage')->andReturns(false)->getMock();
        $codendi_mail_2 = \Mockery::spy(\Codendi_Mail::class)->shouldReceive('send')->once()->andReturns(true)->getMock();
        $this->codendi_mail->shouldReceive('send')->once()->andReturns(false);
        $this->codendi_mail->shouldReceive('getTo')->andReturns('user1@example.com');
        $codendi_mail_2->shouldReceive('getTo')->andReturns('user2@example.com');

        $this->builder->shouldReceive('getMailSender')->andReturns($this->codendi_mail)->once();
        $this->builder->shouldReceive('getMailSender')->andReturns($codendi_mail_2)->once();

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );

        $this->assertFalse($sent);
    }

    public function testItDoesNotTryToSendAMailIfNotRecipientHasBeenSet(): void
    {
        $project = \Mockery::spy(\Project::class)->shouldReceive('getTruncatedEmailsUsage')->andReturns(false)->getMock();
        $this->builder->shouldReceive('getMailSender')->andReturns($this->codendi_mail);
        $this->codendi_mail->shouldReceive('send')->never();

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );
        $this->assertTrue($sent);
    }
}
