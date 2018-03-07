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

namespace Tuleap\DynamicCredentials\REST;

class DynamicCredentialsTest extends \RestBase
{
    const USERNAME         = 'forge__dynamic_credential-user1';
    const PASSWORD         = 'password';

    public function testPOSTNewAccountAndLogin()
    {
        $expiration_date = new \DateTimeImmutable('+30 minutes');

        $this->createAccount(self::USERNAME, self::PASSWORD, $expiration_date);
    }

    private function createAccount(string $username, string $password, \DateTimeImmutable $expiration_date)
    {
        $expiration = $expiration_date->format(\DateTime::ATOM);
        $this->getResponseWithoutAuth($this->client->post(
            'dynamic_credentials',
            null,
            json_encode([
                'username'   => $username,
                'password'   => $password,
                'expiration' => $expiration
            ])
        ));
    }
}
