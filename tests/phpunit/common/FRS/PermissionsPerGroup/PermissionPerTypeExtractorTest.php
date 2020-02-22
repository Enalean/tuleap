<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\FRS\PerGroup;

use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\PermissionsPerGroup\FRSPermissionPerGroupURLBuilder;
use Tuleap\FRS\PermissionsPerGroup\PermissionPerTypeExtractor;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupCollection;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use UGroupManager;

require_once __DIR__ . '/../../../bootstrap.php';

class PermissionPerTypeExtractorTest extends TestCase
{
    /**
     * @var FRSPermission
     */
    private $ugroup_project_member;
    /**
     * @var FRSPermission
     */
    private $ugroup_project_admin;
    /**
     * @var array
     */
    private $formatted_project_admin;
    /**
     * @var array
     */
    private $formatted_project_member;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var FRSPermissionPerGroupURLBuilder
     */
    private $url_builder;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var PermissionPerTypeExtractor
     */
    private $extractor;
    /**
     * @var FRSPermissionFactory
     */
    private $permission_factory;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    public function setUp() : void
    {
        parent::setUp();

        $this->permission_factory = $this->createMock(FRSPermissionFactory::class);
        $this->formatter          = $this->createMock(PermissionPerGroupUGroupFormatter::class);
        $this->url_builder        = $this->createMock(FRSPermissionPerGroupURLBuilder::class);
        $this->ugroup_manager     = $this->createMock(UGroupManager::class);

        $this->extractor = new PermissionPerTypeExtractor(
            $this->permission_factory,
            $this->formatter,
            $this->url_builder,
            $this->ugroup_manager
        );

        $this->formatted_project_member = array(
            'is_project_admin' => false,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project members"
        );

        $this->formatted_project_admin = array(
            'is_project_admin' => true,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project admin"
        );

        $this->ugroup_project_member = new ProjectUGroup(
            [
                "ugroup_id" => ProjectUGroup::PROJECT_MEMBERS,
                "name"      => "Project members"
            ]
        );

        $this->ugroup_project_admin = new ProjectUGroup(
            [
                "ugroup_id" => ProjectUGroup::PROJECT_ADMIN,
                "name"      => "Project admin"
            ]
        );

        $this->project = $this->createMock(Project::class);
    }

    public function testItAlwaysAddFRSAdminPermissionForFRSAdmin()
    {
        $this->permission_factory->method('getFrsUGroupsByPermission')->willReturn(
            array(
                ProjectUGroup::PROJECT_MEMBERS => new FRSPermission(
                    ProjectUGroup::PROJECT_MEMBERS
                )
            )
        );
        $this->ugroup_manager->method('getProjectAdminsUGroup')->willReturn($this->ugroup_project_admin);
        $this->ugroup_manager->method('getUGroup')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_MEMBERS]
        )->willReturnOnConsecutiveCalls(
            $this->ugroup_project_member
        );

        $this->formatter->method('formatGroup')->withConsecutive(
            [$this->ugroup_project_admin],
            [$this->ugroup_project_admin],
            [$this->ugroup_project_member]
        )->willReturnOnConsecutiveCalls(
            $this->formatted_project_admin,
            $this->formatted_project_admin,
            $this->formatted_project_member
        );

        $this->url_builder->method('getGlobalAdminLink')->willReturn(
            "/admin/?group_id=" . $this->project->getID() . "&action=edit-permissions"
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            'FRS administrators',
            null
        );

        $expected_packages =
            [
                [
                    "name"   => "FRS administrators",
                    "groups" => [$this->formatted_project_admin, $this->formatted_project_member],
                    "url"    => "/admin/?group_id=" . $this->project->getID() . "&action=edit-permissions"
                ]
            ];

        $this->assertEquals($expected_packages, $permissions->getPermissions());
    }

    public function testItDontAddProjectAdminPermissionForFRSReaders()
    {
        $this->permission_factory->method('getFrsUGroupsByPermission')->willReturn(
            array(
                ProjectUGroup::PROJECT_MEMBERS => new FRSPermission(
                    ProjectUGroup::PROJECT_MEMBERS
                )
            )
        );
        $this->ugroup_manager->method('getUGroup')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_MEMBERS]
        )->willReturnOnConsecutiveCalls(
            $this->ugroup_project_member
        );

        $this->formatter->method('formatGroup')->withConsecutive(
            [$this->ugroup_project_member]
        )->willReturnOnConsecutiveCalls(
            $this->formatted_project_member
        );

        $this->url_builder->method('getGlobalAdminLink')->willReturn(
            "/admin/?group_id=" . $this->project->getID() . "&action=edit-permissions"
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_READER,
            'FRS readers',
            null
        );

        $expected_packages =
            [
                [
                    "name"   => "FRS readers",
                    "groups" => [$this->formatted_project_member],
                    "url"    => "/admin/?group_id=" . $this->project->getID() . "&action=edit-permissions"
                ]
            ];

        $this->assertEquals($expected_packages, $permissions->getPermissions());
    }

    public function testItDontAddProjectAdminPermissionOnFilterWhenUGroupIsNotInServicePermissions()
    {
        $this->permission_factory->method('getFrsUGroupsByPermission')->willReturn(
            array(
                ProjectUGroup::PROJECT_MEMBERS => new FRSPermission(
                    ProjectUGroup::PROJECT_MEMBERS
                )
            )
        );
        $this->ugroup_manager->method('getUGroup')->willReturn(
            $this->ugroup_project_member
        );

        $this->url_builder->method('getGlobalAdminLink')->willReturn(
            "/admin/?group_id=" . $this->project->getID() . "&action=edit-permissions"
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            'FRS administrators',
            ProjectUGroup::REGISTERED
        );

        $expected_packages = [];

        $this->assertEquals($expected_packages, $permissions->getPermissions());
    }
}
