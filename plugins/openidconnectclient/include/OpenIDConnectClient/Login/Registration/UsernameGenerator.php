<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

class UsernameGenerator
{
    public function __construct(private UserNameNormalizer $username_generator)
    {
    }

    /**
     * @throws NotEnoughDataToGenerateUsernameException
     * @throws DataIncompatibleWithUsernameGenerationException
     */
    public function getUsername(array $user_information): string
    {
        try {
            if (isset($user_information['preferred_username'])) {
                return $this->username_generator->normalize($user_information['preferred_username']);
            }
        } catch (DataIncompatibleWithUsernameGenerationException $ex) {
        }

        $given_name_without_spaces  = '';
        $family_name_without_spaces = '';

        if (isset($user_information['given_name'])) {
            $given_name_without_spaces = self::transformStringToLowercaseWithoutSpaces($user_information['given_name']);
        }

        if (isset($user_information['family_name'])) {
            $family_name_without_spaces = self::transformStringToLowercaseWithoutSpaces($user_information['family_name']);
        }

        if (mb_strlen($family_name_without_spaces) > 0) {
            $base_username = mb_substr($given_name_without_spaces, 0, 1) . $family_name_without_spaces;
            return $this->username_generator->normalize($base_username);
        }

        if ($given_name_without_spaces !== '') {
            return $this->username_generator->normalize($given_name_without_spaces);
        }

        $user_information_name                = (string) ($user_information['name'] ?? '');
        $user_information_name_without_spaces = self::transformStringToLowercaseWithoutSpaces($user_information_name);
        if ($user_information_name_without_spaces !== '') {
            return $this->username_generator->normalize($user_information_name_without_spaces);
        }

        $user_information_email          = (string) ($user_information['email'] ?? '');
        $email_local_part_without_spaces = self::transformStringToLowercaseWithoutSpaces(\Psl\Str\before_last($user_information_email, '@') ?? '');
        if ($email_local_part_without_spaces !== '') {
            return $this->username_generator->normalize($email_local_part_without_spaces);
        }

        throw new NotEnoughDataToGenerateUsernameException('Not enough data in user\'s information to generate a username. Please check your identity provider.');
    }

    /**
     * @psalm-pure
     */
    private static function transformStringToLowercaseWithoutSpaces(string $content): string
    {
        return mb_strtolower(str_replace(' ', '', $content));
    }
}
