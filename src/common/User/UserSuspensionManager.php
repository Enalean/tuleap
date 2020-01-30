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
use DateTimeImmutable;
use ForgeConfig;
use MailPresenterFactory;
use PFUser;
use TemplateRenderer;
use Tuleap\Dao\UserSuspensionDao;
use UserManager;

class UserSuspensionManager
{
    public const CONFIG_NOTIFICATION_DELAY = 'sys_suspend_inactive_accounts_notification_delay';
    public const CONFIG_INACTIVE_DELAY = 'sys_suspend_inactive_accounts_delay';

    /**
     * @var MailPresenterFactory
     */
    private $mail_presenter_factory;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $template;

    /**
     * @var Codendi_Mail
     */
    private $mail ;

    /**
     * @var UserSuspensionDao
     */
    private $dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var BaseLanguageFactory
     */
    private $lang_factory;

    /**
     * @var UserSuspensionLogger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param MailPresenterFactory $mail_presenter_factory
     * @param TemplateRenderer $renderer
     * @param string $template
     * @param Codendi_Mail $mail
     * @param UserSuspensionDao $dao
     * @param UserManager $user_manager
     * @param BaseLanguageFactory $lang_factory
     * @param UserSuspensionLogger $logger
     */
    public function __construct(
        MailPresenterFactory $mail_presenter_factory,
        TemplateRenderer $renderer,
        string $template,
        Codendi_Mail $mail,
        UserSuspensionDao $dao,
        UserManager $user_manager,
        BaseLanguageFactory $lang_factory,
        UserSuspensionLogger $logger
    ) {
        $this->mail_presenter_factory = $mail_presenter_factory;
        $this->renderer = $renderer;
        $this->template = $template;
        $this->mail = $mail;
        $this->dao = $dao;
        $this->user_manager = $user_manager->instance();
        $this->lang_factory = $lang_factory;
        $this->logger = $logger;
    }

    /**
     * Sends email alerts for all idle user accounts
     *
     * @return bool
     */
    public function sendNotificationMailToIdleAccounts() : bool
    {
        $inactive_delay = (int)ForgeConfig::get(self::CONFIG_INACTIVE_DELAY);
        $notification_delay = (int)ForgeConfig::get(self::CONFIG_NOTIFICATION_DELAY);
        $result = true;

        if (($notification_delay > 0) && ($inactive_delay > 0)) {
            $idle_users = $this->getIdleAccounts($notification_delay, $inactive_delay);

            $users = array_column($idle_users, 'user_id');
            $this->logger->info(
                "Sending the suspension notification to the following users (ID): " .
                 implode(", ", $users)
            );

            foreach ($idle_users as $idle_user) {
                $user = $this->user_manager->getUserbyId($idle_user['user_id']);
                if ($user) {
                    $suspension_date = $this->getSuspensionDate($notification_delay);
                    $last_access_date = new DateTimeImmutable('@' . $idle_user['last_access_date']);
                    $language = $this->lang_factory->getBaseLanguage(ForgeConfig::get('sys_lang'));
                    $status = $this->sendNotificationMail($user, $last_access_date, $suspension_date, $language);
                    if ($status) {
                        $this->logger->info(
                            "Suspension notification is sent to user: ID=" .
                            $user->getId() . " username=" . $user->getUserName() .
                            " email=" . $user->getEmail() . " last_access_date=" .
                            date('Y-m-d\TH:i:sO', $idle_user['last_access_date'])
                        );
                    } else {
                        $this->logger->error(
                            "Unable to send suspension notification to user: ID=" .
                            $user->getId() . " username=" . $user->getUserName() .
                            " email=" . $user->getEmail()
                        );
                    }
                    $result = $result && $status;
                }
            }
        }
        return $result;
    }

    /**
     * Sends suspension notification to user
     *
     * @param PFUser $user
     * @param DateTimeImmutable $last_access_date
     * @param DateTimeImmutable $suspension_date
     * @param BaseLanguage $language
     *
     * @return bool True if sent, false otherwise
     */
    private function sendNotificationMail(PFUser $user, DateTimeImmutable $last_access_date, DateTimeImmutable $suspension_date, BaseLanguage $language) : bool
    {
        $presenter = $this->mail_presenter_factory->createMailAccountSuspensionAlertPresenter($last_access_date, $suspension_date, $language);
        $this->mail->setFrom(ForgeConfig::get('sys_noreply'));
        $this->mail->setTo($user->getEmail());
        $subject = sprintf(_('%s - Account suspension notification'), ForgeConfig::get('sys_name'));
        $this->mail->setSubject($subject);
        $this->mail->setBodyHtml($this->renderer->renderToString($this->template, $presenter), Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
        return $this->mail->send();
    }

    /**
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     * @param int $inactive_delay Inactive accounts delay (number of days after since login)
     *
     * @return array
     */
    private function getIdleAccounts(int $notification_delay, int $inactive_delay)
    {
        $start_date = $this->getLastAccessDate($notification_delay, $inactive_delay);
        $end_date = $start_date->modify('+23hours 59 minutes 59 seconds');
        $this->logger->info(
            "Querying users that last accessed " . ForgeConfig::get('sys_name') .
            " between " . $start_date->format('Y-m-d\TH:i:sO') . " and " . $end_date->format('Y-m-d\TH:i:sO')
        );
        return $this->dao->getIdleAccounts($start_date, $end_date);
    }

    /**
     *
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     * @param int $inactive_delay   Inactive accounts delay (number of days after last login)
     *
     * @return DateTimeImmutable
     */
    private function getLastAccessDate(int $notification_delay, int $inactive_delay) : DateTimeImmutable
    {
        $date = new DateTimeImmutable('today');
        $date_param = '- ' . ($inactive_delay - $notification_delay) . ' day';
        return $date->modify($date_param);
    }

    /**
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     * @return DateTimeImmutable
     */
    private function getSuspensionDate(int $notification_delay) : DateTimeImmutable
    {
        $date = new DateTimeImmutable('today');
        $date_param = '+ ' .  $notification_delay . ' day';
        return $date->modify($date_param);
    }

    /**
     * Check user account validity against several rules
     * - Account expiry date
     * - Last user access
     * - User not member of a project
     * All rules apply at midnight
     * @param DateTimeImmutable $date
     */
    public function checkUserAccountValidity(DateTimeImmutable $date)
    {
        $this->suspendExpiredAccounts($date);
        $this->suspendInactiveAccounts($date);
        $this->suspendUserNotProjectMembers($date);
    }

    /**
     * Change account status to suspended when the account expiry date is passed
     *
     * @param DateTimeImmutable $time
     */
    private function suspendExpiredAccounts(DateTimeImmutable $time)
    {
        return $this->dao->suspendExpiredAccounts($time);
    }

    /**
     * Suspend accounts that without activity since date defined in configuration
     *
     * @param DateTimeImmutable $time
     */
    private function suspendInactiveAccounts(DateTimeImmutable $time)
    {
        if (ForgeConfig::exists('sys_suspend_inactive_accounts_delay') && ForgeConfig::get('sys_suspend_inactive_accounts_delay') > 0) {
            $date_param = '- ' . ForgeConfig::get('sys_suspend_inactive_accounts_delay') . ' day';
            $lastValidAccess = $time->modify($date_param);
            return $this->dao->suspendInactiveAccounts($lastValidAccess);
        }
        return true;
    }

    /**
     * Change account status to suspended when user is no more member of any project
     *
     * @param DateTimeImmutable $time
     */
    private function suspendUserNotProjectMembers(DateTimeImmutable $time)
    {
        if (ForgeConfig::exists('sys_suspend_non_project_member_delay') && ForgeConfig::get('sys_suspend_non_project_member_delay') > 0) {
            $date_param = '- ' . ForgeConfig::get('sys_suspend_non_project_member_delay') . ' day';
            $lastRemove = $time->modify($date_param);
            return $this->dao->suspendUserNotProjectMembers($lastRemove);
        }
    }
}
