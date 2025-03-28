<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Test\Rest\TuleapConfig;
use Tuleap\REST\ForgeAccessSandbox;

#[\PHPUnit\Framework\Attributes\Group('UserGroupTests')]
final class UsersTest extends RestBase // phpcs:ignore
{
    use ForgeAccessSandbox;

    private TuleapConfig $tuleap_config;

    public function setUp(): void
    {
        parent::setUp();
        $this->tuleap_config = TuleapConfig::instance();
        $this->setForgeToAnonymous();
    }

    public function testGetIdAsAnonymousHasMinimalInformation()
    {
        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['id']);
        $this->assertEquals('users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('https://localhost/users/rest_api_tester_1/avatar.png', $json['avatar_url']);
        $this->assertFalse(isset($json['email']));
        $this->assertFalse(isset($json['status']));
    }

    public function testGETIdAsRegularUser(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]));

        $this->assertGETId($response);
    }

    public function testGETIdWithReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETId($response);
    }

    public function testGETIdWithSelfKeyword()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/self'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertGETId($response);
    }

    private function assertGETId(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['id']);
        $this->assertEquals('users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_EMAIL, $json['email']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('https://localhost/users/rest_api_tester_1/avatar.png', $json['avatar_url']);
    }

    public function testGETIdDoesNotWorkIfUserDoesNotExist()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', 'users/1'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGETMembershipBySelfReturnsUserGroups(): void
    {
        $this->assertGETMembershipBySelfReturnsUserGroupsForAnID($this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]);
    }

    public function testGETMembershipBySelfReturnsUserGroupsWithSelfID(): void
    {
        $this->assertGETMembershipBySelfReturnsUserGroupsForAnID('self');
    }

    private function assertGETMembershipBySelfReturnsUserGroupsForAnID(string $id): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->request_factory->createRequest('GET', 'users/' . urlencode($id) . '/membership'));
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testGETMembershipWithProjectScopeDoesNotReturnSiteActive(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->request_factory->createRequest('GET', 'users/self/membership?scope=project'));
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $json);
        $this->assertNotContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testGETMembershipWithProjectScopeAndIdFormatReturnsGroupsWithId(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->request_factory->createRequest('GET', 'users/self/membership?scope=project&format=id'));
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $json);
        $this->assertContains($this->project_private_member_id . '_3', $json);
        $this->assertContains('102', $json);
    }

    public function testGetMembershipWithProjectScopeAndFullFormatReturnsUserGroups(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('GET', 'users/self/membership?scope=project&format=full')
        );
        $json     = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $expected_ugroup_id = $this->project_private_member_id . '_3';
        $this->assertEquals($expected_ugroup_id, $json[0]['id']);
        $this->assertEquals('user_groups/' . $expected_ugroup_id, $json[0]['uri']);
        $this->assertEquals('Project members', $json[0]['label']);
        $this->assertEquals('user_groups/' . $expected_ugroup_id . '/users', $json[0]['users_uri']);
        $this->assertEquals(TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY, $json[0]['key']);
        $this->assertEquals('project_members', $json[0]['short_name']);
        $this->assertProject101($json[0]['project']);

        $this->assertEquals(TestDataBuilder::STATIC_UGROUP_2_ID, $json[1]['id']);
        $this->assertEquals('user_groups/' . TestDataBuilder::STATIC_UGROUP_2_ID, $json[1]['uri']);
        $this->assertEquals(TestDataBuilder::STATIC_UGROUP_2_LABEL, $json[1]['label']);
        $this->assertEquals('user_groups/' . TestDataBuilder::STATIC_UGROUP_2_ID . '/users', $json[1]['users_uri']);
        $this->assertEquals(TestDataBuilder::STATIC_UGROUP_2_LABEL, $json[1]['key']);
        $this->assertEquals(TestDataBuilder::STATIC_UGROUP_2_LABEL, $json[1]['short_name']);
        $this->assertProject101($json[1]['project']);
    }

    private function assertProject101(array $project): void
    {
        $this->assertEquals($project['id'], '101');
        $this->assertEquals($project['uri'], 'projects/101');
        $this->assertEquals($project['label'], TestDataBuilder::PROJECT_PRIVATE_MEMBER_LABEL);
        $this->assertEquals($project['shortname'], TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
        $this->assertEquals($project['status'], 'active');
        $this->assertEquals($project['access'], 'private');
        $this->assertEquals($project['is_template'], false);
    }

    public function testGETMembershipWithIdFormatWithoutProjectScopeShouldReturn400(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->request_factory->createRequest('GET', 'users/self/membership?format=id'));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUserCannotSeeGroupOfAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/membership')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUserCanSeeGroupOfAnotherUserIfSheHasDelegatedPermissions()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_3_NAME, $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testUserCanUpdateAnotherUserIfSheHasDelegatedPermissions(): void
    {
        $value = json_encode(
            [
                'values' => [
                    'status' => 'R',
                ],
            ]
        );

        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]
            )->withBody($this->stream_factory->createStream($value))
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]));
        $this->assertEquals($response->getStatusCode(), 200);
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], $json['id']);
        $this->assertEquals('users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], $json['uri']);
        $this->assertEquals('R', $json['status']);

        $value    = json_encode(
            [
                'values' => [
                    'status' => 'A',
                ],
            ]
        );
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]
            )->withBody($this->stream_factory->createStream($value))
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testUserCanNotUpdateToRestrictedIfPlatformDoesntAllowsRestricted(): void
    {
        $value = json_encode(
            [
                'values' => [
                    'status' => 'R',
                ],
            ]
        );
        $this->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]
            )->withBody(
                $this->stream_factory->createStream(
                    $value
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testUserCanNotUpdateIfTheUpdatedUserIsSuperUser(): void
    {
        $value = json_encode(
            [
                'values' => [
                    'status' => 'R',
                ],
            ]
        );

        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::ADMIN_USER_NAME]
            )->withBody(
                $this->stream_factory->createStream(
                    $value
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testUserCanNotUpdateIfTheUpdatedUserNameIsNotValid(): void
    {
        $value = json_encode(
            [
                'values' => [
                    'username' => 'codendiadm',
                ],
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME]
            )->withBody(
                $this->stream_factory->createStream(
                    $value
                )
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPATCHUserWithReadOnlySiteAdmin(): void
    {
        $value    = json_encode(
            [
                'values' => [
                    'status' => 'R',
                ],
            ]
        );
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]
            )->withBody($this->stream_factory->createStream($value)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSiteAdminCanSeeGroupOfAnyUser(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/membership'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertGETMembershipAsAdmin($response);
    }

    private function assertGETMembershipAsAdmin(\Psr\Http\Message\ResponseInterface $response): void
    {
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $json);
    }

    public function testReadOnlySiteAdminCanSeeGroupOfAnyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/membership'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertGETMembershipAsAdmin($response);
    }

    public function testInRestrictedForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRestrictedForgeThatRestrictedProjectMemberIsMemberOfStaticUgroup()
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRestrictedForgeThatRestrictedMemberOfStaticUGroupAlsoBecomesProjectMemberInPrivateProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_restricted_2 should be listed as member of ug_103 ugroup because he is added as project member automatically'
        );
    }

    public function testInRestrictedForgeThatRestrictedNotProjectMemberIsNotMemberOfStaticUGroupInPublicProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_restricted_2 should NOT be listed as member of ug_103 ugroup because he is not project member'
        );
    }

    public function testInRestrictedForgeThatRestrictedIsMemberOfStaticUGroupInPublicInclRestrictedProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_restricted_2 should be listed as member of ug_105 ugroup because he is added as project member automatically'
        );
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME] . '/membership')
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRestrictedForgeThatActiveMemberOfStaticUGroupAlsoBecomesProjectMemberInPrivateProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_103 ugroup because he is added as project member automatically'
        );
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsMemberOfStaticUGroupInPublicProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_104 ugroup because the project is public'
        );
    }

    public function testInRestrictedForgeThatActiveMemberOfStaticUGroupAlsoBecomesProjectMemberInPublicInclRestrictedProject(): void
    {
        $this->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_105 ugroup because he is added as project member automatically'
        );
    }

    public function testInAnonymousForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInAnonymousForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInAnonymousForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupInPublicProject(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_104 ugroup because the project is public'
        );
    }

    public function testInAnonymousForgeThatActiveMemberOfStaticUGroupAlsoBecomesProjectMemberInPrivateProject(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_103 ugroup because he is added as project member automatically'
        );
    }

    public function testInRegularForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $this->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRegularForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $this->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRegularForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupInPublicProject(): void
    {
        $this->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership')
        );

        $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_104 ugroup because the project is public'
        );
    }

    public function testInRegularForgeThatActiveMemberOfStaticUGroupAlsoBecomesProjectMemberInPrivateProject(): void
    {
        $this->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME] . '/membership'
            )
        );
        $ugroups  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains(
            'ug_' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
            $ugroups,
            'rest_api_tester_5 should be listed as member of ug_103 ugroup because he is added as project member automatically'
        );
    }

    public function testGetUsersWithMatching(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', 'users?query=rest_api_tester&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertGETUsersWithMatching($response);
    }

    public function testGetUsersWithMatchingAsReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users?query=rest_api_tester&limit=10'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertGETUsersWithMatching($response);
    }

    private function assertGETUsersWithMatching(\Psr\Http\Message\ResponseInterface $response): void
    {
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(5, $json);
    }

    public function testGetUserWithExactSearchOnUsername(): void
    {
        $search = urlencode(
            json_encode(
                [
                    'username' => REST_TestDataBuilder::TEST_USER_1_NAME,
                ]
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        self::assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $json);
        self::assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json[0]['id']);
    }

    public function testGetUserWithExactSearchOnLoginName(): void
    {
        $search = urlencode(
            json_encode(
                [
                    'loginname' => REST_TestDataBuilder::TEST_USER_1_NAME,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        self::assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $json);
        self::assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json[0]['id']);
    }

    public function testGetUserWithExactSearchOnEmail(): void
    {
        $search = urlencode(
            json_encode(
                [
                    'email' => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        self::assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json[0]['id']);
    }

    public function testGetUserWithExactSearchWithoutResult()
    {
        $search = urlencode(
            json_encode(
                [
                    'username' => 'muppet',
                ]
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(0, $json);
    }

    public function testGetUserWithInvalidJson()
    {
        $search = urlencode('{jeanclaude}');

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testOptionsPreferences(): void
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('OPTIONS', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsPreferencesWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPatchPreferences(): void
    {
        $preference = json_encode(
            [
                'key'   => 'my_preference',
                'value' => 'my_preference_value_1',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPatchPreferencesMultipleTimes(): void
    {
        $preference = json_encode(
            [
                'key' => 'my_preference_multiple_times',
                'value' => 'my_preference_value_1',
            ],
            JSON_THROW_ON_ERROR
        );

        $response_1 = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals(200, $response_1->getStatusCode());
        $response_2 = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals(200, $response_2->getStatusCode());
    }

    public function testPatchPreferencesWithSelfKeyword(): void
    {
        $preference = json_encode(
            [
                'key'   => 'my_preference',
                'value' => 'my_preference_value',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('PATCH', 'users/self/preferences')->withBody($this->stream_factory->createStream($preference))
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPatchPreferencesWithReadOnlySiteAdmin(): void
    {
        $preference = json_encode(
            [
                'key'   => 'my_preference',
                'value' => 'my_preference_value',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPatchPreferencesAnotherUser()
    {
        $preference = json_encode(
            [
                'key'   => 'my_preference',
                'value' => 'my_preference_value',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testGETPreferences(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=my_preference')
        );

        $this->assertGETPreferences($response);
    }

    public function testGETPreferencesReturnsFalseIfPreferenceDoesNotExistInDB(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=my_preference_not_in_db')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('my_preference_not_in_db', $json['key']);
        $this->assertFalse($json['value']);
    }

    public function testGETPreferencesWithSelfKeyword()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/self/preferences?key=my_preference')
        );

        $this->assertGETPreferences($response);
    }

    public function testGETPreferencesWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=my_preference'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function assertGETPreferences(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('my_preference', $json['key']);
        $this->assertEquals('my_preference_value', $json['value']);
    }

    public function testDeletePreferences()
    {
        $preference = json_encode(
            [
                'key'   => 'preference_to_be_deleted',
                'value' => 'awesome_value',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'DELETE',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=preference_to_be_deleted'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'GET',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=preference_to_be_deleted'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('preference_to_be_deleted', $json['key']);
        $this->assertEquals(false, $json['value']);
    }

    public function testDeletePreferencesWithSelfKeyword()
    {
        $preference = json_encode(
            [
                'key'   => 'preference_to_be_deleted',
                'value' => 'awesome_value',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('PATCH', 'users/self/preferences')->withBody(
                $this->stream_factory->createStream($preference)
            )
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('DELETE', 'users/self/preferences?key=preference_to_be_deleted')
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/self/preferences?key=preference_to_be_deleted')
        );
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('preference_to_be_deleted', $json['key']);
        $this->assertEquals(false, $json['value']);
    }

    public function testDeletePreferencesWithReadOnlySiteAdmin(): void
    {
        $preference = json_encode(
            [
                'key' => 'preference_to_be_deleted',
                'value' => 'awesome_value',
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PATCH',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences'
            )->withBody($this->stream_factory->createStream($preference))
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME] . '/preferences?key=preference_to_be_deleted'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETPreferencesAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/preferences?key=my_preference')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETHistoryAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/history')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETHistoryWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/history'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTHistoryAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/history'
            )->withBody($this->stream_factory->createStream(json_encode([])))
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTHistoryWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/history'
            )->withBody($this->stream_factory->createStream(json_encode([]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTHistoryManipulation()
    {
        $history_entries = json_encode(
            [
                [
                    'visit_time' => 1496386853,
                    'xref' => 'bugs #845',
                    'link' => '/plugins/tracker/?aid=845',
                    'title' => '',
                ],
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('PUT', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/history')->withBody($this->stream_factory->createStream($history_entries))
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETAccessKeysWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME] . '/access_keys'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
