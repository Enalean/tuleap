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

namespace Tuleap\User\Password\Change;

use Tuleap\User\Password\Reset\Revoker as ResetTokenRevoker;
use Tuleap\User\SessionManager;

class PasswordChanger
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var ResetTokenRevoker
     */
    private $token_revoker;
    /**
     * @var SessionManager
     */
    private $session_manager;

    public function __construct(
        \UserManager $user_manager,
        SessionManager $session_manager,
        ResetTokenRevoker $token_revoker
    ) {
        $this->user_manager    = $user_manager;
        $this->session_manager = $session_manager;
        $this->token_revoker   = $token_revoker;
    }

    /**
     * @throws PasswordChangeException
     * @throws \Tuleap\User\Password\Reset\TokenDataAccessException
     * @throws \Tuleap\User\SessionDataAccessException
     */
    public function changePassword(\PFUser $user, $new_password)
    {
        $this->token_revoker->revokeTokens($user);
        $this->session_manager->destroyAllSessionsButTheCurrentOne($user);
        $user->setPassword($new_password);
        if (! $this->user_manager->updateDb($user)) {
            throw new PasswordChangeException('Could not update password of the user in DB');
        }
    }
}
