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
    ) {
    }

    /**
     * @param string[] $emails
     *
     * @return string[] emails in failure
     *
     * @throws InvitationSenderGateKeeperException
     * @throws UnableToSendInvitationsException
     */
    public function send(PFUser $current_user, array $emails, ?string $custom_message): array
    {
        $emails = array_filter($emails);
        $this->gate_keeper->checkNotificationsCanBeSent($current_user, $emails);

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
                (int) $current_user->getId(),
                $email,
                $recipient->getUserId(),
                $custom_message,
                $secret,
            );

            $token = $this->split_token_formatter->getIdentifier(
                new SplitToken($invitation_id, $secret)
            );

            if ($this->email_notifier->send($current_user, $recipient, $custom_message, $token)) {
                $this->instrumentation->incrementPlatformInvitation();
                $this->dao->markAsSent($invitation_id);
            } else {
                $this->logger->error("Unable to send invitation from user #{$current_user->getId()} to $email");
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
}
