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

namespace Tuleap\Project\REST;

use Luracast\Restler\RestException;

class UserRESTReferenceRetriever
{
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(\UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @return null|\PFUser
     * @throws RestException
     */
    public function getUserFromReference(UserRESTReferenceRepresentation $representation)
    {
        $this->checkOnlyOneValueIsSet($representation);

        if ($representation->id !== null) {
            return $this->user_manager->getUserById($representation->id);
        }
        if ($representation->username !== null) {
            return $this->user_manager->getUserByUserName($representation->username);
        }
        if ($representation->email !== null) {
            $users = $this->user_manager->getAllUsersByEmail($representation->email);

            if (count($users) > 1) {
                throw new RestException(400, "More than one user use the email address $representation->email");
            }

            if (count($users) === 0) {
                return null;
            }

            return $users[0];
        }
        if ($representation->ldap_id !== null) {
            return $this->user_manager->getUserByIdentifier('ldapId:' . $representation->ldap_id);
        }

        throw new RestException(400, 'At least one key must be passed in the representation');
    }

    /**
     * @throws RestException
     */
    private function checkOnlyOneValueIsSet(UserRESTReferenceRepresentation $representation)
    {
        $number_of_non_null_values = 0;
        /** @psalm-suppress RawObjectIteration */
        foreach ($representation as $key => $value) {
            if ($value !== null) {
                $number_of_non_null_values++;
            }
            if ($number_of_non_null_values > 1) {
                throw new RestException(400, 'Only one key can be passed in the representation');
            }
        }
    }
}
