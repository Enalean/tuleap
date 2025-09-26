<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\UserRole;

use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserRolesCheckerTest extends TestCase
{
    public function testItDoesNotThrowAnExceptionIfUserIsInAdminstrators(): void
    {
        $checker = new UserRolesChecker();

        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    [
                        'name' => 'Member',
                    ],
                    [
                        'name' => 'Administrators',
                    ],
                ];
            }
        };

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );

        $this->addToAssertionCount(1);
    }

    public function testItDoesNotThrowAnExceptionIfUserIsInAdminstrator(): void
    {
        $checker = new UserRolesChecker();

        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    [
                        'name' => 'Member',
                    ],
                    [
                        'name' => 'Administrator',
                    ],
                ];
            }
        };

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );

        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionIfUserIsNotAdmin(): void
    {
        $checker = new UserRolesChecker();

        $this->expectException(UserIsNotProjectAdminException::class);
        $this->expectExceptionMessage('User is not project administrator.');

        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    [
                        'name' => 'Member',
                    ],
                ];
            }
        };

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }

    public function testItThrowsAnExceptionIfResponseIsNotWellFormed(): void
    {
        $checker = new UserRolesChecker();

        $this->expectException(UserRolesResponseNotWellFormedException::class);
        $this->expectExceptionMessage('User roles key `name` not found');

        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    [
                        'whatever' => 'Member',
                    ],
                ];
            }
        };

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }

    public function testItThrowsAnExceptionIfResponseIsNull(): void
    {
        $checker = new UserRolesChecker();

        $this->expectException(UserRolesResponseNotWellFormedException::class);
        $this->expectExceptionMessage('User roles data is null');

        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return null;
            }
        };

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }

    public function testItDetectsProjectAdministratorWithJiraServer(): void
    {
        $checker = new UserRolesChecker();
        $client  = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/mypermissions?projectKey=proj01' => [
                'permissions' => [
                    'PROJECT_ADMIN' => [
                        'id'             => '23',
                        'key'            => 'PROJECT_ADMIN',
                        'name'           => 'Manage projects',
                        'type'           => 'PROJECT',
                        'description'    => 'Ability to manage Jira projects.',
                        'havePermission' => true,
                        'deprecatedKey'  => true,
                    ],
                ],
            ],
        ]);

        $this->expectNotToPerformAssertions();

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }

    public function testItDetectsAdministratorsOfAllProjectsWithJiraServer(): void
    {
        $checker = new UserRolesChecker();
        $client  = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/mypermissions?projectKey=proj01' => [
                'permissions' => [
                    'ADMINISTER_PROJECTS' => [
                        'id'             => '23',
                        'key'            => 'ADMINISTER_PROJECTS',
                        'name'           => 'Manage projects',
                        'type'           => 'PROJECT',
                        'description'    => 'Ability to manage Jira projects.',
                        'havePermission' => true,
                    ],
                ],
            ],
        ]);

        $this->expectNotToPerformAssertions();

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }

    public function testItDetectsLackOfAdministrationPermissionWithJiraServer(): void
    {
        $checker = new UserRolesChecker();
        $client  = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/mypermissions?projectKey=proj01' => [
                'permissions' => [
                    'SCHEDULE_ISSUES' => [
                        'id'             => '28',
                        'key'            => 'SCHEDULE_ISSUES',
                        'name'           => 'Plan tickets',
                        'type'           => 'PROJECT',
                        'description'    => '...',
                        'havePermission' => true,
                    ],
                ],
            ],
        ]);

        $this->expectException(UserIsNotProjectAdminException::class);

        $checker->checkUserIsAdminOfJiraProject(
            $client,
            new NullLogger(),
            'proj01'
        );
    }
}
