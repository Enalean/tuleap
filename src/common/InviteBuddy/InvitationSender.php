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
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Project\Admin\MembershipDelegationDao;

class InvitationSender
{
    public function __construct(
        private InvitationSenderGateKeeper $gate_keeper,
        private InvitationEmailNotifier $email_notifier,
        private \UserManager $user_manager,
        private InvitationDao $dao,
        private LoggerInterface $logger,
        private InvitationInstrumentation $instrumentation,
        private SplitTokenFormatter $split_token_formatter,
        private MembershipDelegationDao $delegation_dao,
        private \ProjectHistoryDao $history_dao,
    ) {
    }

    /**
     * @param string[] $emails
     *
     * @return string[] emails in failure
     *
     * @throws InvitationSenderGateKeeperException
     * @throws UnableToSendInvitationsException
     * @throws MustBeProjectAdminToInvitePeopleInProjectException
     */
    public function send(
        PFUser $from_user,
        array $emails,
        ?\Project $project,
        ?string $custom_message,
        ?PFUser $resent_from_user,
    ): array {
        $to_project_id = $project ? (int) $project->getID() : null;
        $this->checkUserCanInviteIntoProject($project, $from_user);

        $emails = array_filter($emails);
        $this->gate_keeper->checkNotificationsCanBeSent($from_user, $emails);

        $now = (new \DateTimeImmutable())->getTimestamp();

        $failures = [];
        foreach ($emails as $email) {
            $recipient = new InvitationRecipient(
                $this->user_manager->getUserByEmail($email),
                $email,
            );

            $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

            $invitation_id = $this->dao->create(
                $now,
                (int) $from_user->getId(),
                $email,
                $recipient->getUserId(),
                $to_project_id,
                $custom_message,
                $secret,
            );

            $token = $this->split_token_formatter->getIdentifier(
                new SplitToken($invitation_id, $secret)
            );

            $successfully_sent = $this->email_notifier->send(
                $from_user,
                $recipient,
                $custom_message,
                $token,
                $project,
                $resent_from_user,
            );
            if ($successfully_sent) {
                if ($project) {
                    $this->instrumentation->incrementProjectInvitation();
                    $this->history_dao->addHistory(
                        $project,
                        $from_user,
                        new \DateTimeImmutable(),
                        $resent_from_user
                            ? InvitationHistoryEntry::InvitationResent->value
                            : InvitationHistoryEntry::InvitationSent->value,
                        '',
                        [],
                    );
                } else {
                    $this->instrumentation->incrementPlatformInvitation();
                }
                $this->dao->markAsSent($invitation_id);
            } else {
                $this->logger->error("Unable to send invitation from user #{$from_user->getId()} to $email");
                $this->dao->markAsError($invitation_id);
                $failures[] = $email;
            }
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

        return $failures;
    }

    /**
     * @throws MustBeProjectAdminToInvitePeopleInProjectException
     */
    private function checkUserCanInviteIntoProject(?\Project $project, PFUser $from_user): void
    {
        if (! $project) {
            return;
        }

        if ($from_user->isAdmin((int) $project->getID())) {
            return;
        }

        if ($this->delegation_dao->doesUserHasMembershipDelegation((int) $from_user->getId(), (int) $project->getID())) {
            return;
        }

        throw new MustBeProjectAdminToInvitePeopleInProjectException();
    }
}
