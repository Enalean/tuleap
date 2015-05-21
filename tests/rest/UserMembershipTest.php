<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * @group UserMembershipTests
 */
class UserMembershipsTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::ADMIN_USER_NAME),
            $request
        );
    }

    public function testGET() {
        $comma_separated_users = implode(',',array(
            TestDataBuilder::TEST_USER_1_NAME,
            TestDataBuilder::TEST_USER_2_NAME,
            TestDataBuilder::TEST_USER_3_NAME
        ));

        $response = $this->getResponse($this->client->get('users_memberships/?users='.$comma_separated_users.'&limit=3&offset=0'));

        $user1_groups = array(
            "site_active",
            "private-member_project_members",
            "private-member_project_admin",
            "public-member_project_members",
            "pbi-6348_project_members",
            "dragndrop_project_members",
            "dragndrop_project_admin",
            "test-git_project_members",
            "test-git_project_admin",
            "rest-xml-api_project_members",
            "ug_101",
            "ug_102"
        );

        $user2_groups = array(
            "site_active",
            "private-member_project_members",
            "ug_102"
        );

        $user3_groups = array(
            "site_active",
            "private-member_project_members",
            "ug_103",
            "ug_104"
        );

        $response_json = $response->json();
        $this->assertCount(3,$response_json);
        $this->assertEquals($response_json[0]["user_groups"], $user1_groups);
        $this->assertEquals($response_json[1]["user_groups"], $user2_groups);
        $this->assertEquals($response_json[2]["user_groups"], $user3_groups);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptions() {
        $response = $this->getResponse($this->client->options('users_memberships'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
