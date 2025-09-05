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

namespace Tuleap\InviteBuddy;

use Psr\Log\LoggerInterface;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\AlreadyProjectMemberException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\NoEmailForUserException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\UserIsNotActiveOrRestrictedException;
use Tuleap\User\RetrieveUserById;

final class ProjectMemberAccordingToInvitationAdder implements AddUserToProjectAccordingToInvitation
{
    public function __construct(
        private RetrieveUserById $user_manager,
        private ProjectByIDFactory $project_retriever,
        private ProjectMemberAdder $project_member_adder,
        private InvitationInstrumentation $invitation_instrumentation,
        private LoggerInterface $logger,
        private InvitationEmailNotifier $email_notifier,
        private \ProjectHistoryDao $project_history_dao,
    ) {
    }

    #[\Override]
    public function addUserToProjectAccordingToInvitation(
        \PFUser $just_created_user,
        Invitation|InvitationToEmail $invitation,
    ): void {
        if (! $invitation->to_project_id) {
            return;
        }

        if (! $just_created_user->isAlive()) {
            $this->logger->info("User #{$just_created_user->getId()} has been invited to project #{$invitation->to_project_id}, but need to be active first. Waiting for site admin approval");
            return;
        }

        $from_user = $this->user_manager->getUserById($invitation->from_user_id);
        if (! $from_user) {
            $this->logger->error("User #{$just_created_user->getId()} has been invited by user #{$invitation->from_user_id} to project #{$invitation->to_project_id}, but we cannot find user #{$invitation->from_user_id}");
            return;
        }

        if (! $from_user->isAlive()) {
            $this->logger->error("User #{$just_created_user->getId()} has been invited by user #{$invitation->from_user_id} to project #{$invitation->to_project_id}, but user #{$invitation->from_user_id} is not active nor restricted");
            return;
        }

        try {
            $project = $this->project_retriever->getValidProjectById($invitation->to_project_id);
        } catch (\Project_NotFoundException $e) {
            $this->logger->error(
                "User #{$just_created_user->getId()} has been invited to project #{$invitation->to_project_id}, but it appears that this project is not valid."
            );
            return;
        }

        if ($just_created_user->isMember((int) $project->getID())) {
            $this->registerCompletedInvitation($project, $from_user, $invitation);
            return;
        }

        try {
            $this->project_member_adder->addProjectMember($just_created_user, $project, $from_user);
            $this->registerCompletedInvitation($project, $from_user, $invitation);

            // clear membership cache so that we will know if user is already project member when processing the next invitation
            $just_created_user->clearGroupData();
        } catch (UserIsNotAllowedToManageProjectMembersException) {
            $this->logger->error(
                "User #{$just_created_user->getId()} has been invited to project #{$invitation->to_project_id} by user #{$from_user->getId()}, but user #{$from_user->getId()} is not project admin."
            );
        } catch (UserIsNotActiveOrRestrictedException $e) {
            $this->logger->error(
                'Unable to add non active nor restricted user to project. This should not happen.',
                ['exception' => $e]
            );
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted) {
            $this->logger->error(
                "Unable to add restricted user #{$just_created_user->getId()} to project #{$invitation->to_project_id}.",
            );
            $this->email_notifier->informThatCannotAddRestrictedUserToProjectNotAllowingRestricted(
                $from_user,
                $just_created_user,
                $project
            );
        } catch (AlreadyProjectMemberException) {
            // I don't know how we can end up in this situation
            // but we don't need to do anything. This is fine.
        } catch (NoEmailForUserException $e) {
            $this->logger->error(
                'User that have been invited by email does not have an email. This should not happen.',
                ['exception' => $e]
            );
        } catch (\Exception $e) {
            $this->logger->error('Got an unexpected error while adding an invited user to a project', ['exception' => $e]);
            throw $e;
        }
    }

    private function registerCompletedInvitation(\Project $project, \PFUser $from_user, Invitation|InvitationToEmail $invitation): void
    {
        $this->invitation_instrumentation->incrementProjectInvitation();
        $this->project_history_dao->addHistory(
            $project,
            $from_user,
            new \DateTimeImmutable('now'),
            InvitationHistoryEntry::InvitationCompleted->value,
            (string) $invitation->id,
            []
        );
    }
}
