<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project\GroupMembers;

use PFUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Tuleap\Project\UGroups\XML\XMLUserGroup;
use Tuleap\Project\XML\XMLUserGroups;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\GetTuleapUserFromJiraUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLUser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GroupMembersImporterTest extends TestCase
{
    #[DataProvider('getJiraServerTestsData')]
    public function testJiraServer(array $jira_payloads, array $jira_to_tuleap_users, callable $tests): void
    {
        $jira_default_user = UserTestBuilder::aUser()->withId(TrackerImporterUser::ID)->build();

        $client       = new class extends JiraServerClientStub {
        };
        $client->urls = $jira_payloads;

        $users_converter        = new class ($jira_default_user) implements GetTuleapUserFromJiraUser {
            public array $users = [];

            public function __construct(private PFUser $default_user)
            {
            }

            public function getAssignedTuleapUser(string $unique_account_identifier): PFUser
            {
                return $this->users[$unique_account_identifier] ?? $this->default_user;
            }

            public function retrieveJiraAuthor(JiraUser $update_author): PFUser
            {
                return UserTestBuilder::aUser()->build();
            }
        };
        $users_converter->users = $jira_to_tuleap_users;

        $importer = new GroupMembersImporter(
            $client,
            new NullLogger(),
            $users_converter,
            $jira_default_user,
        );

        $user_groups_xml = $importer->getUserGroups('SP');
        $tests($user_groups_xml);
    }

    public static function getJiraServerTestsData(): iterable
    {
        $payload_only_administrators = [
            '/rest/api/2/project/SP/role' => [
                'Administrators' => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
            ],
            '/rest/api/2/project/11102/role/10002' => [
                'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                'name'        => 'Administrators',
                'id'          => 10002,
                'description' => 'foo',
                'actors'      => [
                    [
                        'id'          => 11339,
                        'displayName' => 'John Doe',
                        'type'        => 'atlassian-user-role-actor',
                        'name'        => 'john_doe',
                        'avatarUrl'   => 'https://jira.example.com/secure/useravatar?size=xsmall&ownerId=JIRAUSER10125&avatarId=10605',
                    ],

                ],
            ],
        ];

        $payload_with_multiple_groups = [
            '/rest/api/2/project/SP/role' => [
                'Développeur / Exploitant'     => 'https://jira.example.com/rest/api/2/project/11102/role/10101',
                'Administrators' => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                'Testers'        => 'https://jira.example.com/rest/api/2/project/11102/role/10102',
            ],
            '/rest/api/2/project/11102/role/10002' => [
                'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                'name'        => 'Administrators',
                'id'          => 10002,
                'description' => 'foo',
                'actors'      => [
                    [
                        'id'          => 11339,
                        'displayName' => 'John Doe',
                        'type'        => 'atlassian-user-role-actor',
                        'name'        => 'john_doe',
                        'avatarUrl'   => 'https://jira.example.com/secure/useravatar?size=xsmall&ownerId=JIRAUSER10125&avatarId=10605',
                    ],

                ],
            ],
            '/rest/api/2/project/11102/role/10101' => [
                'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10101',
                'name'        => 'Développeur / Exploitant',
                'id'          => 10101,
                'description' => 'foo',
                'actors'      => [
                    [
                        'id'          => 11340,
                        'displayName' => 'Foo Bar',
                        'type'        => 'atlassian-user-role-actor',
                        'name'        => 'foo_bar',
                        'avatarUrl'   => 'https://jira.example.com/secure/useravatar?size=xsmall&ownerId=JIRAUSER10125&avatarId=10605',
                    ],

                ],
            ],
            '/rest/api/2/project/11102/role/10102' => [
                'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10102',
                'name'        => 'Testers',
                'id'          => 10102,
                'description' => 'foo',
                'actors'      => [
                    [
                        'id'          => 11341,
                        'displayName' => 'Jane Biz',
                        'type'        => 'atlassian-user-role-actor',
                        'name'        => 'jane_biz',
                        'avatarUrl'   => 'https://jira.example.com/secure/useravatar?size=xsmall&ownerId=JIRAUSER10125&avatarId=10605',
                    ],

                ],
            ],
        ];

        return [
            'it import jira administrators as project_admins' => [
                'jira_payloads' => $payload_only_administrators,
                'jira_to_tuleap_users' => [
                    'john_doe' => UserTestBuilder::aUser()->withUserName('jdoe')->build(),
                ],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    $project_admin = self::getMemberOf($user_groups_xml, \ProjectUGroup::PROJECT_ADMIN_NAME);
                    self::assertCount(1, $project_admin->users);
                    self::assertEquals('jdoe', $project_admin->users[0]->name);
                },
            ],
            'it excludes missing users that are converted as XML import user' => [
                'jira_payloads' => $payload_only_administrators,
                'jira_to_tuleap_users' => [],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    $project_admin = self::getMemberOf($user_groups_xml, \ProjectUGroup::PROJECT_ADMIN_NAME);
                    self::assertCount(0, $project_admin->users);
                },
            ],
            'it collect all members of all groups as project members' => [
                'jira_payloads' => $payload_with_multiple_groups,
                'jira_to_tuleap_users' => [
                    'john_doe' => UserTestBuilder::aUser()->withUserName('jdoe')->build(),
                    'foo_bar' => UserTestBuilder::aUser()->withUserName('fbar')->build(),
                    'jane_biz' => UserTestBuilder::aUser()->withUserName('jbiz')->build(),
                ],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    $project_members = self::getMemberOf($user_groups_xml, \ProjectUGroup::PROJECT_MEMBERS_NAME);

                    self::assertCount(3, $project_members->users);
                    self::assertContains('jdoe', array_map(static fn (XMLUser $user) => $user->name, $project_members->users));
                    self::assertContains('fbar', array_map(static fn (XMLUser $user) => $user->name, $project_members->users));
                    self::assertContains('jbiz', array_map(static fn (XMLUser $user) => $user->name, $project_members->users));
                },
            ],
            'each jira role correspond to a project user group' => [
                'jira_payloads' => $payload_with_multiple_groups,
                'jira_to_tuleap_users' => [
                    'john_doe' => UserTestBuilder::aUser()->withUserName('jdoe')->build(),
                    'foo_bar' => UserTestBuilder::aUser()->withUserName('fbar')->build(),
                    'jane_biz' => UserTestBuilder::aUser()->withUserName('jbiz')->build(),
                ],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    self::assertCount(4, $user_groups_xml->user_groups);

                    $testers = self::getMemberOf($user_groups_xml, 'Testers');
                    self::assertCount(1, $testers->users);
                    self::assertContains('jbiz', array_map(static fn (XMLUser $user) => $user->name, $testers->users));

                    $devs = self::getMemberOf($user_groups_xml, 'Developpeur_Exploitant');
                    self::assertCount(1, $devs->users);
                    self::assertContains('fbar', array_map(static fn (XMLUser $user) => $user->name, $devs->users));
                },
            ],
        ];
    }

    private static function getMemberOf(XMLUserGroups $user_groups_xml, string $wanted_user_group_name): XMLUserGroup
    {
        return array_values(array_filter($user_groups_xml->user_groups, static fn (XMLUserGroup $user_group) => $user_group->name === $wanted_user_group_name))[0];
    }

    #[DataProvider('getJiraCloudTestsData')]
    public function testJiraCloud(array $jira_payloads, array $jira_to_tuleap_users, callable $tests): void
    {
        $jira_default_user = UserTestBuilder::aUser()->withId(TrackerImporterUser::ID)->build();

        $client       = new class extends JiraCloudClientStub {
        };
        $client->urls = $jira_payloads;

        $users_converter        = new class ($jira_default_user) implements GetTuleapUserFromJiraUser {
            public array $users = [];

            public function __construct(private PFUser $default_user)
            {
            }

            public function getAssignedTuleapUser(string $unique_account_identifier): PFUser
            {
                return $this->users[$unique_account_identifier] ?? $this->default_user;
            }

            public function retrieveJiraAuthor(JiraUser $update_author): PFUser
            {
                return UserTestBuilder::aUser()->build();
            }
        };
        $users_converter->users = $jira_to_tuleap_users;

        $importer = new GroupMembersImporter(
            $client,
            new NullLogger(),
            $users_converter,
            $jira_default_user,
        );

        $user_groups_xml = $importer->getUserGroups('SP');
        $tests($user_groups_xml);
    }

    public static function getJiraCloudTestsData(): iterable
    {
        return [
            'it import jira administrators as project_admins' => [
                'jira_payloads' => [
                    '/rest/api/2/project/SP/role' => [
                        'Administrators' => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                    ],
                    '/rest/api/2/project/11102/role/10002' => [
                        'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                        'name'        => 'Administrators',
                        'id'          => 10002,
                        'description' => 'foo',
                        'actors'      => [
                            [
                                'id'          => 10254,
                                'displayName' => 'John Doe',
                                'type'        => 'atlassian-user-role-actor',
                                'actorUser'   => [
                                    'accountId' => '5d2ece042d76f30c36bf7e96',
                                ],
                            ],
                        ],
                    ],
                ],
                'jira_to_tuleap_users' => [
                    '5d2ece042d76f30c36bf7e96' => UserTestBuilder::aUser()->withUserName('jdoe')->build(),
                ],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    $project_admin = self::getMemberOf($user_groups_xml, \ProjectUGroup::PROJECT_ADMIN_NAME);
                    self::assertCount(1, $project_admin->users);
                    self::assertEquals('jdoe', $project_admin->users[0]->name);
                },
            ],
            'it excludes groups of groups' => [
                'jira_payloads' => [
                    '/rest/api/2/project/SP/role' => [
                        'Administrators' => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                    ],
                    '/rest/api/2/project/11102/role/10002' => [
                        'self'        => 'https://jira.example.com/rest/api/2/project/11102/role/10002',
                        'name'        => 'Administrators',
                        'id'          => 10002,
                        'description' => 'foo',
                        'actors' => [
                            [
                                'id'          => 10100,
                                'displayName' => 'jira-administrators',
                                'type'        => 'atlassian-group-role-actor',
                                'name'        => 'jira-administrators',
                                'actorGroup'  => [
                                    'name'        => 'jira-administrators',
                                    'displayName' => 'jira-administrators',
                                    'groupId'     => 'bf146b3f-7f6a-46e7-8cb1-1c8bbe8b2406',
                                ],
                            ],
                            [
                                'id'          => 10254,
                                'displayName' => 'John Doe',
                                'type'        => 'atlassian-user-role-actor',
                                'actorUser'   => [
                                    'accountId' => '5d2ece042d76f30c36bf7e96',
                                ],
                            ],
                        ],
                    ],
                ],
                'jira_to_tuleap_users' => [
                    '5d2ece042d76f30c36bf7e96' => UserTestBuilder::aUser()->withUserName('jdoe')->build(),
                ],
                'tests' => function (XMLUserGroups $user_groups_xml) {
                    $project_admin = self::getMemberOf($user_groups_xml, \ProjectUGroup::PROJECT_ADMIN_NAME);
                    self::assertCount(1, $project_admin->users);
                    self::assertEquals('jdoe', $project_admin->users[0]->name);
                },
            ],
        ];
    }
}
