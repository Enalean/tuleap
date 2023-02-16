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

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembers;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\User\RetrieveUserByEmail;

class InvitationSender
{
    public function __construct(
        private InvitationSenderGateKeeper $gate_keeper,
        private RetrieveUserByEmail $user_manager,
        private LoggerInterface $logger,
        private EnsureUserCanManageProjectMembers $members_manager_checker,
        private InvitationToOneRecipientSender $one_recipient_sender,
    ) {
    }

    /**
     * @param string[] $emails
     *
     * @throws InvitationSenderGateKeeperException
     * @throws UnableToSendInvitationsException
     * @throws UserIsNotAllowedToManageProjectMembersException
     */
    public function send(
        PFUser $from_user,
        array $emails,
        ?\Project $project,
        ?string $custom_message,
        ?PFUser $resent_from_user,
    ): SentInvitationResult {
        $to_project_id = $project ? (int) $project->getID() : null;
        $this->checkUserCanInviteIntoProject($project, $from_user);

        $emails = array_filter($emails);
        $this->gate_keeper->checkNotificationsCanBeSent($from_user, $emails);

        $now = (new \DateTimeImmutable())->getTimestamp();

        $failures               = [];
        $already_project_member = [];
        foreach ($emails as $email) {
            $user = $this->user_manager->getUserByEmail($email);
            if ($project && $user && $user->isMember((int) $project->getID())) {
                $already_project_member[] = $user;
                continue;
            }

            $this->one_recipient_sender
                ->sendToRecipient(
                    $from_user,
                    new InvitationRecipient($user, $email),
                    $project,
                    $custom_message,
                    $resent_from_user,
                )->orElse(
                    function (Fault $fault) use ($email, &$failures): Err {
                        Fault::writeToLogger($fault, $this->logger);
                        $failures[] = $email;

                        return Result::err($fault);
                    },
                );
        }

        if (count($failures) === count($emails)) {
            throw new UnableToSendInvitationsException(
                ngettext(
                    "An error occurred while trying to send invitation",
                    "An error occurred while trying to send invitations",
                    count($failures)
                )
            );
        }

        return new SentInvitationResult($failures, $already_project_member);
    }

    /**
     * @throws UserIsNotAllowedToManageProjectMembersException
     */
    private function checkUserCanInviteIntoProject(?\Project $project, PFUser $from_user): void
    {
        if (! $project) {
            return;
        }

        $this->members_manager_checker->checkUserCanManageProjectMembers($from_user, $project);
    }
}
