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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\GlobalLanguageMock;
use Tuleap\MediawikiStandalone\Permissions\ISearchByProjectStub;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionPerGroupServicePaneBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private PermissionPerGroupUGroupFormatter|\PHPUnit\Framework\MockObject\MockObject $formatter;
    private \ProjectUGroup $project_members;
    private \ProjectUGroup $project_admins;
    private \ProjectUGroup $developers;
    private \Project $project;
    private UGroupRetriever $ugroup_retriever;
    private PermissionPerGroupServicePaneBuilder $builder;

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

        $dao = ISearchByProjectStub::buildWithPermissions(
            [$this->project_members->getId(), $this->developers->getId()],
            [$this->project_admins->getId(), $this->developers->getId()],
            [$this->project_admins->getId(), $this->developers->getId()],
        );

        $this->ugroup_retriever = UGroupRetrieverStub::buildWithUserGroups(
            $this->project_admins,
            $this->project_members,
            $this->developers
        );

        $this->builder = new PermissionPerGroupServicePaneBuilder(
            $this->formatter,
            new ProjectPermissionsRetriever($dao),
            $this->ugroup_retriever,
        );

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testBuildPresenterWhenUserFilterOnDevelopers(): void
    {
        $event = new PermissionPerGroupPaneCollector($this->project, $this->developers->getId());

        self::assertEquals(
            [
                [
                    'name'   => 'MediaWiki administrators',
                    'groups' => [
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
                ],
                [
                    'name'   => 'MediaWiki writers',
                    'groups' => [
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
                ],
                [
                    'name'   => 'MediaWiki readers',
                    'groups' => [
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
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
                    'name'   => 'MediaWiki readers',
                    'groups' => [
                        [
                            'name' => 'Project members',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
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
                    'name'   => 'MediaWiki administrators',
                    'groups' => [
                        [
                            'name' => 'Project administrators',
                        ],
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
                ],
                [
                    'name'   => 'MediaWiki writers',
                    'groups' => [
                        [
                            'name' => 'Project administrators',
                        ],
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
                ],
                [
                    'name'   => 'MediaWiki readers',
                    'groups' => [
                        [
                            'name' => 'Project members',
                        ],
                        [
                            'name' => 'Developers',
                        ],
                    ],
                    'url'    => '/mediawiki_standalone/admin/TestProject/permissions',
                ],
            ],
            $this->builder->buildPresenter($event)->permissions
        );
    }
}
