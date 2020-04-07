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
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\MailAccountSuspensionAlertPresenter;
use Tuleap\Mail\MailAccountSuspensionPresenter;
use UserManager;

class UserSuspensionManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    private $dao;
    private $lang_factory;
    private $language;
    private $mail;
    private $mail_account_suspension_alert_presenter;
    private $mail_presenter_factory;
    private $template_renderer;
    private $user_manager;
    private $user_suspension_logger;
    private $user_suspension_manager;
    private $mail_account_suspension_presenter;

    public function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        ForgeConfig::set('sys_suspend_inactive_accounts_delay', 10);
        ForgeConfig::set('sys_suspend_inactive_accounts_notification_delay', 4);
        ForgeConfig::set('sys_suspend_non_project_member_delay', 4);
        ForgeConfig::set('sys_suspend_send_account_suspension_email', 1);

        $this->mail_account_suspension_alert_presenter = Mockery::mock(MailAccountSuspensionAlertPresenter::class);
        $this->mail_account_suspension_presenter = Mockery::mock(MailAccountSuspensionPresenter::class);
        $this->mail_presenter_factory = Mockery::mock(MailPresenterFactory::class);
        $this->template_renderer = Mockery::mock(TemplateRenderer::class);
        $this->mail = Mockery::mock(Codendi_Mail::class);
        $this->dao = Mockery::mock(UserSuspensionDao::class);
        $this->language = Mockery::mock(BaseLanguage::class);
        $this->lang_factory = Mockery::mock(BaseLanguageFactory::class);
        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('instance')->andReturn($this->user_manager);
        $this->user_suspension_logger = Mockery::mock(LoggerInterface::class);

        $this->user_suspension_manager = new UserSuspensionManager(
            $this->mail_presenter_factory,
            $this->template_renderer,
            $this->mail,
            $this->dao,
            $this->user_manager,
            $this->lang_factory,
            $this->user_suspension_logger,
            new LocaleSwitcher()
        );
    }

    public function testSendNotificationMailToIdleAccountsIsCalled()
    {
        $test_date = (new \DateTimeImmutable())->setTimestamp(1585090800);
        $last_access_date = (new \DateTimeImmutable())->setTimestamp(1584576000);
        $suspension_date = (new \DateTimeImmutable())->setTimestamp(1585436400);

        // Disable mail to inactive accounts
        ForgeConfig::set('sys_suspend_send_account_suspension_email', 0);

        $idle_user = new PFUser(array("email" => "valid_mail@domain.com", "user_id" => 111, "user_name" => "idle_user", "language_id" => "fr_FR"));

        $this->dao->shouldReceive('getIdleAccounts')
            ->andReturn(array(array('user_id' => 111, 'last_access_date' => $last_access_date->getTimestamp())));
        $this->user_manager->shouldReceive('getUserbyId')->with(111)->andReturn($idle_user);
        $this->lang_factory->shouldReceive('getBaseLanguage')->andReturn($this->language);
        $this->user_suspension_logger->shouldReceive('info');
        $this->mail_presenter_factory->shouldReceive('createMailAccountSuspensionAlertPresenter')
            ->with(Matchers::equalTo($last_access_date), Matchers::equalTo($suspension_date), $this->language)
            ->andReturn($this->mail_account_suspension_alert_presenter);

        $this->mail->shouldReceive('setFrom')->with(ForgeConfig::get('sys_noreply'));
        $this->mail->shouldReceive('setTo')->with($idle_user->getEmail());
        $this->mail->shouldReceive('setSubject');
        $this->mail->shouldReceive('setBodyHtml');
        $this->mail->shouldReceive('send')->andReturn(true);
        $this->template_renderer->shouldReceive('renderToString')
            ->with('mail-suspension-alert', $this->mail_account_suspension_alert_presenter)
            ->andReturn('Rendered_Email');

        $this->user_suspension_manager->sendNotificationMailToIdleAccounts($test_date);
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

    public function testSendNotificationMailToInactiveAccountsIsCalled()
    {
        $test_date = (new \DateTimeImmutable())->setTimestamp(1579616700);
        $last_access_date = (new \DateTimeImmutable())->setTimestamp(1578752699);

        // Disable mailing to idle accounts
        ForgeConfig::set('sys_suspend_inactive_accounts_notification_delay', 0);

        $inactive_user = new PFUser(array("email" => "valid_mail@domain.com", "user_id" => 111, "user_name" => "inactive_user", "language_id" => "fr_FR"));

        $this->dao->shouldReceive('getUsersWithoutConnectionOrAccessBetweenDates')
            ->andReturn(array(array('user_id' => 111, 'last_access_date' => 1578752699)));
        $this->user_manager->shouldReceive('getUserbyId')->with(111)->andReturn($inactive_user);
        $this->lang_factory->shouldReceive('getBaseLanguage')->andReturn($this->language);
        $this->user_suspension_logger->shouldReceive('info');
        $this->mail_presenter_factory->shouldReceive('createMailAccountSuspensionPresenter')
            ->with(Matchers::equalTo($last_access_date), $this->language)
            ->andReturn($this->mail_account_suspension_presenter);

        $this->mail->shouldReceive('setFrom')->with(ForgeConfig::get('sys_noreply'));
        $this->mail->shouldReceive('setTo')->with($inactive_user->getEmail());
        $this->mail->shouldReceive('setSubject');
        $this->mail->shouldReceive('setBodyHtml');
        $this->mail->shouldReceive('send')->andReturn(true);
        $this->template_renderer->shouldReceive('renderToString')
            ->with('mail-suspension', $this->mail_account_suspension_presenter)
            ->andReturn('Rendered_Email');

        $this->user_suspension_manager->sendSuspensionMailToInactiveAccounts($test_date);
    }
}
