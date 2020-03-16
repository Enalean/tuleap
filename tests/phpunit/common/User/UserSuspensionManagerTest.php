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

namespace Tuleap\User;

use BaseLanguage;
use BaseLanguageFactory;
use Codendi_Mail;
use DateInterval;
use ForgeConfig;
use Hamcrest\Matchers;
use MailPresenterFactory;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TemplateRenderer;
use Tuleap\Dao\UserSuspensionDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Mail\MailAccountSuspensionAlertPresenter;
use UserManager;

class UserSuspensionManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $dao;
    private $email;
    private $lang_factory;
    private $language;
    private $mail;
    private $mail_account_suspension_alert_presenter;
    private $mail_presenter_factory;
    private $pf_user;
    private $query;
    private $template_renderer;
    private $user_info;
    private $user_manager;
    private $user_suspension_logger;
    private $user_suspension_manager;

    public function setUp() : void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        ForgeConfig::set('sys_suspend_inactive_accounts_delay', 10);
        ForgeConfig::set('sys_suspend_inactive_accounts_notification_delay', 4);
        ForgeConfig::set('sys_suspend_non_project_member_delay', 4);

        $this->query = array(array('user_id' => 102, 'last_access_date' => 1579267252));
        $this->email = "jane.doe@domain.com";
        $this->user_info = array('user_id' => 102, 'user_name' => 'janedoe');

        $this->mail_account_suspension_alert_presenter = Mockery::mock(MailAccountSuspensionAlertPresenter::class);
        $this->mail_presenter_factory = Mockery::mock(MailPresenterFactory::class);
        $this->template_renderer = Mockery::mock(TemplateRenderer::class);
        $this->mail = Mockery::mock(Codendi_Mail::class);
        $this->dao = Mockery::mock(UserSuspensionDao::class);
        $this->language = Mockery::mock(BaseLanguage::class);
        $this->lang_factory = Mockery::mock(BaseLanguageFactory::class);
        $this->pf_user = Mockery::mock(PFUser::class);
        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('instance')->andReturn($this->user_manager);
        $this->user_suspension_logger = Mockery::mock(LoggerInterface::class);

        $this->user_suspension_manager = new UserSuspensionManager(
            $this->mail_presenter_factory,
            $this->template_renderer,
            'mail-suspension-alert',
            $this->mail,
            $this->dao,
            $this->user_manager,
            $this->lang_factory,
            $this->user_suspension_logger
        );
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
        $this->user_suspension_logger->shouldReceive('info');
        $this->user_suspension_logger->shouldReceive('error');
        $this->dao = Mockery::mock(\Tuleap\Dao\UserSuspensionDao::class);

        $this->assertEquals(true, $this->user_suspension_manager->sendNotificationMailToIdleAccounts());
    }

    public function testCheckUserAccountValidity()
    {
        $test_date = (new \DateTimeImmutable())->setTimestamp(1579699252);

        $last_remove = $test_date->sub(new DateInterval("P4D"));
        $last_valid_access = $test_date->sub(new DateInterval("P10D"));

        $non_project_members = array(array('user_id' => 103), array('user_id' => 104));
        $non_project_member_1 = 103;
        $non_project_member_2 = 104;
        $this->user_suspension_logger->shouldReceive('info');
        $this->user_suspension_logger->shouldReceive('error');
        $this->user_suspension_logger->shouldReceive('debug');

        $this->dao->shouldReceive('returnNotProjectMembers')->andReturn($non_project_members);
        $this->dao->shouldReceive('delayForBeingNotProjectMembers')->with($non_project_member_1)->andReturn(array());
        $this->dao->shouldReceive('delayForBeingNotProjectMembers')->with($non_project_member_2)->andReturn(array(array('date' => 1579267252)));

        $this->dao->shouldReceive('delayForBeingSubscribed')
            ->with($non_project_member_1, Matchers::equalTo($last_remove))
            ->andReturn(array(array(null)));

        $this->dao->shouldReceive('suspendAccount')->with($non_project_member_1);
        $this->dao->shouldReceive('verifySuspension')->with($non_project_member_1)->andReturn(true);
        $this->dao->shouldReceive('suspendAccount')->with($non_project_member_2);
        $this->dao->shouldReceive('verifySuspension')->with($non_project_member_2)->andReturn(true);

        $this->dao->shouldReceive('suspendInactiveAccounts')->with(Matchers::equalTo($last_valid_access))->once();
        $this->dao->shouldReceive('suspendExpiredAccounts')->with($test_date)->once();

        $this->user_suspension_manager->checkUserAccountValidity($test_date);
    }
}
