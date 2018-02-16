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

use FRSPackage;
use FRSRelease;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;
use TuleapTestCase;

class PermissionPerGroupFRSPackagesPresenterBuilderTest extends TuleapTestCase
{
    /**
     * @var []
     */
    private $release2;
    /**
     * @var []
     */
    private $release;
    /**
     * @var []
     */
    private $project_admin;
    /**
     * @var []
     */
    private $project_member;
    /**
     * @var []
     */
    private $package;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var PermissionPerGroupFRSPackagesPresenterBuilder
     */
    private $presenter_builder;

    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var \FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var \FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_ugroup_retriever;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;

    public function setUp()
    {
        parent::setUp();

        $this->ugroup_manager              = mock('UGroupManager');
        $this->permission_ugroup_retriever = mock('Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever');
        $this->package_factory             = mock('FRSPackageFactory');
        $this->release_factory             = mock('FRSReleaseFactory');
        $this->formatter                   = mock('Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter');

        $this->presenter_builder = new PermissionPerGroupFRSPackagesPresenterBuilder(
            $this->ugroup_manager,
            $this->permission_ugroup_retriever,
            $this->package_factory,
            $this->formatter,
            $this->release_factory
        );

        $this->project = aMockProject()->withId(101)->build();

        $this->package = [
            'package_id' => 1,
            'group_id'   => 101,
            'name'       => 'Package 1'
        ];

        $this->release  = [
            'release_id' => 1,
            'package_id' => 1,
            'name'       => 'Release 1'
        ];
        $this->release2 = [
            'release_id' => 2,
            'package_id' => 1,
            'name'       => 'Release 2'
        ];

        $this->project_member = [
            'is_project_admin' => false,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project members"
        ];

        $this->project_admin = [
            'is_project_admin' => true,
            'is_static'        => true,
            'is_custom'        => false,
            'name'             => "Project admin"
        ];
    }

    public function itAddEmptyPackages()
    {
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([new FRSPackage($this->package)]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject()->returns([ProjectUGroup::PROJECT_ADMIN]);
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([]);

        $expected_packages_permissions = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ]
        ];
        $releases                      = [];
        $presenter                     = $this->presenter_builder->getPanePresenter($this->project, null);
        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'], $releases);
    }

    public function itAddReleasePermissions()
    {
        $package = new FRSPackage($this->package);
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([$package]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $package->getPackageID(),
            FRSPackage::PERM_READ
        )->returns([ProjectUGroup::PROJECT_ADMIN]);

        $release  = new FRSRelease($this->release);
        $release2 = new FRSRelease($this->release2);
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([$release, $release2]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $release->getReleaseID(),
            FRSRelease::PERM_READ
        )->returns([ProjectUGroup::PROJECT_MEMBERS]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $release2->getReleaseID(),
            FRSRelease::PERM_READ
        )->returns([ProjectUGroup::PROJECT_MEMBERS]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );

        $expected_packages_permissions = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ]
        ];
        $release1_permissions          = [
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $release2_permissions          = [
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $presenter                     = $this->presenter_builder->getPanePresenter($this->project, null);
        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'][0]['release_permissions'], $release1_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'][1]['release_permissions'], $release2_permissions);
    }

    public function itAddPackagePermissionIfReleaseHasNoPermissions()
    {
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([new FRSPackage($this->package)]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject()->returns([ProjectUGroup::PROJECT_ADMIN]);
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([new FRSRelease()]);

        $expected_packages_permissions = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ]
        ];
        $releases                      = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ]
        ];
        $presenter                     = $this->presenter_builder->getPanePresenter($this->project, null);
        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'][0]['release_permissions'], $releases);
    }

    public function itFilterOnAGroupWeFindCorrespondingPackagesAndReleases()
    {
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([new FRSPackage($this->package)]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject()->returns(
            [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS]
        );
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([new FRSRelease()]);

        $expected_packages_permissions = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ],
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $releases                      = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ],
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $presenter                     = $this->presenter_builder->getPanePresenter(
            $this->project,
            ProjectUGroup::PROJECT_ADMIN
        );
        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'][0]['release_permissions'], $releases);
    }

    public function itFilterInReleasesEvenIfPackageHasNotThePermission()
    {
        $package = new FRSPackage($this->package);
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([$package]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $package->getPackageID(),
            FRSPackage::PERM_READ
        )->returns([]);


        $release = new FRSRelease();
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([$release]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $release->getReleaseID(),
            FRSRelease::PERM_READ
        )->returns([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );

        $expected_packages_permissions = [];
        $releases                      = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ],
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $presenter                     = $this->presenter_builder->getPanePresenter(
            $this->project,
            ProjectUGroup::PROJECT_ADMIN
        );
        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'][0]['release_permissions'], $releases);
    }

    public function itDoesNotDisplayPackageWhenPackageHasNotTheFilteredPermission()
    {
        $package = new FRSPackage($this->package);
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([$package]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject()->returns([]);


        $release = new FRSRelease();
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([$release]);

        $expected_packages_permissions = [];
        $presenter                     = $this->presenter_builder->getPanePresenter(
            $this->project,
            ProjectUGroup::PROJECT_ADMIN
        );
        $this->assertEqual($presenter->permissions, $expected_packages_permissions);
    }

    public function itDoesNotDisplayReleaseWhenPackageHasTheFilteredPermissionAndTheReleaseHasNotTheFilteredPermission()
    {
        $package = new FRSPackage($this->package);
        stub($this->package_factory)->getFRSPackagesFromDb()->returns([$package]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $package->getPackageID(),
            FRSPackage::PERM_READ
        )->returns([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS]);


        $release = new FRSRelease();
        stub($this->release_factory)->getFRSReleasesFromDb()->returns([$release]);
        stub($this->permission_ugroup_retriever)->getAllUGroupForObject(
            $this->project,
            $release->getReleaseID(),
            FRSRelease::PERM_READ
        )->returns([ProjectUGroup::PROJECT_MEMBERS]);
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns(
            $this->project_admin
        );
        stub($this->formatter)->formatGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns(
            $this->project_member
        );

        $expected_packages_permissions = [
            [
                'is_project_admin' => true,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project admin'
            ],
            [
                'is_project_admin' => false,
                'is_static'        => true,
                'is_custom'        => false,
                'name'             => 'Project members'
            ]
        ];
        $releases                      = [];
        $presenter                     = $this->presenter_builder->getPanePresenter(
            $this->project,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->assertEqual($presenter->permissions[0]['permissions'], $expected_packages_permissions);
        $this->assertEqual($presenter->permissions[0]['releases'], $releases);
    }
}
