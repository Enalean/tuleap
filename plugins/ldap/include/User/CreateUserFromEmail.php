<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\LDAP\User;

use LDAP;
use Psr\Log\LoggerInterface;
use Tuleap\User\FindUserByEmailEvent;

final class CreateUserFromEmail
{
    public function __construct(private LDAP $ldap, private \LDAP_UserManager $ldap_user_manager, private LoggerInterface $logger)
    {
    }

    public function process(FindUserByEmailEvent $event): void
    {
        $this->logger->debug('Looking for user email ' . $event->email);
        $iterator = $this->ldap->searchEmail($event->email);
        if ($iterator === false) {
            $this->logger->error('Search by email failed');
            return;
        }
        if (count($iterator) !== 1) {
            $this->logger->debug(sprintf('%d entries found for email %s. Account creation aborted.', count($iterator), $event->email));
            return;
        }

        $result = $iterator->current();
        $user   = $this->ldap_user_manager->getUserFromLdap($result);
        if ($user === false) {
            $this->logger->error('Error at creation of Tuleap user from LDAP based on user email ' . $event->email);
            return;
        }

        $event->setUser($user);
    }
}
