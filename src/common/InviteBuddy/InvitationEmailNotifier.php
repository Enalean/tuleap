<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Codendi_Mail;
use ForgeConfig;
use PFUser;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\mail\TemplateWithoutFooter;
use Tuleap\ServerHostname;

class InvitationEmailNotifier
{
    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    public function __construct()
    {
        $this->template_renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates/invite_buddy");
    }

    public function send(\PFUser $current_user, InvitationRecipient $recipient, ?string $custom_message): bool
    {
        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate(new TemplateWithoutFooter());
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->addAdditionalHeader('Reply-To', $current_user->getEmail());

        if ($recipient->user) {
            $this->askToLogin($mail, $current_user, $recipient->user, $custom_message);
        } else {
            $this->askToRegister($mail, $current_user, $recipient->email, $custom_message);
        }

        return $mail->send();
    }

    public function askToLogin(
        Codendi_Mail $mail,
        \PFUser $current_user,
        PFUser $recipient_user,
        ?string $custom_message,
    ): void {
        $mail->setTo($recipient_user->getEmail());
        $mail->setSubject(sprintf(_('Invitation to log on to %s'), ForgeConfig::get('sys_name')));

        $login_url = ServerHostname::HTTPSUrl() . '/account/login.php';

        $presenter = new InvitationEmailLoginPresenter($current_user, $recipient_user, $login_url, $custom_message);
        $body      = $this->template_renderer->renderToString("invite-login", $presenter);
        $body_text = $this->template_renderer->renderToString("invite-login-text", $presenter);

        $mail->setBodyHtml($body);
        $mail->setBodyText($body_text);
    }

    public function askToRegister(
        Codendi_Mail $mail,
        \PFUser $current_user,
        string $external_email,
        ?string $custom_message,
    ): void {
        $mail->setTo($external_email);
        $mail->setSubject(sprintf(_('Invitation to register to %s'), ForgeConfig::get('sys_name')));

        $register_url = ServerHostname::HTTPSUrl() . '/account/register.php';

        $presenter = new InvitationEmailRegisterPresenter($current_user, $register_url, $custom_message);
        $body      = $this->template_renderer->renderToString("invite-register", $presenter);
        $body_text = $this->template_renderer->renderToString("invite-register-text", $presenter);

        $mail->setBodyHtml($body);
        $mail->setBodyText($body_text);
    }
}
