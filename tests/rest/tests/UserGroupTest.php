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
 * @group UserGroupTests
 */
class UserGroupTest extends RestBase {

    private function getResponseWithUser2($request) {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID));

        $this->assertEquals(
            $response->json(),
            array(
                'id'         => (string) REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri'        => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'label'      => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri'  => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users',
                'key'        => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'short_name' => 'static_ugroup_1'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdDoesNotWorkIfUserIsProjectMemberButNotProjectAdmin() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponse(
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID),
                REST_TestDataBuilder::TEST_USER_2_NAME
            );
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETIdDoesNotWorkIfUserIsNotProjectMember() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponse(
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_2_ID),
                REST_TestDataBuilder::TEST_USER_2_NAME
            );
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETIdThrowsA404IfUserGroupIdDoesNotExist() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $response = $this->getResponse($this->client->get("user_groups/$this->project_private_id"."_999"));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testOptionsUsers() {
        $response = $this->getResponse($this->client->get('user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromADynamicGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.$this->project_private_member_id.'_3/users'));
        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME],
                    'user_url'     => '/users/rest_api_restricted_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME,
                    'ldap_id'      => '',
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'R',
                    'is_anonymous' => false,
                    'has_avatar' => false
                ),
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                ),
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => REST_TestDataBuilder::TEST_USER_2_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                ),
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME],
                    'user_url'     => '/users/rest_api_tester_3',
                    'email'        => REST_TestDataBuilder::TEST_USER_3_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_3_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_3_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetMultipleUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                ),
                array(
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'uri'          => 'users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => REST_TestDataBuilder::TEST_USER_2_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetMultipleUsersFromAStaticGroup
     */
    public function testPutUsersInProjectMembersAddsMembers() {
        $put_resource = json_encode(array(
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME])
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]["id"], 102);
        $this->assertEquals($response_get_json[1]["id"], 105);

        $this->restoreProjectMembersToAvoidBreakingOtherTests();
    }

    private function restoreProjectMembersToAvoidBreakingOtherTests() {
        $put_resource = json_encode(array(
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME])
        ));

        $response_put = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID.'/users',
            null,
            $put_resource
        ));

        $response_put = $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID.'/users',
            null,
            json_encode(array(
                array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
                array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME]),
                array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME]),
                array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME])
            ))
        ));
    }

    /**
     * @depends testPutUsersInProjectMembersAddsMembers
     */
    public function testPutUsersInProjectAdmins()
    {
        $put_resource = json_encode([
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
        ]);
        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID.'/users',
            null,
            $put_resource
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $response_get_admins = $this->getResponse($this->client->get(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID.'/users'
        ));
        $admins_after_update = $response_get_admins->json();
        $this->assertCount(2, $admins_after_update);
        $this->assertEquals($admins_after_update[0]['id'], $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]);
        $this->assertEquals($admins_after_update[1]['id'], $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]);

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID.'/users',
            null,
            json_encode([
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
            ])
        ));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPutUsersInProjectAdmins
     */
    public function testPutUsersInUserGroupWithUsername() {
        $put_resource = json_encode(array(
            array('username' => REST_TestDataBuilder::TEST_USER_1_NAME),
            array('username' => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]["id"], 102);
        $this->assertEquals($response_get_json[1]["id"], 104);
    }

    /**
     * @depends testPutUsersInUserGroupWithUsername
     */
    public function testPutUsersInUserGroupWithEmail() {
        $put_resource = json_encode(array(
            array('email' => REST_TestDataBuilder::TEST_USER_2_EMAIL),
            array('email' => REST_TestDataBuilder::TEST_USER_3_EMAIL)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]["id"], 103);
        $this->assertEquals($response_get_json[1]["id"], 104);
    }

    /**
     * @depends testPutUsersInUserGroupWithUsername
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithEmailMultipleUsers() {
        $put_resource = json_encode(array(
            array('email' => REST_TestDataBuilder::TEST_USER_1_EMAIL),
            array('email' => REST_TestDataBuilder::TEST_USER_3_EMAIL)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testPutUsersInUserGroupWithEmail
     */
    public function testPutUsersInUserGroup() {
        $put_resource = json_encode(array(
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]),
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME])
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 3);
        $this->assertEquals($response_get_json[0]["id"], 102);
        $this->assertEquals($response_get_json[1]["id"], 103);
        $this->assertEquals($response_get_json[2]["id"], 104);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithTwoDifferentIds() {
        $put_resource = json_encode(array(
            array('id'       => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            array('id'       => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]),
            array('username' => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithUnknownKey() {
        $put_resource = json_encode(array(
            array('unknown' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            array('id'      => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]),
            array('id'       => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithNonAdminUser() {
        $put_resource = json_encode(array(
            array('id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME])
        ));

        $this->getResponseWithUser2($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithNonValidRepresentation() {
        $put_resource = json_encode(array(
            array(
                'id'       => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                'username' => REST_TestDataBuilder::TEST_USER_1_NAME
            )
        ));

        $this->getResponse($this->client->put(
            'user_groups/'.$this->project_private_member_id.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );
    }

    public function testOptions()
    {
        $response = $this->getResponse($this->client->options('user_groups'));

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPOSTUserGroup()
    {
        $post_resource = json_encode(array(
            'project_id' => $this->project_private_member_id,
            'short_name' => 'static_ugroup_rest_1'
        ));

        $response = $this->getResponse($this->client->post('user_groups', null, $post_resource));

        $this->assertEquals($response->getStatusCode(), 201);

        $ugroup = $response->json();

        $this->assertTrue($ugroup['id'] > 0);
        $this->assertEquals($ugroup['short_name'], 'static_ugroup_rest_1');
    }
}
