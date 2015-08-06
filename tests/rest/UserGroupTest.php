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
class UserGroupTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID));

        $this->assertEquals(
            $response->json(),
            array(
                'id'        => REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri'       => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'label'     => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users',
                'key'       => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdDoesNotWorkIfUserIsProjectMemberButNotProjectAdmin() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByToken(
                $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID)
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
            $this->getResponseByToken(
                $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_2_ID)
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
            $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_ID.'_999'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testOptionsUsers() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromADynamicGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_3/users'));
        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'         => REST_TestDataBuilder::ADMIN_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::ADMIN_ID,
                    'email'      => REST_TestDataBuilder::ADMIN_EMAIL,
                    'real_name'  => REST_TestDataBuilder::ADMIN_REAL_NAME,
                    'username'   => REST_TestDataBuilder::ADMIN_USER_NAME,
                    'ldap_id'    => '',
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                ),
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'email'      => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'  => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'username'   => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'    => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                ),
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_2_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_2_ID,
                    'email'      => '',
                    'real_name'  => '',
                    'username'   => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'    => '',
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                ),
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_3_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_3_ID,
                    'email'      => '',
                    'real_name'  => '',
                    'username'   => REST_TestDataBuilder::TEST_USER_3_NAME,
                    'ldap_id'    => '',
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'email'      => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'  => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'username'   => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'    => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetMultipleUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'email'      => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'  => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'username'   => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'    => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                ),
                array(
                    'id'         => REST_TestDataBuilder::TEST_USER_2_ID,
                    'uri'        => 'users/'.REST_TestDataBuilder::TEST_USER_2_ID,
                    'email'      => '',
                    'real_name'  => '',
                    'username'   => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'    => '',
                    'avatar_url' => '/themes/common/images/avatar_default.png',
                    'status'     => 'A'
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
