<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use MailPresenterFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TemplateRenderer;
use Tuleap\Dao\UserSuspensionDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\MailAccountSuspensionAlertPresenter;
use Tuleap\Mail\MailAccountSuspensionPresenter;
use UserManager;

class UserSuspensionManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private UserSuspensionDao&MockObject $dao;
    private BaseLanguageFactory&MockObject $lang_factory;
    private BaseLanguage&MockObject $language;
    private Codendi_Mail&MockObject $mail;
    private MailAccountSuspensionAlertPresenter&MockObject $mail_account_suspension_alert_presenter;
    private MailPresenterFactory&MockObject $mail_presenter_factory;
    private TemplateRenderer&MockObject $template_renderer;
    private UserManager&MockObject $user_manager;
    private LoggerInterface&MockObject $user_suspension_logger;
    private UserSuspensionManager $user_suspension_manager;
    private MailAccountSuspensionPresenter&MockObject $mail_account_suspension_presenter;

    public function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        ForgeConfig::set('sys_suspend_inactive_accounts_delay', 10);
        ForgeConfig::set('sys_suspend_inactive_accounts_notification_delay', 4);
        ForgeConfig::set('sys_suspend_non_project_member_delay', 4);
        ForgeConfig::set('sys_suspend_send_account_suspension_email', 1);

        $this->mail_account_suspension_alert_presenter = $this->createMock(MailAccountSuspensionAlertPresenter::class);
        $this->mail_account_suspension_presenter       = $this->createMock(MailAccountSuspensionPresenter::class);
        $this->mail_presenter_factory                  = $this->createMock(MailPresenterFactory::class);
        $this->template_renderer                       = $this->createMock(TemplateRenderer::class);
        $this->mail                                    = $this->createMock(Codendi_Mail::class);
        $this->dao                                     = $this->createMock(UserSuspensionDao::class);
        $this->language                                = $this->createMock(BaseLanguage::class);
        $this->lang_factory                            = $this->createMock(BaseLanguageFactory::class);
        $this->user_manager                            = $this->createMock(UserManager::class);

        $this->user_suspension_logger = $this->createMock(LoggerInterface::class);

        $this->user_suspension_manager = new UserSuspensionManager(
            $this->mail_presenter_factory,
            $this->template_renderer,
            $this->mail,
            $this->dao,
            $this->user_manager,
            $this->lang_factory,
            $this->user_suspension_logger,
            new LocaleSwitcher(),
        );
    }

    public function testSendNotificationMailToIdleAccountsIsCalled()
    {
        $test_date        = (new \DateTimeImmutable())->setTimestamp(1585090800);
        $last_access_date = (new \DateTimeImmutable())->setTimestamp(1584576000);
        $suspension_date  = (new \DateTimeImmutable())->setTimestamp(1585436400);

        // Disable mail to inactive accounts
        ForgeConfig::set('sys_suspend_send_account_suspension_email', 0);

        $idle_user = new PFUser(["email" => "valid_mail@domain.com", "user_id" => 111, "user_name" => "idle_user", "language_id" => "fr_FR"]);

        $this->dao->method('getIdleAccounts')
            ->willReturn([['user_id' => 111, 'last_access_date' => $last_access_date->getTimestamp()]]);
        $this->user_manager->method('getUserbyId')->with(111)->willReturn($idle_user);
        $this->lang_factory->method('getBaseLanguage')->willReturn($this->language);
        $this->user_suspension_logger->method('info');
        $this->mail_presenter_factory->method('createMailAccountSuspensionAlertPresenter')
            ->with($last_access_date, $suspension_date, $this->language)
            ->willReturn($this->mail_account_suspension_alert_presenter);

        $this->mail->method('setFrom')->with(ForgeConfig::get('sys_noreply'));
        $this->mail->method('setTo')->with($idle_user->getEmail());
        $this->mail->method('setSubject');
        $this->mail->method('setBodyHtml');
        $this->mail->expects(self::atLeast(1))->method('send')->willReturn(true);
        $this->template_renderer->method('renderToString')
            ->with('mail-suspension-alert', $this->mail_account_suspension_alert_presenter)
            ->willReturn('Rendered_Email');

        $this->user_suspension_manager->sendNotificationMailToIdleAccounts($test_date);
    }

    public function testCheckUserAccountValidity(): void
    {
        $test_date = (new \DateTimeImmutable())->setTimestamp(1579699252);

        $last_remove       = $test_date->sub(new DateInterval("P4D"));
        $last_valid_access = $test_date->sub(new DateInterval("P10D"));

        $non_project_members  = [['user_id' => 103], ['user_id' => 104]];
        $non_project_member_1 = 103;
        $non_project_member_2 = 104;
        $this->user_suspension_logger->method('info');
        $this->user_suspension_logger->method('error');
        $this->user_suspension_logger->method('debug');

        $this->dao->method('returnNotProjectMembers')->willReturn($non_project_members);
        $this->dao->method('delayForBeingNotProjectMembers')->willReturnCallback(
            function (int $user_id) use ($non_project_member_1, $non_project_member_2): array {
                if ($user_id === $non_project_member_1) {
                    return [];
                } elseif ($user_id === $non_project_member_2) {
                    return [['date' => 1579267252]];
                }

                throw new \LogicException('must not be here.');
            }
        );

        $this->dao->method('delayForBeingSubscribed')
            ->with($non_project_member_1, $last_remove)
            ->willReturn([[null]]);

        $this->dao->method('suspendAccount')->withConsecutive([$non_project_member_1], [$non_project_member_2]);

        $this->dao->expects(self::once())->method('suspendInactiveAccounts')->with($last_valid_access);
        $this->dao->expects(self::once())->method('suspendExpiredAccounts')->with($test_date);

        $this->user_suspension_manager->checkUserAccountValidity($test_date);
    }

    public function testSendNotificationMailToInactiveAccountsIsCalled()
    {
        $test_date        = (new \DateTimeImmutable())->setTimestamp(1579616700);
        $last_access_date = (new \DateTimeImmutable())->setTimestamp(1578752699);

        // Disable mailing to idle accounts
        ForgeConfig::set('sys_suspend_inactive_accounts_notification_delay', 0);

        $inactive_user = new PFUser(["email" => "valid_mail@domain.com", "user_id" => 111, "user_name" => "inactive_user", "language_id" => "fr_FR"]);

        $this->dao->method('getUsersWithoutConnectionOrAccessBetweenDates')
            ->willReturn([['user_id' => 111, 'last_access_date' => 1578752699]]);
        $this->user_manager->method('getUserbyId')->with(111)->willReturn($inactive_user);
        $this->lang_factory->method('getBaseLanguage')->willReturn($this->language);
        $this->user_suspension_logger->method('info');
        $this->mail_presenter_factory->method('createMailAccountSuspensionPresenter')
            ->with($last_access_date, $this->language)
            ->willReturn($this->mail_account_suspension_presenter);

        $this->mail->method('setFrom')->with(ForgeConfig::get('sys_noreply'));
        $this->mail->method('setTo')->with($inactive_user->getEmail());
        $this->mail->method('setSubject');
        $this->mail->method('setBodyHtml');
        $this->mail->expects(self::atLeast(1))->method('send')->willReturn(true);
        $this->template_renderer->method('renderToString')
            ->with('mail-suspension', $this->mail_account_suspension_presenter)
            ->willReturn('Rendered_Email');

        $this->user_suspension_manager->sendSuspensionMailToInactiveAccounts($test_date);
    }
}
