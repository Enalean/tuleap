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
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID));

        $this->assertEquals(
            $response->json(),
            array(
                'id'        => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri'       => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'label'     => TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID.'/users'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdDoesNotWorkIfUserIsProjectMemberButNotProjectAdmin() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PUBLIC_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID));
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
            $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID));
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
            $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_ID.'_999'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testOptionsUsers() {
        $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromADynamicGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_3/users'));
        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'        => TestDataBuilder::ADMIN_ID,
                    'email'     => TestDataBuilder::ADMIN_EMAIL,
                    'real_name' => TestDataBuilder::ADMIN_REAL_NAME,
                    'username'  => TestDataBuilder::ADMIN_USER_NAME,
                    'ldap_id'   => ''
                ),
                array(
                    'id'        => TestDataBuilder::TEST_USER_1_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'   => ''
                ),
                array(
                    'id'        => TestDataBuilder::TEST_USER_2_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'   => ''
                ),
                array(
                    'id'        => TestDataBuilder::TEST_USER_3_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_3_NAME,
                    'ldap_id'   => ''
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'        => TestDataBuilder::TEST_USER_1_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'   => ''
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetMultipleUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'        => TestDataBuilder::TEST_USER_1_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'   => ''
                ),
                array(
                    'id'        => TestDataBuilder::TEST_USER_2_ID,
                    'email'     => '',
                    'real_name' => '',
                    'username'  => TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'   => ''
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }
}