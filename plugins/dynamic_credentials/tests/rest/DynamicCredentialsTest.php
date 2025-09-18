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

namespace Tuleap\DynamicCredentials\REST;

require_once 'bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DynamicCredentialsTest extends \Tuleap\REST\RestBase
{
    public const string USERNAME         = 'forge__dynamic_credential-user1';
    public const string USERNAME_EXPIRED = self::USERNAME . '-expired';
    public const string PASSWORD         = 'password';

    public function testPOSTNewAccountAndLogin()
    {
        $expiration_date = new \DateTimeImmutable('+30 minutes');

        $this->createAccount(self::USERNAME, self::PASSWORD, $expiration_date);
        $response = $this->login(self::USERNAME, self::PASSWORD);
        self::assertSame(201, $response->getStatusCode());
    }

    public function testPOSTNewAccountAndLoginFailureWithExpiredAccount()
    {
        $expiration_date = new \DateTimeImmutable('- 1 seconds');

        $this->createAccount(self::USERNAME_EXPIRED, self::PASSWORD, $expiration_date);
        $this->ensureLoginFail(self::USERNAME_EXPIRED, self::PASSWORD);
    }

    public function testPOSTInvalidSignatureRejected()
    {
        $expiration_date = new \DateTimeImmutable('+30 minutes');
        $expiration      = $expiration_date->format(\DateTime::ATOM);

        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('POST', 'dynamic_credentials')->withBody($this->stream_factory->createStream(json_encode([
            'username'   => self::USERNAME . 'reject_me',
            'password'   => self::PASSWORD,
            'expiration' => $expiration,
            'signature'  => $this->getSignatureForPostAction('wrong_username', self::PASSWORD, $expiration),
        ]))));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTNewAccountAndLogin')]
    #[\PHPUnit\Framework\Attributes\Depends('testPOSTNewAccountAndLoginFailureWithExpiredAccount')]
    public function testDELETEAccount()
    {
        $uri      = 'dynamic_credentials/' . urlencode(self::USERNAME) . '?' . http_build_query([
            'signature'  => $this->getSignatureForDeleteAction(self::USERNAME),
        ]);
        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('DELETE', $uri));
        self::assertSame(200, $response->getStatusCode());
    }

    public function testDELETEInvalidSignatureRejected()
    {
        $uri      = 'dynamic_credentials/' . urlencode(self::USERNAME . 'reject_me') . '?'  . http_build_query([
            'signature' => $this->getSignatureForDeleteAction('wrong_username'),
        ]);
        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('DELETE', $uri));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDELETENonExistingAccount()
    {
        $uri      = 'dynamic_credentials/' . urlencode(self::USERNAME . 'donotexist') . '?' . http_build_query([
            'signature'  => $this->getSignatureForDeleteAction(self::USERNAME . 'donotexist'),
        ]);
        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('DELETE', $uri));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testDELETEAccount')]
    public function testCannotConnectWithDeletedAccount()
    {
        $this->ensureLoginFail(self::USERNAME, self::PASSWORD);
    }

    private function createAccount(string $username, string $password, \DateTimeImmutable $expiration_date)
    {
        $expiration = $expiration_date->format(\DateTime::ATOM);
        $this->getResponseWithoutAuth($this->request_factory->createRequest('POST', 'dynamic_credentials')->withBody($this->stream_factory->createStream(json_encode([
            'username'   => $username,
            'password'   => $password,
            'expiration' => $expiration,
            'signature'  => $this->getSignatureForPostAction($username, $password, $expiration),
        ]))));
    }

    private function login(string $username, string $password)
    {
        return $this->getResponseWithoutAuth($this->request_factory->createRequest('POST', 'tokens')->withBody($this->stream_factory->createStream(json_encode([
            'username' => $username,
            'password' => $password,
        ]))));
    }

    private function ensureLoginFail(string $username, string $password)
    {
        $response = $this->login($username, $password);

        $this->assertEquals(401, $response->getStatusCode());
    }

    private function getSignatureForPostAction(string $username, string $password, string $expiration_date): string
    {
        $host    = parse_url('https://localhost/api/v1', PHP_URL_HOST);
        $message = $host . $username . $password . $expiration_date;

        return base64_encode(
            \sodium_crypto_sign_detached($message, base64_decode(DynamicCredentialsPluginRESTInitializer::PRIVATE_KEY))
        );
    }

    private function getSignatureForDeleteAction(string $username): string
    {
        $host    = parse_url('https://localhost/api/v1', PHP_URL_HOST);
        $message = $host . $username;

        return base64_encode(
            \sodium_crypto_sign_detached($message, base64_decode(DynamicCredentialsPluginRESTInitializer::PRIVATE_KEY))
        );
    }
}
