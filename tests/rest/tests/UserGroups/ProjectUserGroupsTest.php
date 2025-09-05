<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\REST\UserGroups;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\Attributes\Group;
use ProjectUGroup;
use Psl\Json;
use Tuleap\Disposable\Dispose;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RestBase;
use Tuleap\REST\Tests\API\ProjectsAPIHelper;
use Tuleap\REST\Tests\PlatformAccessControl;

#[DisableReturnValueGenerationForTestDoubles]
#[Group('UserGroupTests')]
final class ProjectUserGroupsTest extends RestBase
{
    public function testProjectUserGroups(): void
    {
        $this->assertOptions();
        $this->assertGetContainingCustomUserGroups();
        Dispose::using(
            new PlatformAccessControl(),
            $this->assertGetWithSystemUserGroupsReturnsAnonymousAndRegisteredWhenAnonymousUsersCanAccessThePlatform(...)
        );
    }

    private function assertOptions(): void
    {
        $options_response = $this->getResponse(
            $this->request_factory->createRequest(
                'OPTIONS',
                sprintf('projects/%s/user_groups', urlencode((string) $this->project_private_member_id))
            )
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $options_response->getHeaderLine('Allow')));
    }

    private function assertGetContainingCustomUserGroups(): void
    {
        $projects_api = new ProjectsAPIHelper($this->rest_request, $this->request_factory);

        $project_members_id = $this->project_private_member_id . '_' . ProjectUGroup::PROJECT_MEMBERS;
        $project_admins_id  = $this->project_private_member_id . '_' . ProjectUGroup::PROJECT_ADMIN;
        $expected_result    = [
            [
                'id'                     => $project_members_id,
                'uri'                    => 'user_groups/' . $project_members_id,
                'label'                  => 'Project members',
                'users_uri'              => 'user_groups/' . $project_members_id . '/users',
                'key'                    => BaseTestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY,
                'short_name'             => 'project_members',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id'                     => $project_admins_id,
                'uri'                    => 'user_groups/' . $project_admins_id,
                'label'                  => 'Project administrators',
                'users_uri'              => 'user_groups/' . $project_admins_id . '/users',
                'key'                    => 'ugroup_' . BaseTestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL . '_name_key',
                'short_name'             => 'project_admins',
                'additional_information' => [],
            ],
            [
                'id'                     => $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'uri'                    => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'label'                  => BaseTestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_LABEL,
                'users_uri'              => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID . '/users',
                'key'                    => 'ugroup_file_manager_admin_name_key',
                'short_name'             => 'file_manager_admins',
                'additional_information' => [],
            ],
            [
                'id'                     => $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'uri'                    => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'label'                  => 'Wiki administrators',
                'users_uri'              => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID . '/users',
                'key'                    => 'ugroup_wiki_admin_name_key',
                'short_name'             => 'wiki_admins',
                'additional_information' => [],
            ],
            [
                'id'                     => $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'uri'                    => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'label'                  => 'Forum moderators',
                'users_uri'              => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID . '/users',
                'key'                    => 'ugroup_forum_admin_name_key',
                'short_name'             => 'forum_admins',
                'additional_information' => [],
            ],
            [
                'id'                     => $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'uri'                    => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'label'                  => 'News administrators',
                'users_uri'              => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID . '/users',
                'key'                    => 'ugroup_news_admin_name_key',
                'short_name'             => 'news_admins',
                'additional_information' => [],

            ],
            [
                'id'                     => $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'uri'                    => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'label'                  => 'News writers',
                'users_uri'              => 'user_groups/' . $this->project_private_member_id . '_' . BaseTestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID . '/users',
                'key'                    => 'ugroup_news_writer_name_key',
                'short_name'             => 'news_editors',
                'additional_information' => [],
            ],
            [
                'id'                     => (string) BaseTestDataBuilder::STATIC_UGROUP_1_ID,
                'uri'                    => 'user_groups/' . BaseTestDataBuilder::STATIC_UGROUP_1_ID,
                'label'                  => BaseTestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri'              => 'user_groups/' . BaseTestDataBuilder::STATIC_UGROUP_1_ID . '/users',
                'key'                    => BaseTestDataBuilder::STATIC_UGROUP_1_LABEL,
                'short_name'             => 'static_ugroup_1',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id'                     => (string) BaseTestDataBuilder::STATIC_UGROUP_2_ID,
                'uri'                    => 'user_groups/' . BaseTestDataBuilder::STATIC_UGROUP_2_ID,
                'label'                  => BaseTestDataBuilder::STATIC_UGROUP_2_LABEL,
                'users_uri'              => 'user_groups/' . BaseTestDataBuilder::STATIC_UGROUP_2_ID . '/users',
                'key'                    => BaseTestDataBuilder::STATIC_UGROUP_2_LABEL,
                'short_name'             => 'static_ugroup_2',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id'                     => (string) BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'uri'                    => 'user_groups/' . BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'label'                  => BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'users_uri'              => 'user_groups/' . BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID . '/users',
                'key'                    => BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'short_name'             => BaseTestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'additional_information' => ['ldap' => null],
            ],
        ];
        $collection = $projects_api->getUserGroupsOfProject($this->project_private_member_id);
        self::assertEquals($expected_result, iterator_to_array($collection, false));
    }

    public function assertGetWithSystemUserGroupsReturnsAnonymousAndRegisteredWhenAnonymousUsersCanAccessThePlatform(
        PlatformAccessControl $platform_access,
    ): void {
        $platform_access->setForgeToAnonymous();

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                sprintf(
                    'projects/%s/user_groups?query=%s',
                    $this->project_public_member_id,
                    urlencode('{"with_system_user_groups":true}')
                )
            )
        );

        $json_response = Json\decode($response->getBody()->getContents());

        $user_group_ids = [];
        foreach ($json_response as $user_group) {
            $user_group_ids[] = $user_group['id'];
        }
        self::assertContains((string) ProjectUGroup::ANONYMOUS, $user_group_ids);
        self::assertContains((string) ProjectUGroup::REGISTERED, $user_group_ids);
    }
}
