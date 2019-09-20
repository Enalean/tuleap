<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * @group TokenTests
 */
class AuthenticationTest extends RestBase
{

    public function testOPTIONSIsReadableByAnonymous()
    {
        $response = $this->client->options('projects')->send();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPublicGETResourceIsReadableByAnonymous()
    {
        $response = $this->client->get('projects')->send();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGETWithBasicAuthAndWrongCredentialsThrowsAnException()
    {
        $response = $this->getResponseByBasicAuth(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            'wrong_password',
            $this->client->get('projects')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGETWithTokenAndWrongCredentialsThrowsAnException()
    {
        $request = $this->client->get('projects');
        $request
            ->setHeader('X-Auth-Token', 'wrong_token')
            ->setHeader('X-Auth-UserId', $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]);
        $response = $request->send();
        $this->assertEquals(401, $response->getStatusCode());
    }
}
