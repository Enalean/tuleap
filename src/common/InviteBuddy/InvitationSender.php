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

class InvitationSender
{
    /**
     * @var InvitationSenderGateKeeper
     */
    private $gate_keeper;
    /**
     * @var InvitationEmailNotifier
     */
    private $email_notifier;

    public function __construct(InvitationSenderGateKeeper $gate_keeper, InvitationEmailNotifier $email_notifier)
    {
        $this->gate_keeper    = $gate_keeper;
        $this->email_notifier = $email_notifier;
    }

    /**
     * @param string[] $emails
     *
     * @return string[] emails in failure
     *
     * @throws InvitationSenderGateKeeperException
     * @throws UnableToSendInvitationsException
     */
    public function send(PFUser $current_user, array $emails): array
    {
        $emails = array_filter($emails);
        $this->gate_keeper->checkNotificationsCanBeSent($current_user, $emails);

        $failures = [];
        foreach ($emails as $email) {
            if (! $this->email_notifier->send($current_user, $email)) {
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
