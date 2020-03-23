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

use RuntimeException;

class ToBeMappedUser extends ActionToBeTakenForUser
{

    public const ACTION = 'map';

    /** @var \PFUser[] */
    private $matching_users;

    public function __construct(
        $username,
        $realname,
        array $matching_users,
        $original_user_id,
        $original_ldap_id
    ) {
        if (empty($matching_users)) {
            throw new RuntimeException('Matching users should not be empty');
        }

        $email  = $matching_users[0]->getEmail();

        parent::__construct($username, $realname, $email, $original_user_id, $original_ldap_id);

        $this->matching_users = $matching_users;
    }

    /** @return array */
    public function getCSVData()
    {
        $matching = array();
        $actions  = array();
        foreach ($this->matching_users as $user) {
            $matching[] = $user->getRealName() . ' (' . $user->getUserName() . ') [' . $user->getStatus() . ']';
            $actions[]  = '"' . self::ACTION . ':' . $user->getUserName() . '"';
        }

        return array(
            $this->username,
            self::ACTION . ':',
            sprintf(
                'User %s (%s) has the same email address than following users: %s.'
                . ' Use one of the following actions to confirm the mapping: %s.',
                $this->realname,
                $this->username,
                implode(', ', $matching),
                implode(', ', $actions)
            )
        );
    }

    public function isActionAllowed($action)
    {
        return $action === self::ACTION;
    }
}
