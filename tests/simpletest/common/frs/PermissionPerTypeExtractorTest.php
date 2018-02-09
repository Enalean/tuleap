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

use ProjectUGroup;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupCollection;
use TuleapTestCase;

class PermissionPerTypeExtractorTest extends TuleapTestCase
{
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var PermissionPerTypeExtractor
     */
    private $extractor;
    /**
     * @var array
     */
    private $project_admin;
    /**
     * @var array
     */
    private $project_member;
    /**
     * @var FRSPermissionFactory
     */
    private $permission_factory;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    public function setUp()
    {
        parent::setUp();

        $this->permission_factory = mock('Tuleap\FRS\FRSPermissionFactory');
        $this->formatter          = mock('Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter');

        $this->extractor = new PermissionPerTypeExtractor(
            $this->permission_factory,
            $this->formatter
        );

        $this->project_member = array(
            'is_project_admin' => false,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project members"
        );

        $this->project_admin = array(
            'is_project_admin' => true,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project admin"
        );

        $this->project = aMockProject()->build();
    }

    public function itAlwaysAddFRSAdminPermissionForFRSAdmin()
    {
        stub($this->permission_factory)->getFrsUGroupsByPermission()->returns(
            array(
                ProjectUGroup::PROJECT_MEMBERS => new FRSPermission(
                    101,
                    FRSPermission::FRS_ADMIN,
                    ProjectUGroup::PROJECT_MEMBERS
                )
            )
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            'FRS administrators',
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->assertEqual(
            array(
                array(
                    "name"   => "FRS administrators",
                    "groups" => array($this->project_admin, $this->project_member)
                )
            ),
            $permissions->getPermissions()
        );
    }

    public function itDontAddProjectAdminPermissionForFRSReaders()
    {
        stub($this->permission_factory)->getFrsUGroupsByPermission()->returns(
            array(
                ProjectUGroup::PROJECT_MEMBERS => new FRSPermission(
                    101,
                    FRSPermission::FRS_ADMIN,
                    ProjectUGroup::PROJECT_MEMBERS
                )
            )
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_READER,
            'File readers',
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->assertEqual(
            array(
                array(
                    "name"   => "File readers",
                    "groups" => array($this->project_member)
                )
            ),
            $permissions->getPermissions()
        );
    }

    public function itDontAddProjectAdminPermissionOnFilterWhenUGroupIsNotInServicePermissions()
    {
        stub($this->permission_factory)->getFrsUGroupsByPermission()->returns(array());

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            'FRS administrators',
            ProjectUGroup::FORUM_ADMIN
        );

        $this->assertEqual(
            array(),
            $permissions->getPermissions()
        );
    }

    public function itAddProjectAdminPermissionOnFilterWhenSelectedUGroupIsProjectAdmin()
    {
        stub($this->permission_factory)->getFrsUGroupsByPermission()->returns(array());

        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );

        $permissions = new PermissionPerGroupCollection();

        $this->extractor->extractPermissionByType(
            $this->project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            'FRS administrators',
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->assertEqual(
            array(
                array(
                    "name"   => "FRS administrators",
                    "groups" => array($this->project_admin)
                )
            ),
            $permissions->getPermissions()
        );
    }
}
