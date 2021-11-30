<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    /**
     * @var string
     */
    private $user_realname;
    /**
     * @var callable
     */
    private $invalid_state_clean_up;

    public function __construct(
        DynamicCredentialSession $dynamic_credential_session,
        \UserManager $user_manager,
        $user_realname,
        callable $invalid_state_clean_up,
    ) {
        $this->dynamic_credential_session = $dynamic_credential_session;
        $this->user_manager               = $user_manager;
        $this->user_realname              = $user_realname;
        $this->invalid_state_clean_up     = $invalid_state_clean_up;
    }

    /**
     * @return DynamicUser
     */
    public function getDynamicUser(array $row)
    {
        try {
            $credential = $this->dynamic_credential_session->getAssociatedCredential();
            if ($credential->hasExpired()) {
                $this->terminateInvalidState();
            }
        } catch (CredentialNotFoundException $ex) {
            $this->terminateInvalidState();
        } catch (DynamicCredentialSessionNotInitializedException $ex) {
            return new DynamicUser($this->user_realname, $row, false);
        }

        return new DynamicUser($this->user_realname, $row, true);
    }

    private function terminateInvalidState()
    {
        static $is_trying_to_logout = false;
        if (! $is_trying_to_logout) {
            $is_trying_to_logout = true;
            $this->user_manager->logout();
            call_user_func($this->invalid_state_clean_up);
            throw new InvalidStateCleanUpDoesNotInterruptException();
        }
    }
}
