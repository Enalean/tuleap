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
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class InvitationToOneRecipientWithoutVerificationSender implements InvitationToOneRecipientSender
{
    public function __construct(
        private InvitationEmailNotifier $email_notifier,
        private InvitationCreator $invitation_creator,
        private InvitationStatusUpdater $invitation_status_updater,
        private InvitationInstrumentation $instrumentation,
        private SplitTokenFormatter $split_token_formatter,
        private \ProjectHistoryDao $history_dao,
    ) {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    #[\Override]
    public function sendToRecipient(
        PFUser $from_user,
        InvitationRecipient $recipient,
        ?\Project $project,
        ?string $custom_message,
        ?PFUser $resent_from_user,
    ): Ok|Err {
        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $invitation_id = $this->invitation_creator->create(
            (new \DateTimeImmutable())->getTimestamp(),
            (int) $from_user->getId(),
            $recipient->email,
            $recipient->getUserId(),
            $project ? (int) $project->getID() : null,
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

        if (! $successfully_sent) {
            $this->invitation_status_updater->markAsError($invitation_id);

            return Result::err(Fault::fromMessage("Unable to send invitation from user #{$from_user->getId()} to {$recipient->email}"));
        }

        if ($project) {
            $this->instrumentation->incrementProjectInvitation();
            $this->history_dao->addHistory(
                $project,
                $from_user,
                new \DateTimeImmutable(),
                $resent_from_user
                    ? InvitationHistoryEntry::InvitationResent->value
                    : InvitationHistoryEntry::InvitationSent->value,
                (string) $invitation_id,
                [],
            );
        } else {
            $this->instrumentation->incrementPlatformInvitation();
        }
        $this->invitation_status_updater->markAsSent($invitation_id);

        return Result::ok(true);
    }
}
