<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
namespace User\XML\Import;

use PFUser;

class ToBeActivatedUser extends ActionToBeTakenForUser
{

    private static $ALLOWED_ACTIONS = array(self::ACTION, ToBeMappedUser::ACTION);

    public const ACTION = 'noop';

    private $status;

    public function __construct(PFUser $user, $original_user_id, $original_ldap_id)
    {
        parent::__construct(
            $user->getUserName(),
            $user->getRealName(),
            $user->getEmail(),
            $original_user_id,
            $original_ldap_id
        );

        $this->status = $user->getStatus();
    }

    /** @return array */
    public function getCSVData()
    {
        return array(
            $this->username,
            self::ACTION,
            sprintf(
                'Status of existing user %s is [%s]',
                $this->username,
                $this->status
            )
        );
    }

    public function isActionAllowed($action)
    {
        return in_array($action, self::$ALLOWED_ACTIONS);
    }
}
