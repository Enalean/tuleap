<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
use DateInterval;
use ForgeConfig;
use MailPresenterFactory;
use PFUser;
use Psr\Log\LoggerInterface;
use TemplateRenderer;
use Tuleap\Config\ConfigKey;
use Tuleap\Dao\UserSuspensionDao;
use Tuleap\Language\LocaleSwitcher;
use UserManager;

class UserSuspensionManager
{
    #[ConfigKey("Send an email X number of days before an inactive user will be suspended")]
    public const CONFIG_NOTIFICATION_DELAY = 'sys_suspend_inactive_accounts_notification_delay';
    #[ConfigKey("Toggle activation of notification of inactive accounts")]
    public const CONFIG_INACTIVE_EMAIL     = 'sys_suspend_send_account_suspension_email';
    public const CONFIG_INACTIVE_DELAY     = 'sys_suspend_inactive_accounts_delay';
    public const ONE_DAY_INTERVAL          = "PT23H59M59S";

    /**
     * @var MailPresenterFactory
     */
    private $mail_presenter_factory;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var Codendi_Mail
     */
    private $mail;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LocaleSwitcher
     */
    private $locale_switcher;

    /**
     * Constructor
     */
    public function __construct(
        MailPresenterFactory $mail_presenter_factory,
        TemplateRenderer $renderer,
        Codendi_Mail $mail,
        UserSuspensionDao $dao,
        UserManager $user_manager,
        BaseLanguageFactory $lang_factory,
        LoggerInterface $logger,
        LocaleSwitcher $locale_switcher,
    ) {
        $this->mail_presenter_factory = $mail_presenter_factory;
        $this->renderer               = $renderer;
        $this->mail                   = $mail;
        $this->dao                    = $dao;
        $this->user_manager           = $user_manager;
        $this->lang_factory           = $lang_factory;
        $this->logger                 = $logger;
        $this->locale_switcher        = $locale_switcher;
    }

    /**
     * Sends email alerts for all idle user accounts
     */
    public function sendNotificationMailToIdleAccounts(DateTimeImmutable $date): void
    {
        $inactive_delay     = (int) ForgeConfig::get(self::CONFIG_INACTIVE_DELAY);
        $notification_delay = (int) ForgeConfig::get(self::CONFIG_NOTIFICATION_DELAY);

        if (($notification_delay > 0) && ($inactive_delay > 0)) {
            $idle_users = $this->getIdleAccounts($notification_delay, $inactive_delay, $date);
            $users      = array_column($idle_users, 'user_id');

            if ($users) {
                $this->logger->info(
                    "Sending the suspension notification to the following users (ID): " .
                    implode(", ", $users)
                );
            } else {
                $this->logger->info("No users to notify (suspension notification).");
            }

            foreach ($idle_users as $idle_user) {
                $user = $this->user_manager->getUserbyId($idle_user['user_id']);
                if ($user) {
                    $locale           = $user->getLocale();
                    $suspension_date  = $this->getSuspensionDate($notification_delay, $date);
                    $last_access_date = new DateTimeImmutable('@' . $idle_user['last_access_date']);
                    $language         = $this->lang_factory->getBaseLanguage($locale);

                    $this->locale_switcher->setLocaleForSpecificExecutionContext(
                        $locale,
                        function () use ($user, $last_access_date, $suspension_date, $language, $idle_user) {
                            $this->sendAndLogNotificationMailToUser($user, $last_access_date, $suspension_date, $language, $idle_user);
                        }
                    );
                }
            }
        }
    }

    private function sendAndLogNotificationMailToUser(PFUser $user, DateTimeImmutable $last_access_date, DateTimeImmutable $suspension_date, BaseLanguage $language, array $idle_user): void
    {
        if ($this->sendNotificationMail($user, $last_access_date, $suspension_date, $language)) {
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
    }

    /**
     * Sends suspension notification to user
     *
     * @return bool True if sent, false otherwise
     */
    private function sendNotificationMail(PFUser $user, DateTimeImmutable $last_access_date, DateTimeImmutable $suspension_date, BaseLanguage $language): bool
    {
        $presenter = $this->mail_presenter_factory->createMailAccountSuspensionAlertPresenter($last_access_date, $suspension_date, $language);
        $this->mail->setFrom(ForgeConfig::get('sys_noreply'));
        $this->mail->setTo($user->getEmail());
        $subject = sprintf(_('%s - Account suspension notification'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        $this->mail->setSubject($subject);
        $this->mail->setBodyHtml($this->renderer->renderToString('mail-suspension-alert', $presenter), Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
        return $this->mail->send();
    }

    /**
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     * @param int $inactive_delay Inactive accounts delay (number of days after since login)
     * @param DateTimeImmutable $date Execution date
     */
    private function getIdleAccounts(int $notification_delay, int $inactive_delay, DateTimeImmutable $date): array
    {
        $start_date = $this->getLastAccessDate($notification_delay, $inactive_delay, $date);
        $end_date   = $start_date->modify('+23hours 59 minutes 59 seconds');
        $this->logger->info(
            "Idle accounts: querying users that last accessed " . ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) .
            " between " . $start_date->format('Y-m-d\TH:i:sO') . " and " . $end_date->format('Y-m-d\TH:i:sO')
        );
        return $this->dao->getIdleAccounts($start_date, $end_date);
    }

    /**
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     * @param int $inactive_delay   Inactive accounts delay (number of days after last login)
     * @param DateTimeImmutable $date Execution date
     */
    private function getLastAccessDate(int $notification_delay, int $inactive_delay, DateTimeImmutable $date): DateTimeImmutable
    {
        $date_param = '- ' . ($inactive_delay - $notification_delay) . ' day';
        return $date->modify($date_param);
    }

    /**
     * @param int $notification_delay Suspension notification delay (number of days before suspension)
     */
    private function getSuspensionDate(int $notification_delay, DateTimeImmutable $date): DateTimeImmutable
    {
        $date_param = '+ ' .  $notification_delay . ' day';
        return $date->modify($date_param);
    }

    /**
     * Check user account validity against several rules
     * - Account expiry date
     * - Last user access
     * - User not member of a project
     * All rules apply at midnight
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
     */
    private function suspendExpiredAccounts(DateTimeImmutable $time): void
    {
        $this->dao->suspendExpiredAccounts($time);
    }

    /**
     * Suspend accounts that without activity since date defined in configuration
     *
     */
    private function suspendInactiveAccounts(DateTimeImmutable $time): void
    {
        if (ForgeConfig::exists('sys_suspend_inactive_accounts_delay') && ForgeConfig::get('sys_suspend_inactive_accounts_delay') > 0) {
            $date_param      = '- ' . ForgeConfig::get('sys_suspend_inactive_accounts_delay') . ' day';
            $lastValidAccess = $time->modify($date_param);
            $this->dao->suspendInactiveAccounts($lastValidAccess);
        }
    }

    /**
     * Change account status to suspended when user is no more member of any project
     */
    private function suspendUserNotProjectMembers(DateTimeImmutable $time)
    {
        if (ForgeConfig::exists('sys_suspend_non_project_member_delay') && ForgeConfig::get('sys_suspend_non_project_member_delay') > 0) {
            $date_param = '- ' . ForgeConfig::get('sys_suspend_non_project_member_delay') . ' day';
            $lastRemove = $time->modify($date_param);

            $timestamp = $lastRemove->getTimestamp();
            $dar       = $this->dao->returnNotProjectMembers();
            if ($dar) {
                //we should verify the delay for it user has been no more belonging to any project
                foreach ($dar as $row) {
                    $user_id = (int) $row['user_id'];
                    $this->logger->debug("Checking user #$user_id");
                    //we split the treatment in two methods to distinguish between 0 row returned
                    //by the fact that there is no "removed user" entry for this user_id and the case
                    //where it is the result of comparing the date
                    $res = $this->dao->delayForBeingNotProjectMembers($user_id);
                    if (count($res) == 0) {
                        $this->logger->debug("User #$user_id never project member");
                        //Verify add_date
                        $result = $this->dao->delayForBeingSubscribed($user_id, $lastRemove);
                        if ($result) {
                            $this->suspendUser($user_id);
                        } else {
                            $this->logger->debug("User #$user_id not in delay, continue");
                            continue;
                        }
                    } else {
                        //verify if delay has not expired yet
                        $rowLastRemove = $res[0];
                        if ($rowLastRemove['date'] > $timestamp) {
                            $this->logger->debug("User #$user_id not in delay, continue");
                            continue;
                        } else {
                            $this->suspendUser($user_id);
                        }
                    }
                }
            }
            return;
        }
    }

    /**
     *  Suspends and logs user suspension
     */
    private function suspendUser(int $user_id)
    {
        $this->dao->suspendAccount($user_id);
        $this->logger->debug("User #$user_id is suspended");
    }

    /**
     * Sends email alerts for all inactive user accounts
     */
    public function sendSuspensionMailToInactiveAccounts(DateTimeImmutable $date)
    {
        $enable_suspension_mails = (bool) ForgeConfig::get(self::CONFIG_INACTIVE_EMAIL);
        $inactive_accounts_delay = (int) ForgeConfig::get(self::CONFIG_INACTIVE_DELAY);

        if ($enable_suspension_mails !== false && $inactive_accounts_delay > 0) {
            $inactive_users = $this->getInactiveAccounts($date);
            $users          = array_column($inactive_users, 'user_id');

            if ($users) {
                $this->logger->info(
                    "Suspension-day email: sending the email to the following users (ID): " .
                    implode(", ", $users)
                );
            } else {
                $this->logger->info("No users to notify (suspension-day notification).");
            }

            foreach ($inactive_users as $inactive_user) {
                $user = $this->user_manager->getUserbyId($inactive_user['user_id']);
                if ($user) {
                    $locale   = $user->getLocale();
                    $language = $this->lang_factory->getBaseLanguage($locale);
                    if ($inactive_user['last_access_date'] != 0) {
                        $last_access_date = new DateTimeImmutable('@' . $inactive_user['last_access_date']);
                        $this->locale_switcher->setLocaleForSpecificExecutionContext(
                            $locale,
                            function () use ($user, $last_access_date, $language) {
                                $this->sendAndLogSuspensionMailToUser($user, $last_access_date, $language);
                            }
                        );
                    } else {
                        $add_date = new DateTimeImmutable('@' . $user->getAddDate());
                        $this->locale_switcher->setLocaleForSpecificExecutionContext(
                            $locale,
                            function () use ($user, $add_date, $language) {
                                $this->sendAndLogSuspensionMailToUser($user, $add_date, $language);
                            }
                        );
                    }
                }
            }
        }
    }

    private function sendAndLogSuspensionMailToUser(PFUser $user, DateTimeImmutable $last_access_date, BaseLanguage $language)
    {
        if ($this->sendSuspensionMail($user, $last_access_date, $language)) {
            $this->logger->info(
                "Suspension email is sent to user: ID=" .
                $user->getId() . " username=" . $user->getUserName() .
                " email=" . $user->getEmail() . " last_access_date=" .
                $last_access_date->format('Y-m-d\TH:i:sO')
            );
        } else {
            $this->logger->error(
                "Unable to send suspension email to user: ID=" .
                $user->getId() . " username=" . $user->getUserName() .
                " email=" . $user->getEmail()
            );
        }
    }

    private function sendSuspensionMail(PFUser $user, DateTimeImmutable $last_access_date, BaseLanguage $language): bool
    {
        $presenter = $this->mail_presenter_factory->createMailAccountSuspensionPresenter($last_access_date, $language);
        $this->mail->setFrom(ForgeConfig::get('sys_noreply'));
        $this->mail->setTo($user->getEmail());
        $subject = sprintf(_('%s - Account suspension'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        $this->mail->setSubject($subject);
        $this->mail->setBodyHtml($this->renderer->renderToString('mail-suspension', $presenter), Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
        return $this->mail->send();
    }

    private function getInactiveAccounts(DateTimeImmutable $date): array
    {
        $last_valid_access_end   = $date->sub(
            new DateInterval("P" . ForgeConfig::get('sys_suspend_inactive_accounts_delay') . "D")
        );
        $last_valid_access_start = $last_valid_access_end->sub(new DateInterval(self::ONE_DAY_INTERVAL));

        $this->logger->info(
            "Inactive accounts: querying users that last accessed " . ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) .
            " between  " . $last_valid_access_start->format('Y-m-d\TH:i:sO') . "and " .
            $last_valid_access_end->format('Y-m-d\TH:i:sO')
        );
        return $this->dao->getUsersWithoutConnectionOrAccessBetweenDates($last_valid_access_start, $last_valid_access_end);
    }
}
