<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\User\Account;

use Tuleap\Layout\BaseLayout;

class ChangePasswordController
{
    public const URL = '/account/change_email.php';

    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\UserManager $user_manager, \EventManager $event_manager)
    {
        $this->user_manager  = $user_manager;
        $this->event_manager = $event_manager;
    }

    public function change(\HTTPRequest $request, BaseLayout $response)
    {
        $this->event_manager->processEvent('before_change_email', array());

        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $response->addFeedback(\Feedback::ERROR, _('Unauthorized action for anonymous'));
            $response->redirect('/');
        }

        $response->header(['title' =>_('Change email address')]);

        $presenter = new ChangePasswordPresenter(
            new \CSRFSynchronizerToken(self::URL),
            $current_user->getId()
        );

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__.'/../../../templates/user');
        $renderer->renderToPage('change-email', $presenter);

        $response->footer(array());
    }

    public function confirm(\HTTPRequest $request, BaseLayout $response)
    {
        $this->event_manager->processEvent('before_change_email-confirm', []);

        $token = new \CSRFSynchronizerToken(self::URL);
        $token->check();

        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $response->addFeedback(\Feedback::ERROR, _('Unauthorized action for anonymous'));
            $response->redirect('/');
        }

        $new_mail = $request->getValidated('form_newemail', new \Valid_Email(), '');
        if ($new_mail === '') {
            $response->addFeedback(\Feedback::ERROR, _('Email format invalid'));
            $response->redirect('/change_email.php');
        }

        $confirmation_hash = (new \RandomNumberGenerator())->getNumber();
        $this->user_manager->setEmailChangeConfirm($current_user->getId(), $confirmation_hash, $new_mail);

        $subject = sprintf(
            _('[%s] Email change confirmation'),
            \ForgeConfig::get('sys_name')
        );
        $message = sprintf(
            _("You have requested a change of email address on %s.\nPlease visit the following URL to complete the email change:\n\n%s\n\n-- The %s Team"),
            \ForgeConfig::get('sys_name'),
            $request->getServerUrl().$this->getChangeCompleteUrl($confirmation_hash),
            \ForgeConfig::get('sys_name')
        );

        $mail = new \Codendi_Mail();
        $mail->setTo($new_mail, true);
        $mail->setSubject($subject);
        $mail->setBodyText($message);
        $mail->setFrom(\ForgeConfig::get('sys_noreply'));
        if (! $mail->send()) {
            $error_message = sprintf(_('The mail was not accepted for the delivery. Please contact the administrator at %s.'), \ForgeConfig::get('sys_email_admin'));
            $response->addFeedback(\Feedback::ERROR, $error_message);
            $response->redirect('/change_email.php');
        }

        $response->header(['title' => _('Email change confirmation')]);

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__.'/../../../templates/user');
        $renderer->renderToPage('change-email-confirm', []);

        $response->footer([]);
    }

    public function complete(\HTTPRequest $request, BaseLayout $response)
    {
        $this->event_manager->processEvent('before_change_email-complete', []);

        $confirmation_hash = $request->getValidated('confirm_hash', 'string', '');
        $current_user      = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $url_redirect  = new \URLRedirect($this->event_manager);
            $return_to     = $this->getChangeCompleteUrl($confirmation_hash);
            $response->redirect($url_redirect->makeReturnToUrl('/account/login.php', $return_to));
        }

        if (! hash_equals($current_user->getConfirmHash(), $confirmation_hash)) {
            $response->addFeedback(\Feedback::ERROR, _('You are not the user who asked for email change'));
            $response->redirect('/');
        }

        $old_email_user = clone $current_user;
        $current_user->clearConfirmHash();
        $current_user->setEmail($old_email_user->getEmailNew());
        $current_user->setEmailNew($old_email_user->getEmail());

        $this->user_manager->updateDb($current_user);

        $response->header(['title' => _('Email change complete')]);

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__.'/../../../templates/user');
        $renderer->renderToPage(
            'change-email-complete',
            [
                'realname' => $current_user->getRealName(),
                'email'    => $current_user->getEmail(),
            ]
        );

        $response->footer([]);
    }

    private function getChangeCompleteUrl($confirmation_hash)
    {
        return '/account/change_email-complete.php?confirm_hash='.$confirmation_hash;
    }
}
