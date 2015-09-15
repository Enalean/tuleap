<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once('common/autoload.php');

class MailBuilderTest extends TuleapTestCase {

    /** @var MailBuilder */
    private $builder;

    /** @var Notification */
    private $notification;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var MailEnhancer */
    private $mail_enhancer;

    public function setUp() {
        parent::setUp();

        $user_manager = stub('UserManager')->getAllUsersByEmail()->returns(array());
        UserManager::setInstance($user_manager);

        $language = stub('BaseLanguage')->getContent('mail/html_template', 'en_US', null, '.php')
                                        ->returns(dirname(__FILE__).'/_fixtures/empty.tpl');

        $GLOBALS['Language'] = $language;

        $this->renderer   = mock('TemplateRenderer');
        $template_factory = stub('TemplateRendererFactory')->getRenderer()->returns($this->renderer);

        $this->mail_enhancer = mock('MailEnhancer');

        $this->builder = new MailBuilder($template_factory);

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

        $GLOBALS['sys_default_domain'] = '';
        $GLOBALS['HTML']               = mock('Layout');
    }

    public function tearDown() {
        UserManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['HTML']);

        parent::tearDown();
    }

    public function itBuildsATruncatedEmailIfProjectUsesTruncatedEmail() {
        $project = stub('Project')->getTruncatedEmailsUsage()->returns(true);

        $email = $this->builder->buildEmail($project, $this->notification, $this->mail_enhancer);

        expect($this->renderer)->expectCallCount('renderToString', 2);
        expect($this->mail_enhancer)->enhanceMail()->count(0);

        $this->assertIsA($email, 'Codendi_Mail');
    }

    public function itBuildsAClassicEmailIfProjectDoesNotUseTruncatedEmail() {
        $project = stub('Project')->getTruncatedEmailsUsage()->returns(false);

        $email = $this->builder->buildEmail($project, $this->notification, $this->mail_enhancer);

        expect($this->renderer)->renderToString()->never();
        expect($this->mail_enhancer)->enhanceMail()->count(1);

        $this->assertIsA($email, 'Codendi_Mail');
    }
}