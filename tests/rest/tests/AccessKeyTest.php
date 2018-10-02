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

namespace Tuleap\User\AccessKey\REST;

use REST_TestDataBuilder;
use RestBase;

class AccessKeyTest extends RestBase
{
    const DESCRIPTION_ACCESS_KEY = 'test_key';

    public function testOptions(): void
    {
        $response = $this->getResponse($this->client->options('access_keys'));

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testAccessKeyLifeCycle(): void
    {
        $access_key_identifier = $this->createAccessKey();

        $this->assertTrue($this->isAuthenticationSuccessful($access_key_identifier));

        $access_keys_user_1 = $this->getAccessKeys();

        $this->assertGreaterThanOrEqual(1, count($access_keys_user_1));
        $has_generated_access_key_been_found = false;
        foreach ($access_keys_user_1 as $access_key) {
            if ($access_key['description'] === self::DESCRIPTION_ACCESS_KEY) {
                $has_generated_access_key_been_found = true;
                break;
            }
        }
        $this->assertTrue($has_generated_access_key_been_found);

        $this->revokeAccessKeys($access_keys_user_1);
        $this->assertFalse($this->isAuthenticationSuccessful($access_key_identifier));
        $this->assertEmpty($this->getAccessKeys());
    }

    private function createAccessKey(): string
    {
        $response = $this->getResponse(
            $this->client->post(
                'access_keys',
                null,
                json_encode(['description' => self::DESCRIPTION_ACCESS_KEY])
            ),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals($response->getStatusCode(), 201);
        $access_key = $response->json();
        $this->assertArrayHasKey('identifier', $access_key);
        return $access_key['identifier'];
    }

    private function getAccessKeys(): array
    {
        $response = $this->getResponse(
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/access_keys'),
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
            $this->assertEquals($response->getStatusCode(), 200);
        }
    }

    private function isAuthenticationSuccessful($key_identifier): bool
    {
        $request = $this->client->get('projects');
        $request->setHeader('X-Auth-AccessKey', $key_identifier);

        try {
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(401, $e->getResponse()->getStatusCode());
            return false;
        }

        $this->assertEquals(200, $response->getStatusCode());
        return true;
    }

    public function testAuthenticationWithAnIncorrectAccessKeyIsRejected(): void
    {
        $this->assertFalse($this->isAuthenticationSuccessful('invalid_identifier'));
    }
}
