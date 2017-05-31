<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

    /** @var REST_TestDataBuilderNG */
    private $data_builder;

    public function __construct()
    {
        parent::__construct();
        $this->data_builder = new REST_TestDataBuilderNG();
        $this->data_builder->setForgeToAnonymous();
    }

    public function testGetIdAsAnonymousHasMinimalInformation() {
        $response = $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID)->send();
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_ID, $json['id']);
        $this->assertEquals('users/'.REST_TestDataBuilder::TEST_USER_1_ID, $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('http://localhost/themes/common/images/avatar_default.png', $json['avatar_url']);
        $this->assertFalse(isset($json['email']));
        $this->assertFalse(isset($json['status']));
    }

    public function testGETIdAsRegularUser() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_ID, $json['id']);
        $this->assertEquals('users/'.REST_TestDataBuilder::TEST_USER_1_ID, $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_EMAIL, $json['email']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('http://localhost/themes/common/images/avatar_default.png', $json['avatar_url']);
    }

    public function testGETIdDoesNotWorkIfUserDoesNotExist() {
        $exception_thrown = false;
        try {
            $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/1'));
        } catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }

    public function testGETMembershipBySelfReturnsUserGroups() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/membership'));
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
            $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/membership'));
         } catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }

    public function testUserCanSeeGroupOfAnotherUserIfSheHasDelegatedPermissions() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_3_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/membership'));
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
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.REST_TestDataBuilder::TEST_USER_2_ID, null, $value));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID));
        $this->assertEquals($response->getStatusCode(), 200);
        $json = $response->json();
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_2_ID, $json['id']);
        $this->assertEquals('users/'.REST_TestDataBuilder::TEST_USER_2_ID, $json['uri']);
        $this->assertEquals("R", $json['status']);

        $value = json_encode(array(
            'values' => array(
                    'status' => "A",
            )
        ));
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.REST_TestDataBuilder::TEST_USER_2_ID, null, $value));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testSiteAdminCanSeeGroupOfAnyUser() {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
    }

    public function testInRestrictedForgeThatActiveProjectMemberIsMemberOfStaticUgroup() {
        $this->data_builder->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatRestrictedProjectMemberIsMemberOfStaticUgroup() {
        $this->data_builder->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_RESTRICTED_1_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatRestrictedNotProjectMemberIsOnlyMemberOfStaticUgroupInPublicInclRestricted() {
        $this->data_builder->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_RESTRICTED_2_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup() {
        $this->data_builder->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_4_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects() {
        $this->data_builder->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_5_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInAnonymousForgeThatActiveProjectMemberIsMemberOfStaticUgroup() {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }


    public function testInAnonymousForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup() {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_4_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInAnonymousForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects() {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_5_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRegularForgeThatActiveProjectMemberIsMemberOfStaticUgroup() {
        $this->data_builder->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }


    public function testInRegularForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup() {
        $this->data_builder->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_4_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testInRegularForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects() {
        $this->data_builder->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_5_ID.'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->data_builder->setForgeToAnonymous();
    }

    public function testGetUsersWithMatching() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users?query=rest_api_tester&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(5, $json);
    }

    public function testGetUserWithExactSearch() {
        $search = urlencode(
            json_encode(
                array(
                    'username' => REST_TestDataBuilder::TEST_USER_1_NAME
                )
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_ID, $json[0]['id']);
    }

    public function testGetUserWithExactSearchWithoutResult() {
        $search = urlencode(
            json_encode(
                array(
                    'username' => 'muppet'
                )
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(0, $json);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetUserWithInvalidJson() {
        $search = urlencode('{jeanclaude}');

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testOptionsPreferences() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->options('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/preferences'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPatchPreferences() {
        $preference = json_encode(
            array(
                'key'   => 'my_preference',
                'value' => 'my_preference_value'
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->patch('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/preferences', null, $preference));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPatchPreferencesAnotherUser() {
        $preference = json_encode(
            array(
                'key'   => 'my_preference',
                'value' => 'my_preference_value'
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->patch('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/preferences', null, $preference));
        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testGETPreferences() {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_1_ID.'/preferences?key=my_preference'));

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals('my_preference', $json['key']);
        $this->assertEquals('my_preference_value', $json['value']);
    }

    public function testGETPreferencesAnotherUser()
    {
        $exception_thrown = false;
        try {
            $this->getResponseByName(
                REST_TestDataBuilder::TEST_USER_1_NAME,
                $this->client->get('users/'.REST_TestDataBuilder::TEST_USER_2_ID.'/preferences?key=my_preference')
            );
        } catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }
}
