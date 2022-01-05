<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\MyTuleapContactSupport;

use Codendi_Mail;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\MyTuleapContactSupport\Presenter\ConfirmationEmailToUserPresenter;
use Tuleap\MyTuleapContactSupport\Presenter\EmailToSupportPresenter;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UserManager;

class SendMailSupportController implements DispatchableWithRequest
{
    /** @var TemplateRenderer */
    private $renderer;

    /**
     * @var string
     */
    private $contact_support_email;

    public function __construct(TemplateRenderer $renderer, string $contact_support_email)
    {
        $this->renderer              = $renderer;
        $this->contact_support_email = $contact_support_email;
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws EmailSendException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $sys_https_host  = (string) ForgeConfig::get('sys_https_host');
        $current_user    = UserManager::instance()->getCurrentUser();
        $message_title   = (string) $request->get('message-title');
        $message_content = (string) $request->get('message-content');

        $this->sendEmailToMyTuleapSupport($sys_https_host, $current_user, $message_title, $message_content);

        if (! $current_user->isAnonymous()) {
            $this->sendConfirmationEmailToUser($sys_https_host, $current_user, $message_title, $message_content);
        }
    }

    /**
     * @throws EmailSendException
     */
    private function sendEmailToMyTuleapSupport(
        string $sys_https_host,
        PFUser $current_user,
        string $message_title,
        string $message_content,
    ): void {
        $current_user_name = 'Anonymous';

        if (! $current_user->isAnonymous()) {
            $current_user_name = $current_user->getRealName();
        }

        $email_presenter = new EmailToSupportPresenter(
            $sys_https_host,
            $current_user_name,
            $message_title,
            $message_content
        );

        $email_body = $this->renderer->renderToString('email-to-support', $email_presenter);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($this->contact_support_email);

        if (! $current_user->isAnonymous()) {
            $mail->addAdditionalHeader('Reply-To', $current_user_name . ' <' . $current_user->getEmail() . '>');
        }

        $mail->setSubject('[myTuleap ' . $sys_https_host . '] ' . $message_title);
        $mail->setBodyHtml($email_body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);

        if (! $mail->send()) {
            throw new EmailSendException('Unable to send the email to ' . $this->contact_support_email);
        }
    }

    /**
     * @throws EmailSendException
     */
    private function sendConfirmationEmailToUser(string $sys_https_host, \PFUser $current_user, string $message_title, string $message_content): void
    {
        $email_presenter = new ConfirmationEmailToUserPresenter(
            $sys_https_host,
            $current_user->getRealName(),
            $message_title,
            $message_content
        );

        $email_body = $this->renderer->renderToString('confirmation-email-to-user', $email_presenter);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($current_user->getEmail());
        $mail->setSubject(dgettext('tuleap-mytuleap_contact_support', 'We have well received your message'));
        $mail->setBodyHtml($email_body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);

        if (! $mail->send()) {
            throw new EmailSendException('Unable to send the email to ' . $current_user->getEmail());
        }
    }
}
