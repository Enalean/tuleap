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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('UserGroupTests')]
class UserGroupTest extends RestBase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private function getResponseWithUser2($request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testGETId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_1_ID));

        $this->assertGETId($response);
    }

    public function testGETIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_1_ID
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETId($response);
    }

    public function testGETIdDoesWorkIfUserIsProjectMemberButNotProjectAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertGETId($response);
    }

    public function testGETIdDoesWorkIfUserIsNotProjectMember()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID),
            REST_TestDataBuilder::TEST_USER_2_NAME
        );

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($json['id'], (string) REST_TestDataBuilder::STATIC_UGROUP_2_ID);
        $this->assertEquals($json['uri'], 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID);
        $this->assertEquals($json['label'], REST_TestDataBuilder::STATIC_UGROUP_2_LABEL);
        $this->assertEquals($json['users_uri'], 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users');
        $this->assertEquals($json['key'], REST_TestDataBuilder::STATIC_UGROUP_2_LABEL);
        $this->assertEquals($json['short_name'], 'static_ugroup_2');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdThrowsA404IfUserGroupIdDoesNotExist()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "user_groups/$this->project_private_id" . '_999'));
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testOptionsUsers()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsUsersForReadUserOnly(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users'
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetUsersFromADynamicGroup(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_3/users')
        );
        $this->assertGETUserGroupsIdUser($response);
    }

    public function testGetUsersGroupsIdUserForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_3/users'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertGETUserGroupsIdUser($response);
    }

    private function assertGETUserGroupsIdUser(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR),
            [
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME],
                    'user_url'     => '/users/rest_api_restricted_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME,
                    'ldap_id'      => '',
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'R',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
                //rest_api_restricted_2 is project_member because he is also member of "Developpers"
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME],
                    'user_url'     => '/users/rest_api_restricted_2',
                    'email'        => REST_TestDataBuilder::TEST_USER_RESTRICTED_2_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_RESTRICTED_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME,
                    'ldap_id'      => '',
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'R',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => true,
                ],
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => REST_TestDataBuilder::TEST_USER_2_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME],
                    'user_url'     => '/users/rest_api_tester_3',
                    'email'        => REST_TestDataBuilder::TEST_USER_3_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_3_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_3_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
                //rest_api_tester_5 is project_member because he is also member of "Developpers"
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME],
                    'user_url'     => '/users/rest_api_tester_5',
                    'email'        => REST_TestDataBuilder::TEST_USER_5_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_5_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_5_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
            ]
        );
    }

    public function testGetUsersFromAStaticGroup()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users'));

        $this->assertEquals(
            json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR),
            [
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => true,
                ],
            ]
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetMultipleUsersFromAStaticGroup()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'));
        $this->assertEquals(
            json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR),
            [
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => 'https://localhost/users/rest_api_tester_1/avatar.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => true,
                ],
                [
                    'id'           => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'uri'          => 'users/' . $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME],
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => REST_TestDataBuilder::TEST_USER_2_EMAIL,
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => 'https://localhost/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false,
                    'has_avatar'   => false,
                ],
            ]
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPutDeniedUsersInProjectMembersForRestUserOnly(): void
    {
        $put_resource = json_encode(
            [
                'user_references' => [
                    ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                    ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME]],
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetMultipleUsersFromAStaticGroup')]
    public function testPutUsersInProjectMembersAddsMembers()
    {
        $put_resource = json_encode([
            'user_references' => [
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME]],
            ],
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream($put_resource)
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users'
            )
        );

        $response_get_json = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]['id'], 102);
        $this->assertEquals($response_get_json[1]['id'], 105);

        $this->restoreProjectMembersToAvoidBreakingOtherTests();
    }

    private function restoreProjectMembersToAvoidBreakingOtherTests()
    {
        $put_resource = json_encode([
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME]],
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME]],
        ]);

        $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME]],
                            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME]],
                            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME]],
                        ]
                    )
                )
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInProjectMembersAddsMembers')]
    public function testPutUsersInProjectAdmins()
    {
        $put_resource = json_encode([
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
        ]);
        $response     = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users',
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response_get_admins = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users'
            )
        );
        $admins_after_update = json_decode($response_get_admins->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $admins_after_update);
        $this->assertEquals($admins_after_update[0]['id'], $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]);
        $this->assertEquals($admins_after_update[1]['id'], $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]);

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                        ]
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInProjectAdmins')]
    public function testPutUsersInUserGroupWithUsername()
    {
        $put_resource = json_encode(
            [
                ['username' => REST_TestDataBuilder::TEST_USER_1_NAME],
                ['username' => REST_TestDataBuilder::TEST_USER_3_NAME],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )
        );

        $response_get_json = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]['id'], 102);
        $this->assertEquals($response_get_json[1]['id'], 104);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroupWithUsername')]
    public function testPutUsersInUserGroupWithEmail()
    {
        $put_resource = json_encode([
            ['email' => REST_TestDataBuilder::TEST_USER_2_EMAIL],
            ['email' => REST_TestDataBuilder::TEST_USER_3_EMAIL],
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )
        );

        $response_get_json = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]['id'], 103);
        $this->assertEquals($response_get_json[1]['id'], 104);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroupWithUsername')]
    public function testPutUsersInUserGroupWithEmailMultipleUsers()
    {
        $put_resource = json_encode(
            [
                ['email' => REST_TestDataBuilder::TEST_USER_1_EMAIL],
                ['email' => REST_TestDataBuilder::TEST_USER_3_EMAIL],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroupWithEmail')]
    public function testPutUsersInUserGroup()
    {
        $put_resource = json_encode(
            [
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_3_NAME]],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )
        );

        $response_get_json = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($response_get_json), 3);
        $this->assertEquals($response_get_json[0]['id'], 102);
        $this->assertEquals($response_get_json[1]['id'], 103);
        $this->assertEquals($response_get_json[2]['id'], 104);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroup')]
    public function testPutUsersInUserGroupWithTwoDifferentIds()
    {
        $put_resource = json_encode(
            [
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
                ['username' => REST_TestDataBuilder::TEST_USER_3_NAME],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroup')]
    public function testPutUsersInUserGroupWithUnknownKey()
    {
        $put_resource = json_encode(
            [
                ['unknown' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]],
                ['id' => REST_TestDataBuilder::TEST_USER_3_NAME],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroup')]
    public function testPutUsersInUserGroupWithNonAdminUser()
    {
        $put_resource = json_encode(
            [
                ['id' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]],
            ]
        );

        $response = $this->getResponseWithUser2(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutUsersInUserGroup')]
    public function testPutUsersInUserGroupWithNonValidRepresentation()
    {
        $put_resource = json_encode(
            [
                [
                    'id'       => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    'username' => REST_TestDataBuilder::TEST_USER_1_NAME,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users'
            )->withBody(
                $this->stream_factory->createStream(
                    $put_resource
                )
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testOptions()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'user_groups'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPOSTUserGroupForReadOnlyUser(): void
    {
        $post_resource = json_encode(
            [
                'project_id' => $this->project_private_member_id,
                'short_name' => 'static_ugroup_rest_1',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'user_groups')->withBody($this->stream_factory->createStream($post_resource)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTUserGroup()
    {
        $post_resource = json_encode(
            [
                'project_id' => $this->project_private_member_id,
                'short_name' => 'static_ugroup_rest_1',
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'user_groups')->withBody($this->stream_factory->createStream($post_resource)));

        $this->assertEquals($response->getStatusCode(), 201);

        $ugroup = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($ugroup['id'] > 0);
        $this->assertEquals($ugroup['short_name'], 'static_ugroup_rest_1');
    }

    public function testGetProjectUserGroups(): array
    {
        $project_id = urlencode($this->project_public_with_membership_id);
        $response   = $this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/user_groups"));

        $this->assertEquals(200, $response->getStatusCode());

        $user_groups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $user_groups;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetProjectUserGroups')]
    public function testPutUsersInStaticUserGroupInPublicSynchronizedProjectAlsoAddsThemToProjectMembers(array $user_groups)
    {
        $developpers = $this->findDeveloppers($user_groups);

        $body = json_encode(
            [
                ['username' => REST_TestDataBuilder::TEST_USER_4_NAME],
            ]
        );

        $developpers_id = urlencode($developpers['id']);
        $response_put   = $this->getResponse($this->request_factory->createRequest('PUT', "user_groups/$developpers_id/users")->withBody($this->stream_factory->createStream($body)));

        $this->assertEquals(200, $response_put->getStatusCode());

        $project_members_id = urlencode($this->project_public_with_membership_id . '_3');
        $response_get       = $this->getResponse($this->request_factory->createRequest('GET', "user_groups/$project_members_id/users"));

        $this->assertEquals(200, $response_get->getStatusCode());

        $members                  = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $user_4_is_project_member = false;
        foreach ($members as $member) {
            if ($member['username'] === REST_TestDataBuilder::TEST_USER_4_NAME) {
                $user_4_is_project_member = true;
            }
        }

        $this->assertTrue(
            $user_4_is_project_member,
            sprintf(
                'Expected to find user %s in the project members of project %s.',
                REST_TestDataBuilder::TEST_USER_4_NAME,
                REST_TestDataBuilder::PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME
            )
        );
    }

    /**
     * @throws Exception
     */
    private function findDeveloppers(array $user_groups): array
    {
        foreach ($user_groups as $user_group) {
            if ($user_group['short_name'] === REST_TestDataBuilder::STATIC_PUBLIC_WITH_MEMBERSHIP_UGROUP_DEVS_LABEL) {
                return $user_group;
            }
        }
        throw new Exception(
            sprintf(
                'Could not find the %s user group in project %s',
                REST_TestDataBuilder::STATIC_PUBLIC_WITH_MEMBERSHIP_UGROUP_DEVS_LABEL,
                REST_TestDataBuilder::PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME
            )
        );
    }

    private function assertGETId(\Psr\Http\Message\ResponseInterface $response): void
    {
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($json['id'], (string) REST_TestDataBuilder::STATIC_UGROUP_1_ID);
        $this->assertEquals($json['uri'], 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID);
        $this->assertEquals($json['label'], REST_TestDataBuilder::STATIC_UGROUP_1_LABEL);
        $this->assertEquals($json['users_uri'], 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users');
        $this->assertEquals($json['key'], REST_TestDataBuilder::STATIC_UGROUP_1_LABEL);
        $this->assertEquals($json['short_name'], 'static_ugroup_1');

        $this->assertArrayHasKey('project', $json);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
