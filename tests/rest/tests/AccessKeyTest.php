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

namespace Tuleap\User\AccessKey\REST;

use DateTimeImmutable;
use REST_TestDataBuilder;
use RestBase;

class AccessKeyTest extends RestBase
{
    private const DESCRIPTION_ACCESS_KEY = 'test_key';

    public function testOptions(): void
    {
        $response = $this->getResponse($this->client->options('access_keys'));

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAccessKeyLifeCycle(): void
    {
        $access_key_identifier = $this->createAccessKey();

        $this->assertTrue($this->isAuthenticationSuccessful($access_key_identifier));

        $access_keys_user_1 = $this->getAccessKeys();

        $this->assertGreaterThanOrEqual(1, count($access_keys_user_1));
        $has_generated_access_key_been_found = false;
        foreach ($access_keys_user_1 as $access_key) {
            $this->assertNotEmpty($access_key['scopes']);
            if ($access_key['description'] === self::DESCRIPTION_ACCESS_KEY) {
                $this->assertNull($access_key['expiration_date']);
                $has_generated_access_key_been_found = true;
                break;
            }
        }
        $this->assertTrue($has_generated_access_key_been_found);

        $this->revokeAccessKeys($access_keys_user_1);
        $this->assertFalse($this->isAuthenticationSuccessful($access_key_identifier));
        $this->assertEmpty($this->getAccessKeys());
    }

    public function testAccessKeyLifeCycleWithExpirationDate(): void
    {
        $expiration_date       = $this->getFormattedExpirationDate();
        $access_key_identifier = $this->createAccessKey($expiration_date);

        $this->assertTrue($this->isAuthenticationSuccessful($access_key_identifier));

        $access_keys_user_1 = $this->getAccessKeys();

        $this->assertGreaterThanOrEqual(1, count($access_keys_user_1));
        $has_generated_access_key_been_found = false;
        foreach ($access_keys_user_1 as $access_key) {
            $this->assertNotEmpty($access_key['scopes']);
            if ($access_key['description'] === self::DESCRIPTION_ACCESS_KEY) {
                $this->assertEquals($expiration_date, $access_key['expiration_date']);
                $has_generated_access_key_been_found = true;
                break;
            }
        }
        $this->assertTrue($has_generated_access_key_been_found);

        $this->revokeAccessKeys($access_keys_user_1);
        $this->assertFalse($this->isAuthenticationSuccessful($access_key_identifier));
        $this->assertEmpty($this->getAccessKeys());
    }

    public function testAccessKeyInformationRetrieval(): void
    {
        $access_key_identifier = $this->createAccessKey();

        $request = $this->client->get('access_keys/self');
        $request->setHeader('X-Auth-AccessKey', $access_key_identifier);
        $response = $request->send();

        $this->assertEquals(200, $response->getStatusCode());
        $access_key_self = $response->json();

        $response = $this->getResponse(
            $this->client->get(
                'access_keys/' . urlencode((string) $access_key_self['id'])
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($access_key_self, $response->json());

        $this->revokeAccessKeys([$access_key_self]);
    }

    public function testAccessKeyCannotBeCreatedWithoutSpecifyingAScope(): void
    {
        $body_content = json_encode(
            [
                'description' => self::DESCRIPTION_ACCESS_KEY,
                'scopes'      => []
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getResponse(
            $this->client->post(
                'access_keys',
                null,
                $body_content
            ),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAccessKeyCannotBeCreatedWithAnInvalidScopeIdentifier(): void
    {
        $body_content = json_encode(
            [
                'description' => self::DESCRIPTION_ACCESS_KEY,
                'scopes'      => ['invalid_scope_identifier']
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getResponse(
            $this->client->post(
                'access_keys',
                null,
                $body_content
            ),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    private function getFormattedExpirationDate(): string
    {
        $expiration_date = new DateTimeImmutable("tomorrow");

        return $expiration_date->format(DateTimeImmutable::ATOM);
    }

    private function createAccessKey($expiration_date = null): string
    {
        $body_content = json_encode(['description' => self::DESCRIPTION_ACCESS_KEY]);
        if ($expiration_date !== null) {
            $body_content = json_encode([
                'description'     => self::DESCRIPTION_ACCESS_KEY,
                'expiration_date' => $expiration_date
            ]);
        }

        $response = $this->getResponse(
            $this->client->post(
                'access_keys',
                null,
                $body_content
            ),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(201, $response->getStatusCode());
        $access_key = $response->json();
        $this->assertArrayHasKey('identifier', $access_key);
        return $access_key['identifier'];
    }

    private function getAccessKeys(): array
    {
        $response = $this->getResponse(
            $this->client->get('users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/access_keys'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertSame(200, $response->getStatusCode());
        return $response->json();
    }

    private function revokeAccessKeys(array $access_keys): void
    {
        foreach ($access_keys as $access_key) {
            $response = $this->getResponse(
                $this->client->delete(
                    'access_keys/' . $access_key['id']
                ),
                REST_TestDataBuilder::TEST_USER_1_NAME
            );
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    private function isAuthenticationSuccessful($key_identifier): bool
    {
        $request = $this->client->get('projects');
        $request->setHeader('X-Auth-AccessKey', $key_identifier);

        $response = $request->send();

        if ($response->getStatusCode() === 401) {
            return false;
        }
        if ($response->getStatusCode() === 200) {
            return true;
        }

        $this->fail('Got unknown HTTP status code for an access key authentication: ' . $response->getStatusCode());
        return false;
    }

    public function testAuthenticationWithAnIncorrectAccessKeyIsRejected(): void
    {
        $this->assertFalse($this->isAuthenticationSuccessful('invalid_identifier'));
    }
}
