<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Mail\MailAccountSuspensionAlertPresenter;
use Tuleap\Mail\MailAccountSuspensionPresenter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MailPresenterFactory
{
    public const FLAMING_PARROT_THEME = 'FlamingParrot';

    /**
     * Create a presenter for email account.
     *
     * @return MailRegisterPresenter|MailNotificationPresenter
     */
    public function createMailAccountPresenter(PFUser $user, $login, $confirm_hash, $presenter_role, string $logo_url)
    {
        $color_logo   = '#000';
        $color_button = '#347DBA';

        $base_url = \Tuleap\ServerHostname::HTTPSUrl();
        $this->setColorTheme($color_logo, $color_button);

        $attributes_presenter = [
            'login'         => $login,
            'color_logo'    => $color_logo,
            'color_button'  => $color_button,
            'confirm_hash'  => $confirm_hash,
            'base_url'      => $base_url,
            'logo_url'      => $logo_url,
        ];

        if ($presenter_role === 'user') {
            $presenter = $this->createUserEmailPresenter($user, $attributes_presenter);
        } elseif ($presenter_role === 'admin') {
            $presenter = $this->createAdminEmailPresenter($user, $attributes_presenter);
        } elseif ($presenter_role === 'admin-notification') {
            $presenter = $this->createAdminNotificationPresenter($attributes_presenter);
        } else {
            $presenter = $this->createApprovalEmailPresenter($user, $attributes_presenter);
        }
        return $presenter;
    }

    /**
     * Create a presenter for email project.
     *
     */
    public function createMailProjectPresenter(Project $project, $logo_url)
    {
        $color_logo = '#000';

        $this->setColorTheme($color_logo);
        $presenter = $this->createMailProjectRegisterPresenter($project, $color_logo, $logo_url);

        return $presenter;
    }

    /**
     * Create a presenter for email notifiaction project.
     *
     */
    public function createMailProjectNotificationPresenter(Project $project, $logo_url)
    {
        $color_logo   = '#000';
        $color_button = '#347DBA';

        $this->setColorTheme($color_logo, $color_button);
        if ($project->projectsMustBeApprovedByAdmin()) {
            $presenter = $this->createMailProjectNotificationMustBeApprovedPresenter($project, $color_logo, $logo_url, $color_button);
        } else {
            $presenter = $this->createMailProjectRegisterNotificationPresenter($project, $color_logo, $logo_url, $color_button);
        }

        return $presenter;
    }

    /**
     * Create a presenter for admin
     * account register.
     *
     * @return MailRegisterByAdminPresenter
     */
    private function createAdminEmailPresenter(PFUser $user, array $attributes_presenter)
    {
        $login = $attributes_presenter['login'];

        include($user->getLanguage()->getContent('account/new_account_email'));
        $presenter = new MailRegisterByAdminPresenter(
            $attributes_presenter['logo_url'],
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $attributes_presenter['color_logo'],
            $login,
            $section_three
        );
        return $presenter;
    }

    /**
     * Create a presenter for user
     * account register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createUserEmailPresenter(PFUser $user, array $attributes_presenter)
    {
        $base_url     = $attributes_presenter['base_url'];
        $login        = $attributes_presenter['login'];
        $confirm_hash = $attributes_presenter['confirm_hash'];

        include($user->getLanguage()->getContent('include/new_user_email'));
        $redirect_url = $base_url . "/account/login.php?confirm_hash=$confirm_hash";

        $presenter = new MailRegisterByUserPresenter(
            $attributes_presenter['logo_url'],
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $attributes_presenter['color_logo'],
            $login,
            $redirect_url,
            $redirect_button,
            $attributes_presenter['color_button']
        );
        return $presenter;
    }

    /**
     * Create a presenter for admin notification.
     */
    private function createAdminNotificationPresenter(array $attributes_presenter): MailRegisterByAdminNotificationPresenter
    {
        $base_url     = $attributes_presenter['base_url'];
        $redirect_url = $base_url . '/admin/approve_pending_users.php?page=pending';

        $presenter = new MailRegisterByAdminNotificationPresenter(
            $attributes_presenter['logo_url'],
            _('Account creation!'),
            sprintf(_('A new user has just registered on %1$s.

User Name:'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $attributes_presenter['login']),
            _('Please click on the following URL to approve the registration:'),
            _('Thanks!'),
            sprintf(_('- The team at %1$s.'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)),
            $attributes_presenter['color_logo'],
            $redirect_url,
            _('Confirm the account creation'),
            $attributes_presenter['color_button'],
            $attributes_presenter['login'],
            '.'
        );
        return $presenter;
    }

    /**
     * Create a presenter for approval account register.
     */
    private function createApprovalEmailPresenter(PFUser $user, array $attributes_presenter): MailRegisterByAdminApprovalPresenter
    {
        $base_url = $attributes_presenter['base_url'];
        $login    = $attributes_presenter['login'];

        include($user->getLanguage()->getContent('admin/new_account_email'));

        $presenter = new MailRegisterByAdminApprovalPresenter(
            $attributes_presenter['logo_url'],
            $title,
            $section_one,
            $section_two,
            '',
            $thanks,
            $signature,
            $help,
            $attributes_presenter['color_logo'],
            $login,
            $section_three
        );
        return $presenter;
    }

    /**
     * Create a presenter for project register.
     */
    private function createMailProjectRegisterPresenter(Project $project, $color_logo, $logo_url): MailProjectOneStepRegisterPresenter
    {
        $presenter = new MailProjectOneStepRegisterPresenter(
            $project,
            $color_logo,
            $logo_url
        );

        return $presenter;
    }

    /**
     * Create a presenter for project register.
     */
    private function createMailProjectRegisterNotificationPresenter(Project $project, $color_logo, $logo_url, $color_button): MailProjectNotificationPresenter
    {
        $presenter = new MailProjectNotificationPresenter(
            $project,
            $color_logo,
            $logo_url,
            $color_button
        );

        return $presenter;
    }

    /**
     * Create a presenter for project register, which must be approved
     */
    private function createMailProjectNotificationMustBeApprovedPresenter(Project $project, $color_logo, $logo_url, $color_button): MailProjectNotificationMustBeApprovedPresenter
    {
        $presenter = new MailProjectNotificationMustBeApprovedPresenter(
            $project,
            $color_logo,
            $logo_url,
            $color_button
        );

        return $presenter;
    }

    /**
     * Return the color of theme with references parameters
     */
    private function setColorTheme(&$color_logo = null, &$color_button = null): void
    {
        $theme_variant       = new ThemeVariant();
        $defaultThemeVariant = $theme_variant->getDefault();
        $color_logo          = $defaultThemeVariant->getHexaCode();
        $color_button        = $color_logo;
    }

    /**
     * Creates a presenter for the account suspension notification email
     *
     */
    public function createMailAccountSuspensionAlertPresenter(DateTimeImmutable $last_access_date, DateTimeImmutable $suspension_date, BaseLanguage $language): MailAccountSuspensionAlertPresenter
    {
        $color_logo = '#000';
        $this->setColorTheme($color_logo);
        $logo_retriever = new LogoRetriever();
        $logo_url       = $logo_retriever->getLegacyUrl();
        return new MailAccountSuspensionAlertPresenter((string) $logo_url, $color_logo, $last_access_date, $suspension_date, $language);
    }

    /**
     * Creates a presenter for the account suspension email
     *
     */
    public function createMailAccountSuspensionPresenter(DateTimeImmutable $last_access_date, BaseLanguage $language): MailAccountSuspensionPresenter
    {
        $color_logo = '#000';
        $this->setColorTheme($color_logo);
        $logo_retriever = new LogoRetriever();
        $logo_url       = $logo_retriever->getLegacyUrl();
        return new MailAccountSuspensionPresenter((string) $logo_url, $color_logo, $last_access_date, $language);
    }
}
