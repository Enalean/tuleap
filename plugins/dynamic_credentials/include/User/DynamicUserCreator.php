<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\User;

use Tuleap\DynamicCredentials\Credential\CredentialNotFoundException;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSession;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSessionNotInitializedException;

class DynamicUserCreator
{
    /**
     * @var DynamicCredentialSession
     */
    private $dynamic_credential_session;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(DynamicCredentialSession $dynamic_credential_session, \UserManager $user_manager)
    {
        $this->dynamic_credential_session = $dynamic_credential_session;
        $this->user_manager               = $user_manager;
    }

    /**
     * @return DynamicUser
     */
    public function getDynamicUser(array $row)
    {
        try {
            $credential = $this->dynamic_credential_session->getAssociatedCredential();
        } catch (CredentialNotFoundException $ex) {
            $this->terminateInvalidState();
        } catch (DynamicCredentialSessionNotInitializedException $ex) {
            return new DynamicUser($row, false);
        }

        if ($credential->hasExpired()) {
            $this->terminateInvalidState();
        }

        return new DynamicUser($row, true);
    }

    private function terminateInvalidState()
    {
        static $is_trying_to_logout = false;
        if (! $is_trying_to_logout) {
            $is_trying_to_logout = true;
            $this->user_manager->logout();
            header('Location: /');
            exit();
        }
    }
}
