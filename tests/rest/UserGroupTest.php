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
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_NAME),
            $request
        );
    }

   public function testGETId() {
        $response = $this->getResponse($this->client->get('user_groups/101_101'));

        $this->assertEquals(
            $response->json(),
            array(
                'id'        => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'uri'       => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'label'     => 'static_ugroup',
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/users'
                )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdDoesNotWorkIfUserIsProjectMemberButNotProjectAdmin() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PUBLIC_MEMBER_ID.'_'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));
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
            $response = $this->getResponse($this->client->get('user_groups/'.TestDataBuilder::PROJECT_PRIVATE_ID.'_'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));
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
}