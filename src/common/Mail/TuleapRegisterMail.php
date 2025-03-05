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

use Tuleap\Language\LocaleSwitcher;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TuleapRegisterMail
{
    public function __construct(
        private MailPresenterFactory $mail_presenter_factory,
        private TemplateRenderer $renderer,
        private UserManager $user_manager,
        private LocaleSwitcher $locale_switcher,
        private string $template,
    ) {
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMail($login, $confirm_hash, $base_url, $from, $to, $presenter_role)
    {
        $user = $this->user_manager->getUserByLoginName($login);
        if (! $user) {
            throw new LogicException(
                "User $login not found. This is not expected."
            );
        }

        return $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $user->getLocale(),
            function () use ($user, $login, $confirm_hash, $base_url, $from, $to, $presenter_role): Codendi_Mail {
                if ($presenter_role === 'user') {
                    $subject = $user->getLanguage()->getText('include_proj_email', 'account_register', ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
                    include($user->getLanguage()->getContent('include/new_user_email'));
                } elseif ($presenter_role === 'admin') {
                    $subject = sprintf(_('Welcome to %1$s!'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
                    include($user->getLanguage()->getContent('account/new_account_email'));
                } elseif ($presenter_role === 'admin-notification') {
                    $redirect_url = $base_url . '/admin/approve_pending_users.php?page=pending';
                    $subject      = sprintf(_('New User Registered: %1$s'), $login);
                    $message      = $this->createNotificationMessageText($login, $redirect_url);
                } else {
                    $subject = sprintf(_('Your account has been created on %s'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
                    include($user->getLanguage()->getContent('admin/new_account_email'));
                }

                $mail     = new Codendi_Mail();
                $cid_logo = $this->addLogoInAttachment($mail);
                $mail->setSubject($subject);
                $mail->setTo($to);
                $mail->setBodyHtml(
                    $this->renderer->renderToString(
                        $this->template,
                        $this->mail_presenter_factory->createMailAccountPresenter(
                            $user,
                            $login,
                            $confirm_hash,
                            $presenter_role,
                            $cid_logo,
                        )
                    ),
                    Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL
                );
                $mail->setBodyText($message);
                $mail->setFrom($from);

                return $mail;
            }
        );
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMailProject($subject, $from, $to, $project)
    {
        $mail     = new Codendi_Mail();
        $cid_logo = $this->addLogoInAttachment($mail);
        $mail->setSubject($subject);
        $mail->setTo($to);

        $presenter = $this->mail_presenter_factory->createMailProjectPresenter($project, $cid_logo);

        $mail->setBodyHtml($this->renderer->renderToString($this->template, $presenter));
        $mail->setBodyText($presenter->getMessageText());
        $mail->setFrom($from);

        return $mail;
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMailNotificationProject($subject, $from, $to, $project)
    {
        $mail     = new Codendi_Mail();
        $cid_logo = $this->addLogoInAttachment($mail);
        $mail->setSubject($subject);
        $mail->setTo($to);

        $presenter = $this->mail_presenter_factory->createMailProjectNotificationPresenter($project, $cid_logo);

        $mail->setBodyHtml($this->renderer->renderToString($this->template, $presenter));
        $mail->setBodyText($presenter->getMessageText());
        $mail->setFrom($from);

        return $mail;
    }

    /**
     * Create a message without html.
     *
     * @return string
     */
    private function createNotificationMessageText($login, $redirect_url)
    {
        $message = _('Account creation!') . "\n\n"
           . sprintf(_('A new user has just registered on %1$s.

User Name:'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)) . ' '
           . $login . _('.') . "\n\n"
           . _('Please click on the following URL to approve the registration:') . "\n\n"
           . '<' . $redirect_url . ">\n\n"
           . _('Thanks!') . "\n\n"
           . sprintf(_('- The team at %1$s.'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)) . "\n\n";

        return $message;
    }

    private function addLogoInAttachment(Codendi_Mail $mail): string
    {
        $logo_retriever = new LogoRetriever();
        $cid_logo       = '';
        $path_logo      = $logo_retriever->getLegacyPath();
        if ($path_logo) {
            $id_attachment = 'logo@tuleap';
            $mail->addInlineAttachment(file_get_contents($path_logo), $logo_retriever->getMimetype(), $id_attachment);
            $cid_logo = 'cid:' . $id_attachment;
        }

        return $cid_logo;
    }
}
