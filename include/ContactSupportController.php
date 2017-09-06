<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\MyTuleapContactSupport;

use HTTPRequest;
use Codendi_Mail;
use ForgeConfig;
use MustacheRenderer;
use Valid_Email;
use UserManager;
use Tuleap\MyTuleapContactSupport\Presenter\ModalPresenter;
use Tuleap\MyTuleapContactSupport\Presenter\EmailToSupportPresenter;
use Tuleap\MyTuleapContactSupport\Presenter\ConfirmationEmailToUserPresenter;

class ContactSupportController
{
    /** @var MustacheRenderer */
    private $renderer;

    public function __construct(MustacheRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function displayModalContent(HTTPRequest $request)
    {
        $modal_presenter = new ModalPresenter(
            ForgeConfig::get('sys_email_admin'),
            $this->getHelpPageContent()
        );
        echo $this->renderer->renderToString('modal', $modal_presenter);
    }

    private function getHelpPageContent()
    {
        ob_start();
        include($GLOBALS['Language']->getContent('help/site'));
        return ob_get_clean();
    }

    public function contactSupport(HTTPRequest $request)
    {
        $sys_https_host  = ForgeConfig::get('sys_https_host');
        $current_user    = UserManager::instance()->getCurrentUser();
        $message_title   = $request->get('message-title');
        $message_content = $request->get('message-content');

        try {
            $this->sendEmailToMyTuleapSupport($sys_https_host, $current_user, $message_title, $message_content);
            $this->sendConfirmationEmailToUser($sys_https_host, $current_user, $message_title, $message_content);

            $GLOBALS['Response']->sendStatusCode(200);
        } catch (EmailSendException $exception) {
            $GLOBALS['Response']->sendStatusCode(500);
        }
    }

    private function sendEmailToMyTuleapSupport($sys_https_host, $current_user, $message_title, $message_content)
    {
        $email_presenter = new EmailToSupportPresenter(
            $sys_https_host,
            $current_user->getRealName(),
            $message_title,
            $message_content
        );

        $email_body = $this->renderer->renderToString('email-to-support', $email_presenter);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo(MYTULEAP_CONTACT_SUPPORT_EMAIL_TO);
        $mail->addAdditionalHeader('Reply-To', $current_user->getRealName().' <'.$current_user->getEmail().'>');
        $mail->setSubject('[myTuleap '.$sys_https_host.'] '.$message_title);
        $mail->setBodyHtml($email_body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);

        if (! $mail->send()) {
            throw new EmailSendException('Unable to send the email to ' . MYTULEAP_CONTACT_SUPPORT_EMAIL_TO);
        }
    }

    private function sendConfirmationEmailToUser($sys_https_host, $current_user, $message_title, $message_content)
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
