<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use LDAP_UserManager;
use Psr\Log\LoggerInterface;
use Tuleap\User\Account\AccountCreated;

final class AccountCreation
{

    /**
     * @var LDAP_UserManager
     */
    private $ldap_user_manager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, LDAP_UserManager $ldap_user_manager)
    {
        $this->ldap_user_manager = $ldap_user_manager;
        $this->logger = $logger;
    }

    public function associateWithLDAPAccount(AccountCreated $account_created): void
    {
        $this->logger->debug(sprintf('associateWithLDAPAccount start for %s (id: %d, ldap: %s)', $account_created->user->getUserName(), $account_created->user->getId(), $account_created->user->getLdapId()));
        $ldap_user = $this->ldap_user_manager->getLDAPUserFromUser($account_created->user);
        if ($ldap_user === null) {
            $this->logger->debug('no matching ldap user found');
            return;
        }
        $this->ldap_user_manager->createLdapUser($ldap_user);
        $this->logger->debug('associateWithLDAPAccount end');
    }
}
