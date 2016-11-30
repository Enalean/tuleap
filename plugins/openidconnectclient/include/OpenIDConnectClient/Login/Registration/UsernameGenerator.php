<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use Rule_UserName;

class UsernameGenerator
{
    /**
     * @var Rule_UserName
     */
    private $username_rule;

    public function __construct(Rule_UserName $username_rule)
    {
        $this->username_rule = $username_rule;
    }

    /**
     * @return string
     * @throws NotEnoughDataToGenerateUsernameException
     * @throws DataIncompatibleWithUsernameGenerationException
     */
    public function getUsername(array $user_information)
    {
        try {
            if (isset($user_information['preferred_username'])) {
                return $this->generate($user_information['preferred_username']);
            }
        } catch (DataIncompatibleWithUsernameGenerationException $ex) {
        }

        if (! isset($user_information['given_name']) && ! isset($user_information['family_name'])) {
            throw new NotEnoughDataToGenerateUsernameException();
        }

        $given_name_without_spaces  = '';
        $family_name_without_spaces = '';

        if (isset($user_information['given_name'])) {
            $given_name_without_spaces = mb_strtolower(str_replace(' ', '', $user_information['given_name']));
        }

        if (isset($user_information['family_name'])) {
            $family_name_without_spaces = mb_strtolower(str_replace(' ', '', $user_information['family_name']));
        }

        if (mb_strlen($family_name_without_spaces) > 0) {
            $base_username = mb_substr($given_name_without_spaces, 0, 1) . $family_name_without_spaces;
            return $this->generate($base_username);
        }

        return $this->generate($given_name_without_spaces);
    }

    /**
     * @return string
     * @throws DataIncompatibleWithUsernameGenerationException
     */
    private function generate($username)
    {
        if (! $this->username_rule->isUnixValid($username)) {
            throw new DataIncompatibleWithUsernameGenerationException();
        }

        if ($this->username_rule->isValid($username)) {
            return $username;
        }

        $username_suffix = 1;

        while (! $this->username_rule->isValid("$username$username_suffix")) {
            $username_suffix++;
        }

        return "$username$username_suffix";
    }
}
