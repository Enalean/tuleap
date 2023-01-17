<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Register;

use Feedback;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\BaseLayout;
use Tuleap\User\LogUser;
use TuleapRegisterMail;
use User_UserStatusManager;

final class AfterSuccessfulUserRegistration implements AfterSuccessfulUserRegistrationHandler
{
    public function __construct(
        private IDisplayConfirmationPage $confirmation_page,
        private TuleapRegisterMail $user_register_mail_builder,
        private TuleapRegisterMail $admin_register_mail_builder,
        private string $base_url,
        private EventDispatcherInterface $event_dispatcher,
        private LogUser $log_user,
    ) {
    }

    public function afterSuccessfullUserRegistration(
        PFUser $new_user,
        HTTPRequest $request,
        BaseLayout $layout,
        string $mail_confirm_code,
        RegisterFormContext $context,
    ): void {
        $this->event_dispatcher->dispatch(
            new AfterUserRegistrationEvent($request, $new_user)
        );

        if ($context->is_admin) {
            if ($request->get('form_send_email')) {
                $is_sent = $this->sendLoginByMailToUser(
                    (string) $request->get('form_email'),
                    (string) $request->get('form_loginname')
                );

                if (! $is_sent) {
                    $layout->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText(
                            'global',
                            'mail_failed',
                            [ForgeConfig::get('sys_email_admin')]
                        )
                    );
                }
            }

            $this->confirmation_page->displayConfirmationForAdmin($layout, $request);
            return;
        }

        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL)) {
            $this->confirmation_page->displayWaitForApproval($layout, $request);
            return;
        }

        if ($context->invitation_to_email) {
            $this->automagicallyLogUser($new_user, $request, $layout);
            return;
        }

        if (
            ! $this->sendNewUserEmail(
                (string) $request->get('form_email'),
                $new_user->getUserName(),
                $mail_confirm_code
            )
        ) {
            $this->confirmation_page->displayConfirmationLinkError($layout);
            return;
        }

        $this->confirmation_page->displayConfirmationLinkSent($layout, $request);
    }

    private function automagicallyLogUser(PFUser $new_user, HTTPRequest $request, BaseLayout $layout): void
    {
        $this->log_user->login($new_user->getUserName(), new ConcealedString($request->get('form_pw')));
        $layout->redirect('/my/?' . http_build_query([
            'invitation-token' => $request->get('invitation-token'),
        ]));
    }

    private function sendLoginByMailToUser(string $to, string $login): bool
    {
        return $this->admin_register_mail_builder
            ->getMail(
                $login,
                '',
                $this->base_url,
                ForgeConfig::get('sys_noreply'),
                $to,
                "admin"
            )
            ->send();
    }

    private function sendNewUserEmail(string $to, string $login, string $confirm_hash): bool
    {
        return $this->user_register_mail_builder
            ->getMail(
                $login,
                $confirm_hash,
                $this->base_url,
                ForgeConfig::get('sys_noreply'),
                $to,
                "user"
            )
            ->send();
    }
}
