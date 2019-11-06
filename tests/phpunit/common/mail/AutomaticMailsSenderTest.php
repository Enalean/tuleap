<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Mockery;
use ForgeConfig;

class AutomaticMailsSenderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $query;
    protected $auto_mail_sender;
    protected $email;
    protected $mail_presenter_factory;
    protected $template_renderer;
    protected $mail;
    protected $dao;
    protected $language;
    protected $pf_user;
    protected $user_manager;
    protected $mail_account_suspension_alert_presenter;
    protected $automatic_mails_logger;
    protected $user_info;


    public function setUp() : void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        ForgeConfig::set(AutomaticMailsSender::CONFIG_INACTIVE_DELAY, '180');
        ForgeConfig::set(AutomaticMailsSender::CONFIG_NOTIFICATION_DELAY, '30');
        $this->query = array(array('user_id' => 102, 'last_access_date' => 1557742551));
        $this->email = "jane.doe@domain.com";
        $this->user_info = array('user_id'=>102, 'user_name'=>'janedoe');

        $this->mail_account_suspension_alert_presenter = Mockery::mock(\Tuleap\Mail\MailAccountSuspensionAlertPresenter::class);
        $this->mail_presenter_factory = Mockery::mock(\MailPresenterFactory::class);
        $this->template_renderer = Mockery::mock(\TemplateRenderer::class);
        $this->mail = Mockery::mock(\Codendi_Mail::class);
        $this->dao = Mockery::mock(\Tuleap\User\IdleUsersDao::class);
        $this->language = Mockery::mock(\BaseLanguage::class);
        $this->lang_factory = Mockery::mock(\BaseLanguageFactory::class);
        $this->pf_user = Mockery::mock(\PFUser::class);
        $this->user_manager = Mockery::mock(\UserManager::class);
        $this->user_manager->shouldReceive('instance')->andReturn($this->user_manager);
        $this->automatic_mails_logger = Mockery::mock(\Tuleap\Mail\AutomaticMailsLogger::class);

        $this->auto_mail_sender = new AutomaticMailsSender(
            $this->mail_presenter_factory,
            $this->template_renderer,
            'mail-suspension-alert',
            $this->mail,
            $this->dao,
            $this->user_manager,
            $this->lang_factory,
            $this->automatic_mails_logger
        );
    }

    public function tearDown() : void
    {
        parent::tearDown();
        ForgeConfig::restore();
        Mockery::close();
    }

    public function testSendNotificationMailToIdleAccountsIsCalled()
    {
        $this->mail->shouldReceive('send')->andReturn(true);
        $this->mail->shouldReceive('setFrom');
        $this->mail->shouldReceive('setTo');
        $this->mail->shouldReceive('setSubject');
        $this->mail->shouldReceive('setBodyHtml');
        $this->dao->shouldReceive('getIdleAccounts')->andReturn($this->query);
        $this->mail_presenter_factory->shouldReceive('createMailAccountSuspensionAlertPresenter')->andReturn($this->mail_account_suspension_alert_presenter);
        $this->template_renderer->shouldReceive('renderToString')->andReturn('Rendered_Email');
        $this->lang_factory->shouldReceive('getBaseLanguage')->with(ForgeConfig::get('sys_lang'))->andReturn($this->language);
        $this->pf_user->shouldReceive('getEmail')->andReturn($this->email);
        $this->pf_user->shouldReceive('getId')->andReturn($this->user_info['user_id']);
        $this->pf_user->shouldReceive('getUserName')->andReturn($this->user_info['user_name']);
        $this->user_manager->shouldReceive('getUserbyId')->andReturn($this->pf_user);
        $this->automatic_mails_logger->shouldReceive('info');
        $this->automatic_mails_logger->shouldReceive('error');

        $this->assertEquals(true, $this->auto_mail_sender->sendNotificationMailToIdleAccounts());
    }
}
