<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group UserGroupTests
 */
class UsersTest extends RestBase {

    public function testGETId() {
        $response = $this->client->get('users/'.TestDataBuilder::TEST_USER_1_ID)->send();

        $this->assertEquals(
            $response->json(),
            array(
                'id'         => TestDataBuilder::TEST_USER_1_ID,
                'uri'        => 'users/'.TestDataBuilder::TEST_USER_1_ID,
                'email'      => TestDataBuilder::TEST_USER_1_EMAIL,
                'real_name'  => TestDataBuilder::TEST_USER_1_REALNAME,
                'username'   => TestDataBuilder::TEST_USER_1_NAME,
                'ldap_id'    => TestDataBuilder::TEST_USER_1_LDAPID,
                'avatar_url' => '/themes/common/images/avatar_default.png'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETIdDoesNotWorkIfUserDoesNotExist() {
        $this->client->get('users/1')->send();
    }

    public function testGetUsersWithMatching() {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users?query=rest_api_tester&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
    }

    public function testGetUserWithExactSearch() {
        $search = urlencode(
            json_encode(
                array(
                    'username' => TestDataBuilder::TEST_USER_1_NAME
                )
            )
        );

        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_ID, $json[0]['id']);
    }

    public function testGetUserWithExactSearchWithoutResult() {
        $search = urlencode(
            json_encode(
                array(
                    'username' => 'muppet'
                )
            )
        );

        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(0, $json);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetUserWithInvalidJson() {
        $search = urlencode('{jeanclaude}');

        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 400);
    }
}
