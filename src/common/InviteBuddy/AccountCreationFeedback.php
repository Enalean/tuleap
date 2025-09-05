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

use Psr\Log\LoggerInterface;
use Tuleap\User\Account\Register\RegisterFormContext;
use Tuleap\User\RetrieveUserById;

class AccountCreationFeedback implements InvitationSuccessFeedback
{
    public function __construct(
        private InvitationDao $dao,
        private RetrieveUserById $user_manager,
        private AccountCreationFeedbackEmailNotifier $email_notifier,
        private AddUserToProjectAccordingToInvitation $project_member_adder,
        private InvitationInstrumentation $invitation_instrumentation,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function accountHasJustBeenCreated(\PFUser $just_created_user, RegisterFormContext $context): void
    {
        $this->dao->saveJustCreatedUserThanksToInvitation(
            (string) $just_created_user->getEmail(),
            (int) $just_created_user->getId(),
            $context->invitation_to_email ? $context->invitation_to_email->id : null
        );

        if ($context->invitation_to_email) {
            $this->invitation_instrumentation->incrementUsedInvitation();
        }

        $already_warned_users_id = [];
        foreach ($this->dao->searchByCreatedUserId((int) $just_created_user->getId()) as $invitation) {
            $this->invitation_instrumentation->incrementCompletedInvitation();

            if ($invitation->to_project_id) {
                $this->project_member_adder->addUserToProjectAccordingToInvitation($just_created_user, $invitation);
            }

            if (isset($already_warned_users_id[$invitation->from_user_id])) {
                continue;
            }

            $already_warned_users_id[$invitation->from_user_id] = true;

            $from_user = $this->user_manager->getUserById($invitation->from_user_id);
            if (! $from_user) {
                $this->logger->error('Invitation was referencing an unknown user #' . $invitation->from_user_id);
                continue;
            }
            if (! $from_user->isAlive()) {
                $this->logger->warning('Cannot send invitation feedback to inactive user #' . $invitation->from_user_id);
                continue;
            }

            if (! $this->email_notifier->send($from_user, $just_created_user)) {
                $this->logger->error(
                    "Unable to send invitation feedback to user #{$from_user->getId()} after registration of user #{$just_created_user->getId()}"
                );
            }
        }
    }
}
