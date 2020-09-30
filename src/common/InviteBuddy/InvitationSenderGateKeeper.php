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

class InvitationSenderGateKeeper
{
    /**
     * @var \Valid_Email
     */
    private $valid_email;
    /**
     * @var InviteBuddyConfiguration
     */
    private $configuration;
    /**
     * @var InvitationLimitChecker
     */
    private $limit_checker;

    public function __construct(\Valid_Email $valid_email, InviteBuddyConfiguration $configuration, InvitationLimitChecker $limit_checker)
    {
        $this->valid_email = $valid_email;
        $this->configuration = $configuration;
        $this->limit_checker = $limit_checker;
    }

    /**
     * @param string[] $emails
     * @throws InvitationSenderGateKeeperException
     */
    public function checkNotificationsCanBeSent(\PFUser $current_user, array $emails): void
    {
        if (! $this->configuration->canBuddiesBeInvited($current_user)) {
            throw new InvitationSenderGateKeeperException(_('Invitations are not enabled'));
        }

        if (empty($emails)) {
            throw new InvitationSenderGateKeeperException(_('We need at least one email to send an invitation'));
        }

        foreach ($emails as $email) {
            if (! $this->valid_email->validate($email)) {
                throw new InvitationSenderGateKeeperException(sprintf(_("Email %s is not valid"), $email));
            }
        }

        $this->limit_checker->checkForNewInvitations(count($emails), $current_user);
    }
}
