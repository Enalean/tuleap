<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class User_UserStatusManager
{

    /**
     * Ensure user can use the platform
     *
     * @throws User_StatusDeletedException
     * @throws User_StatusSuspendedException
     * @throws User_StatusInvalidException
     * @throws User_StatusPendingException
     */
    public function checkStatus(PFUser $user)
    {
        if (! $this->isFinalStatus($user)) {
            switch ($user->getStatus()) {
                case PFUser::STATUS_VALIDATED:
                case PFUser::STATUS_VALIDATED_RESTRICTED:
                case PFUser::STATUS_PENDING:
                    throw new User_StatusPendingException();
                    break;
                default:
                    throw new User_StatusInvalidException();
            }
        }
    }

    /**
     * Specific status checking when user verifies it's account
     *
     * See src/www/account/login.php
     *
     *
     * @throws User_StatusDeletedException
     * @throws User_StatusSuspendedException
     * @throws User_StatusInvalidException
     * @throws User_StatusPendingException
     */
    public function checkStatusOnVerifyPage(PFUser $user)
    {
        if (! $this->isFinalStatus($user)) {
            switch ($user->getStatus()) {
                case PFUser::STATUS_VALIDATED:
                case PFUser::STATUS_VALIDATED_RESTRICTED:
                    break;
                case PFUser::STATUS_PENDING:
                    if (ForgeConfig::get('sys_user_approval') == 0) {
                        break;
                    }
                    // User should not have a pending status if approval is not required
                default:
                    throw new User_StatusInvalidException();
            }
        }
    }

    /**
     * Check user status validity
     *
     * @param bool $allowpending
     * @return bool
     * @throws User_StatusDeletedException
     * @throws User_StatusSuspendedException
     */
    private function isFinalStatus(PFUser $user)
    {
        $status = $user->getStatus();
        switch ($status) {
            case PFUser::STATUS_ACTIVE:
            case PFUser::STATUS_RESTRICTED:
                return true;
                break;
            case PFUser::STATUS_DELETED:
                throw new User_StatusDeletedException();
            case PFUser::STATUS_SUSPENDED:
                throw new User_StatusSuspendedException();
            default:
                return false;
        }
    }
}
