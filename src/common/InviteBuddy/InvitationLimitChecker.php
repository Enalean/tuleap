<?php
/*
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

class InvitationLimitChecker
{
    /**
     * @var InvitationDao
     */
    private $dao;
    /**
     * @var InviteBuddyConfiguration
     */
    private $invite_buddy_configuration;

    public function __construct(InvitationDao $dao, InviteBuddyConfiguration $invite_buddy_configuration)
    {
        $this->dao                        = $dao;
        $this->invite_buddy_configuration = $invite_buddy_configuration;
    }

    /**
     * @throws InvitationSenderGateKeeperException
     */
    public function checkForNewInvitations(int $nb_invitation_to_send, \PFUser $user): void
    {
        $invitation_limit = $this->invite_buddy_configuration->getNbMaxInvitationsByDay();

        $already_sent_invitations = $this->dao->getInvitationsSentByUserForToday((int) $user->getId());

        if ($already_sent_invitations + $nb_invitation_to_send > $invitation_limit) {
            if ($already_sent_invitations === 0) {
                $message = \sprintf(
                    _(
                        "You are trying to send %s invitations, but the maximum is %s per day.",
                    ),
                    $nb_invitation_to_send,
                    $invitation_limit
                );
            } else {
                $message = \sprintf(
                    ngettext(
                        "You are trying to send one invitation.",
                        "You are trying to send %s invitations.",
                        $nb_invitation_to_send
                    ),
                    $nb_invitation_to_send
                ) . " " .
                    \sprintf(
                        _("The maximum number of invitations per day is %s."),
                        $invitation_limit
                    )
                    . " " .
                    \sprintf(
                        ngettext(
                            "You can only send one more invitation.",
                            "You can only send %s more invitations.",
                            $invitation_limit - $already_sent_invitations
                        ),
                        $invitation_limit - $already_sent_invitations
                    );
            }
            throw new InvitationSenderGateKeeperException($message);
        }
    }

    public function isLimitReached(\PFUser $user): bool
    {
        $invitation_limit         = $this->invite_buddy_configuration->getNbMaxInvitationsByDay();
        $already_sent_invitations = $this->dao->getInvitationsSentByUserForToday((int) $user->getId());

        return $already_sent_invitations >= $invitation_limit;
    }
}
