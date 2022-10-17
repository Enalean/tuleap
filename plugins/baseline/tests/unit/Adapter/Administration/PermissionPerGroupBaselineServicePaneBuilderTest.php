<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Domain\RoleAssignmentsUpdate;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class PermissionPerGroupBaselineServicePaneBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private PermissionPerGroupUGroupFormatter|\PHPUnit\Framework\MockObject\MockObject $formatter;
    private \ProjectUGroup $project_members;
    private \ProjectUGroup $project_admins;
    private \ProjectUGroup $developers;
    private \Project $project;
    private RoleAssignmentRepository $role_assignment_repository;
    private UGroupRetriever $ugroup_retriever;
    private PermissionPerGroupBaselineServicePaneBuilder $builder;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->project_members = ProjectUGroupTestBuilder::buildProjectMembers();
        $this->project_admins  = ProjectUGroupTestBuilder::buildProjectAdmins();
        $this->developers      = ProjectUGroupTestBuilder::aCustomUserGroup(102)
            ->withName('Developers')
            ->build();

        $this->formatter = $this->createMock(PermissionPerGroupUGroupFormatter::class);
        $this->formatter
            ->method('formatGroup')
            ->willReturnMap([
                [
                    $this->project_admins,
                    [
                        'name' => 'Project administrators',
                    ],
                ],
                [
                    $this->project_members,
                    [
                        'name' => 'Project members',
                    ],
                ],
                [
                    $this->developers,
                    [
                        'name' => 'Developers',
                    ],
                ],
            ]);

        $this->role_assignment_repository = new class implements RoleAssignmentRepository {
            public function findByProjectAndRole(ProjectIdentifier $project, Role $role): array
            {
                return match ($role->getName()) {
                    RoleBaselineAdmin::NAME => RoleAssignmentTestBuilder::aRoleAssignment($role)->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())->build(),
                    RoleBaselineReader::NAME => RoleAssignmentTestBuilder::aRoleAssignment($role)->withUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(102)->build())->build(),
                };
            }

            public function saveAssignmentsForProject(RoleAssignmentsUpdate $role_assignments_update): void
            {
            }
        };

        $this->ugroup_retriever = new class ($this->project_admins, $this->project_members, $this->developers) implements UGroupRetriever {
            public function __construct(
                private \ProjectUGroup $project_admins,
                private \ProjectUGroup $project_members,
                private \ProjectUGroup $developers,
            ) {
            }

            public function getUGroup(\Project $project, $ugroup_id): ?\ProjectUGroup
            {
                return match ($ugroup_id) {
                    $this->project_admins->getId() => $this->project_admins,
                    $this->project_members->getId() => $this->project_members,
                    $this->developers->getId() => $this->developers,
                    default => null,
                };
            }
        };

        $this->builder = new PermissionPerGroupBaselineServicePaneBuilder(
            $this->formatter,
            $this->role_assignment_repository,
            $this->ugroup_retriever
        );

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testBuildPresenterWhenUserFilterOnDevelopers(): void
    {
        $event = new PermissionPerGroupPaneCollector($this->project, $this->developers->getId());

        self::assertEquals(
            [
                [
                    'name'   => 'Baseline readers',
                    'groups' => [
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/plugins/baseline/TestProject/admin',
                ],
            ],
            $this->builder->buildPresenter($event)->permissions
        );
    }

    public function testBuildPresenterWhenUserFilterOnProjectMembers(): void
    {
        $event = new PermissionPerGroupPaneCollector($this->project, $this->project_members->getId());

        self::assertEquals(
            [
                [
                    'name'   => 'Baseline administrators',
                    'groups' => [
                        [
                            'name' => 'Project members',
                        ],
                    ],
                    'url'    => '/plugins/baseline/TestProject/admin',
                ],
            ],
            $this->builder->buildPresenter($event)->permissions
        );
    }

    public function testBuildPresenterWithoutFilterAlwaysIncludesProjectAdministrators(): void
    {
        $event = new PermissionPerGroupPaneCollector($this->project, false);

        self::assertEquals(
            [
                [
                    'name'   => 'Baseline administrators',
                    'groups' => [
                        [
                            'name' => 'Project administrators',
                        ],
                        [
                            'name' => 'Project members',
                        ],
                    ],
                    'url'    => '/plugins/baseline/TestProject/admin',
                ],
                [
                    'name'   => 'Baseline readers',
                    'groups' => [
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/plugins/baseline/TestProject/admin',
                ],
            ],
            $this->builder->buildPresenter($event)->permissions
        );
    }
}
