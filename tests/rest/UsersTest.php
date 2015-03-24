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

    public function testGetIdAsAnonymousHasMinimalInformation() {
        $response = $this->client->get('users/'.TestDataBuilder::TEST_USER_1_ID)->send();
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals(TestDataBuilder::TEST_USER_1_ID, $json['id']);
        $this->assertEquals('users/'.TestDataBuilder::TEST_USER_1_ID, $json['uri']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('/themes/common/images/avatar_default.png', $json['avatar_url']);
        $this->assertFalse(isset($json['email']));
        $this->assertFalse(isset($json['status']));
    }

    public function testGETIdAsRegularUser() {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_1_ID));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals(TestDataBuilder::TEST_USER_1_ID, $json['id']);
        $this->assertEquals('users/'.TestDataBuilder::TEST_USER_1_ID, $json['uri']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_EMAIL, $json['email']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('/themes/common/images/avatar_default.png', $json['avatar_url']);
    }

    public function testGETIdDoesNotWorkIfUserDoesNotExist() {
        $exception_thrown = false;
        try {
            $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/1'));
        } catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }

    public function testGETMembershipBySelfReturnsUserGroups() {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_2_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_2_ID.'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testUserCannotSeeGroupOfAnotherUser() {
        $exception_thrown = false;
        try {
            $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_2_ID.'/membership'));
         } catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }

    public function testUserCanSeeGroupOfAnotherUserIfSheHasDelegatedPermissions() {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_3_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_2_ID.'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testUserCanUpdateAnotherUserIfSheHasDelegatedPermissions() {
        $value = json_encode(array(
            'values' => array(
                    'status' => "R",
            )
        ));
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.TestDataBuilder::TEST_USER_2_ID, null, $value));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_2_ID));
        $this->assertEquals($response->getStatusCode(), 200);
        $json = $response->json();
        $this->assertEquals(TestDataBuilder::TEST_USER_2_ID, $json['id']);
        $this->assertEquals('users/'.TestDataBuilder::TEST_USER_2_ID, $json['uri']);
        $this->assertEquals("R", $json['status']);

        $value = json_encode(array(
            'values' => array(
                    'status' => "A",
            )
        ));
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.TestDataBuilder::TEST_USER_2_ID, null, $value));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testSiteAdminCanSeeGroupOfAnyUser() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.TestDataBuilder::TEST_USER_2_ID.'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
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
