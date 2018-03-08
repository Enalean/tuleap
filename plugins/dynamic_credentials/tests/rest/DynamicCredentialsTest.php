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

use Guzzle\Http\Exception\ClientErrorResponseException;

require_once 'bootstrap.php';

class DynamicCredentialsTest extends \RestBase
{
    const USERNAME         = 'forge__dynamic_credential-user1';
    const PASSWORD         = 'password';

    public function testPOSTNewAccountAndLogin()
    {
        $expiration_date = new \DateTimeImmutable('+30 minutes');

        $this->createAccount(self::USERNAME, self::PASSWORD, $expiration_date);
    }

    public function testPOSTInvalidSignatureRejected()
    {
        $expiration_date = new \DateTimeImmutable('+30 minutes');
        $expiration = $expiration_date->format(\DateTime::ATOM);

        try {
            $this->getResponseWithoutAuth($this->client->post(
                'dynamic_credentials',
                null,
                json_encode([
                    'username'   => self::USERNAME . 'reject_me',
                    'password'   => self::PASSWORD,
                    'expiration' => $expiration,
                    'signature'  => $this->getSignatureForPostAction('wrong_username', self::PASSWORD, $expiration)
                ])
            ));
        } catch (ClientErrorResponseException $ex) {
            $this->assertEquals(403, $ex->getResponse()->getStatusCode());
            return;
        }
        $this->fail();
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
                'expiration' => $expiration,
                'signature'  => $this->getSignatureForPostAction($username, $password, $expiration)
            ])
        ));
    }

    private function getSignatureForPostAction(string $username, string $password, string $expiration_date): string
    {
        $host    = parse_url('https://localhost/api/v1', PHP_URL_HOST);
        $message = $host . $username . $password . $expiration_date;

        return base64_encode(
            \sodium_crypto_sign_detached($message, base64_decode(DynamicCredentialsPluginRESTInitializer::PRIVATE_KEY))
        );
    }
}
