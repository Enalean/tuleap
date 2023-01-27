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
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\AlreadyProjectMemberException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\NoEmailForUserException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\UserIsNotActiveOrRestrictedException;
use Tuleap\User\Account\Register\InvitationToEmail;
use Tuleap\User\Account\Register\RegisterFormContext;
use Tuleap\User\RetrieveUserById;

class AccountCreationFeedback implements InvitationSuccessFeedback
{
    public function __construct(
        private InvitationDao $dao,
        private RetrieveUserById $user_manager,
        private AccountCreationFeedbackEmailNotifier $email_notifier,
        private ProjectByIDFactory $project_retriever,
        private ProjectMemberAdder $project_member_adder,
        private LoggerInterface $logger,
    ) {
    }

    public function accountHasJustBeenCreated(\PFUser $just_created_user, RegisterFormContext $context): void
    {
        $this->dao->saveJustCreatedUserThanksToInvitation(
            (string) $just_created_user->getEmail(),
            (int) $just_created_user->getId(),
            $context->invitation_to_email ? $context->invitation_to_email->id : null
        );

        if ($context->invitation_to_email) {
            $this->addUserToProjectAccordingToInvitation($just_created_user, $context->invitation_to_email);
        }

        foreach ($this->dao->searchByCreatedUserId((int) $just_created_user->getId()) as $row) {
            $from_user = $this->user_manager->getUserById($row['from_user_id']);
            if (! $from_user) {
                $this->logger->error("Invitation was referencing an unknown user #" . $row['from_user_id']);
                continue;
            }
            if (! $from_user->isAlive()) {
                $this->logger->warning("Cannot send invitation feedback to inactive user #" . $row['from_user_id']);
                continue;
            }

            if (! $this->email_notifier->send($from_user, $just_created_user)) {
                $this->logger->error(
                    "Unable to send invitation feedback to user #{$from_user->getId()} after registration of user #{$just_created_user->getId()}"
                );
            }
        }
    }

    private function addUserToProjectAccordingToInvitation(
        \PFUser $just_created_user,
        InvitationToEmail $invitation_to_email,
    ): void {
        if (! $invitation_to_email->to_project_id) {
            return;
        }

        if (! $just_created_user->isActive() && $just_created_user->isRestricted()) {
            $this->logger->info("User #{$just_created_user->getId()} has been invited to project #{$invitation_to_email->to_project_id}, but need to be active first. Wating for site admin approval");
            return;
        }

        try {
            $project = $this->project_retriever->getValidProjectById($invitation_to_email->to_project_id);
            $this->project_member_adder->addProjectMember($just_created_user, $project);
        } catch (UserIsNotActiveOrRestrictedException $e) {
            $this->logger->error(
                "Unable to add non active nor restricted user to project. This should not happen.",
                ['exception' => $e]
            );
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted) {
            $this->logger->error(
                "Unable to add restricted user #{$just_created_user->getId()} to project #{$invitation_to_email->to_project_id}.",
            );
        } catch (AlreadyProjectMemberException) {
            // I don't know how we can end up in this situation
            // but we don't need to do anything. This is fine.
        } catch (NoEmailForUserException $e) {
            $this->logger->error(
                "User that have been invited by email does not have an email. This should not happen.",
                ['exception' => $e]
            );
        } catch (\Project_NotFoundException) {
            $this->logger->error(
                "User #{$just_created_user->getId()} has been invited to project #{$invitation_to_email->to_project_id}, but it appears that this project is not valid."
            );
        } catch (\Exception $e) {
            $this->logger->error("Got an unexpceted error", ['exception' => $e]);
        }
    }
}
