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
use Tuleap\InviteBuddy\InvitationToEmail;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\User\LogUser;
use User_UserStatusManager;

final class AfterSuccessfulUserRegistration implements AfterSuccessfulUserRegistrationHandler
{
    public function __construct(
        private IDisplayConfirmationPage $confirmation_page,
        private ConfirmationHashEmailSender $confirmation_hash_email_sender,
        private NewUserByAdminEmailSender $new_user_by_admin_email_sender,
        private EventDispatcherInterface $event_dispatcher,
        private LogUser $log_user,
        private ProjectByIDFactory $project_retriever,
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
            new AfterUserRegistrationEvent($new_user)
        );

        if ($context->is_admin) {
            if ($request->get('form_send_email')) {
                $is_sent = $this->new_user_by_admin_email_sender->sendLoginByMailToUser(
                    $new_user->getEmail(),
                    $new_user->getUserName(),
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

            $this->confirmation_page->displayConfirmationForAdmin($layout, $new_user, new ConcealedString((string) $request->get('form_pw')));
            return;
        }

        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL)) {
            $this->confirmation_page->displayWaitForApproval($layout, $new_user);
            return;
        }

        if ($context->invitation_to_email) {
            $this->automagicallyLogUser($new_user, $request, $layout, $context->invitation_to_email);
            return;
        }

        if (
            ! $this->confirmation_hash_email_sender->sendConfirmationHashEmail(
                $new_user->getEmail(),
                $new_user->getUserName(),
                $mail_confirm_code
            )
        ) {
            $this->confirmation_page->displayConfirmationLinkError($layout);
            return;
        }

        $this->confirmation_page->displayConfirmationLinkSent($layout, $new_user);
    }

    private function automagicallyLogUser(
        PFUser $new_user,
        HTTPRequest $request,
        BaseLayout $layout,
        InvitationToEmail $invitation_to_email,
    ): void {
        $this->log_user->login($new_user->getUserName(), new ConcealedString($request->get('form_pw')));
        if (! $invitation_to_email->to_project_id) {
            $this->redirectToUserDashboard($layout);
        } else {
            $this->redirectToProjectDashboard($layout, $invitation_to_email->to_project_id);
        }
    }

    private function redirectToProjectDashboard(BaseLayout $layout, int $project_id): void
    {
        try {
            $project = $this->project_retriever->getValidProjectById($project_id);
            $layout->redirect($project->getUrl());
        } catch (\Project_NotFoundException) {
            $this->redirectToUserDashboard($layout);
        }
    }

    private function redirectToUserDashboard(BaseLayout $layout): void
    {
        $layout->redirect('/my/');
    }
}
