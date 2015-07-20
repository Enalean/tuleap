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

class TuleapRegisterMail {

    /** @var MailPresenterFactory */
    private $mail_presenter_factory;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var string */
    private $template;

    public function __construct(MailPresenterFactory $mail_presenter_factory, TemplateRenderer $renderer, $template) {
        $this->mail_presenter_factory = $mail_presenter_factory;
        $this->renderer               = $renderer;
        $this->template               = $template;
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMail($login, $password, $confirm_hash, $base_url, $from, $to, $presenter_role) {
        if ($presenter_role == "user") {
            $subject = $GLOBALS['Language']->getText('include_proj_email', 'account_register', $GLOBALS['sys_name']);
            include($GLOBALS['Language']->getContent('include/new_user_email'));
        } else {
            $subject = $GLOBALS['Language']->getText('account_register', 'welcome_email_title', $GLOBALS['sys_name']);
            include($GLOBALS['Language']->getContent('account/new_account_email'));
        }

        $mail = new Codendi_Mail();
        $mail->setSubject($subject);
        $mail->setTo($to);
        $mail->setBodyHtml($this->renderer->renderToString($this->template, $this->mail_presenter_factory->createPresenter($login, $password, $confirm_hash, $presenter_role)));
        $mail->setBodyText($message);
        $mail->setFrom($from);

        return $mail;
    }

}
?>