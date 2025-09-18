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

namespace Tuleap\DynamicCredentials\Credential;

class CredentialIdentifierExtractor
{
    public const string EXPECTED_PREFIX = 'forge__dynamic_credential-';

    /**
     * @return string
     * @throws CredentialInvalidUsernameException
     */
    public function extract($username)
    {
        if (mb_strpos($username, self::EXPECTED_PREFIX) !== 0) {
            throw new CredentialInvalidUsernameException();
        }
        $identifier = mb_substr($username, mb_strlen(self::EXPECTED_PREFIX));
        if ($identifier === '') {
            throw new CredentialInvalidUsernameException();
        }
        return $identifier;
    }
}
