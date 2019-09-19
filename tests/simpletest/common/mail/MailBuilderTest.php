<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

class MailBuilderTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();

        $user_manager = stub('UserManager')->getAllUsersByEmail()->returns(array());
        UserManager::setInstance($user_manager);

        $language = stub('BaseLanguage')->getContent('mail/html_template', 'en_US', null, '.php')
                                        ->returns(dirname(__FILE__).'/_fixtures/empty.tpl');

        $GLOBALS['Language'] = $language;

        $this->renderer   = mock('TemplateRenderer');
        $template_factory = stub('TemplateRendererFactory')->getRenderer()->returns($this->renderer);

        $this->mail_enhancer = mock('MailEnhancer');
        $this->mail_filter   =  mock('Tuleap\Mail\MailFilter');

        $this->builder = partial_mock(
            'MailBuilder',
            array('getMailSender'),
            array($template_factory, $this->mail_filter)
        );
        $this->codendi_mail  = mock('Codendi_Mail');

        $emails            = array('a@example.com', 'b@example.com');
        $subject           = 'This is an awesome subject';
        $full_body_html    = 'Body in <b> HTML </b>';
        $full_body_text    = 'Body in plain/text';
        $goto_link         = 'https://tuleap.example.com/goto?key=release&value=116&group_id=116';
        $service_name      = 'Files';

        $this->notification = new Notification(
            $emails,
            $subject,
            $full_body_html,
            $full_body_text,
            $goto_link,
            $service_name
        );
        stub($this->mail_filter)->filter()->returns($this->notification->getEmails());

        $GLOBALS['sys_default_domain'] = '';
        $GLOBALS['HTML']               = mock('Layout');
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['HTML']);

        parent::tearDown();
    }

    public function itBuildsAndSendsATruncatedEmailIfProjectUsesTruncatedEmail()
    {
        $project = stub('Project')->getTruncatedEmailsUsage()->returns(true);
        stub($this->codendi_mail)->send()->returns(true);
        stub($this->builder)->getMailSender()->returns($this->codendi_mail);

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        expect($this->renderer)->expectCallCount('renderToString', 2);
        expect($this->mail_enhancer)->enhanceMail()->count(0);

        $this->assertTrue($sent);
    }

    public function itBuildsAndSendsAClassicEmailIfProjectDoesNotUseTruncatedEmail()
    {
        $project = stub('Project')->getTruncatedEmailsUsage()->returns(false);
        stub($this->codendi_mail)->send()->returns(true);
        stub($this->builder)->getMailSender()->returns($this->codendi_mail);

        $sent = $this->builder->buildAndSendEmail($project, $this->notification, $this->mail_enhancer);

        expect($this->renderer)->renderToString()->never();
        expect($this->mail_enhancer)->enhanceMail()->count(2);

        $this->assertTrue($sent);
    }

    public function itDoesNotStopIfAMailIsNotSent()
    {
        $project        = stub('Project')->getTruncatedEmailsUsage()->returns(false);
        $codendi_mail_2 = stub('Codendi_Mail')->send()->returns(true);
        stub($this->codendi_mail)->send()->returns(false);
        stub($this->codendi_mail)->getTo()->returns('user1@example.com');
        stub($codendi_mail_2)->getTo()->returns('user2@example.com');

        stub($this->builder)->getMailSender()->returnsAt(0, $this->codendi_mail);
        stub($this->builder)->getMailSender()->returnsAt(1, $codendi_mail_2);

        $this->builder->expectCallCount('getMailSender', 2);
        $this->codendi_mail->expectCallCount('send', 1);
        $codendi_mail_2->expectCallCount('send', 1);

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );

        $this->assertFalse($sent);
    }

    public function itDoesNotTryToSendAMailIfNotRecipientHasBeenSet()
    {
        $project = stub('Project')->getTruncatedEmailsUsage()->returns(false);
        stub($this->builder)->getMailSender()->returns($this->codendi_mail);
        $this->codendi_mail->expectCallCount('send', 0);

        $sent = $this->builder->buildAndSendEmail(
            $project,
            $this->notification,
            $this->mail_enhancer
        );
        $this->assertTrue($sent);
    }
}
