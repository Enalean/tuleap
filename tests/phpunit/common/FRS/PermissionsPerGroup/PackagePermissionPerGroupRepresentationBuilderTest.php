<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\FRS\PermissionsPerGroup;

use FRSPackage;
use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class PackagePermissionPerGroupRepresentationBuilderTest extends TestCase
{
    /**
     * @var []
     */
    private $package;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var PackagePermissionPerGroupRepresentationBuilder
     */
    private $representation_builder;

    /**
     * @var \FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_ugroup_retriever;
    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_representation_builder;

    /**
     * @var PackagePermissionPerGroupReleaseRepresentationBuilder
     */
    private $release_representation_builder;
    /**
     * @var PermissionPerGroupUGroupRepresentation
     */
    private $project_member_representation;
    /**
     * @var PermissionPerGroupUGroupRepresentation
     */
    private $project_admin_representation;
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;

    private $package_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->permission_ugroup_retriever    = $this->createMock(PermissionPerGroupUGroupRetriever::class);
        $this->package_factory                = $this->createMock(FRSPackageFactory::class);
        $this->ugroup_representation_builder  = $this->createMock(PermissionPerGroupUGroupRepresentationBuilder::class);
        $this->release_factory                = $this->createMock(FRSReleaseFactory::class);
        $this->release_representation_builder = new PackagePermissionPerGroupReleaseRepresentationBuilder(
            $this->release_factory,
            $this->permission_ugroup_retriever,
            $this->ugroup_representation_builder
        );

        $this->representation_builder = new PackagePermissionPerGroupRepresentationBuilder(
            $this->permission_ugroup_retriever,
            $this->package_factory,
            $this->ugroup_representation_builder,
            $this->release_representation_builder
        );

        $this->project = $this->createMock(Project::class);
        $this->project->method('getId')->willReturn(101);

        $this->project_member_representation = new PermissionPerGroupUGroupRepresentation(
            "Project members",
            false,
            true,
            false
        );

        $this->project_admin_representation = new PermissionPerGroupUGroupRepresentation(
            "Project admin",
            true,
            true,
            false
        );

        $this->package_id = 1;
        $this->package = new FRSPackage(
            [
                'package_id' => $this->package_id,
                'group_id'   => 101,
                'name'       => 'Package 1'
            ]
        );
    }

    public function testItAddEmptyPackages()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);
        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->willReturn([ProjectUGroup::PROJECT_ADMIN]);
        $this->ugroup_representation_builder->method('build')->with($this->project, ProjectUGroup::PROJECT_ADMIN)->willReturn(
            $this->project_admin_representation
        );

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([]);

        $expected_packages = [
            new PackagePermissionPerGroupRepresentation(
                '/file/admin/package.php?func=edit&group_id=101&id=1',
                'Package 1',
                [$this->project_admin_representation],
                []
            )
        ];

        $representation = $this->representation_builder->build($this->project, null);
        $this->assertEquals($expected_packages, $representation);
    }

    public function testItAddReleasesPermissions()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);

        $this->ugroup_representation_builder->method('build')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN],
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN],
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_MEMBERS]
        )->willReturnOnConsecutiveCalls(
            $this->project_admin_representation,
            $this->project_admin_representation,
            $this->project_member_representation
        );

        $release1_id = 1;
        $release2_id = 2;

        $release1 = new FRSRelease(
            [
                'release_id' => $release1_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 1'
            ]
        );
        $release2 = new FRSRelease(
            [
                'release_id' => $release2_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 2'
            ]
        );

        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->withConsecutive(
            [$this->equalTo($this->project), $this->equalTo($this->package_id), FRSPackage::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release1_id), FRSRelease::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release2_id), FRSRelease::PERM_READ]
        )->willReturnOnConsecutiveCalls(
            [ProjectUGroup::PROJECT_ADMIN],
            [ProjectUGroup::PROJECT_ADMIN],
            [ProjectUGroup::PROJECT_MEMBERS]
        );

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([$release1, $release2]);

        $release_representation_1 = new PackagePermissionPerGroupReleaseRepresentation(
            '/file/admin/release.php?func=edit&group_id=101&package_id=1&id=1',
            'Release 1',
            [$this->project_admin_representation]
        );
        $release_representation_2 = new PackagePermissionPerGroupReleaseRepresentation(
            '/file/admin/release.php?func=edit&group_id=101&package_id=1&id=2',
            'Release 2',
            [$this->project_member_representation]
        );
        $expected_packages = [
            new PackagePermissionPerGroupRepresentation(
                '/file/admin/package.php?func=edit&group_id=101&id=1',
                'Package 1',
                [$this->project_admin_representation],
                [$release_representation_1, $release_representation_2]
            )
        ];

        $representation = $this->representation_builder->build($this->project, null);
        $this->assertEquals($expected_packages, $representation);
    }

    public function testItFilterOnAGroupWeFindCorrespondingPackagesAndReleases()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);

        $this->ugroup_representation_builder->method('build')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN],
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN]
        )->willReturnOnConsecutiveCalls(
            $this->project_admin_representation,
            $this->project_admin_representation
        );

        $release1_id = 1;
        $release2_id = 2;

        $release1 = new FRSRelease(
            [
                'release_id' => $release1_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 1'
            ]
        );
        $release2 = new FRSRelease(
            [
                'release_id' => $release2_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 2'
            ]
        );

        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->withConsecutive(
            [$this->equalTo($this->project), $this->equalTo($this->package_id), FRSPackage::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release1_id), FRSRelease::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release2_id), FRSRelease::PERM_READ]
        )->willReturnOnConsecutiveCalls(
            [ProjectUGroup::PROJECT_ADMIN],
            [ProjectUGroup::PROJECT_ADMIN],
            [ProjectUGroup::PROJECT_MEMBERS]
        );

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([$release1, $release2]);

        $release_representation_1 = new PackagePermissionPerGroupReleaseRepresentation(
            '/file/admin/release.php?func=edit&group_id=101&package_id=1&id=1',
            'Release 1',
            [$this->project_admin_representation]
        );

        $expected_packages = [
            new PackagePermissionPerGroupRepresentation(
                '/file/admin/package.php?func=edit&group_id=101&id=1',
                'Package 1',
                [$this->project_admin_representation],
                [$release_representation_1]
            )
        ];

        $representation = $this->representation_builder->build($this->project, ProjectUGroup::PROJECT_ADMIN);
        $this->assertEquals($expected_packages, $representation);
    }

    public function testItFilterInReleasesEvenIfPackageHasNotThePermission()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);

        $this->ugroup_representation_builder->method('build')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_MEMBERS],
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN]
        )->willReturnOnConsecutiveCalls(
            $this->project_member_representation,
            $this->project_admin_representation
        );

        $release_id = 1;

        $release = new FRSRelease(
            [
                'release_id' => $release_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 1'
            ]
        );

        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->withConsecutive(
            [$this->equalTo($this->project), $this->equalTo($this->package_id), FRSPackage::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release_id), FRSRelease::PERM_READ]
        )->willReturnOnConsecutiveCalls([ProjectUGroup::PROJECT_MEMBERS], [ProjectUGroup::PROJECT_ADMIN]);

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([$release]);

        $release_representation_1 = new PackagePermissionPerGroupReleaseRepresentation(
            '/file/admin/release.php?func=edit&group_id=101&package_id=1&id=1',
            'Release 1',
            [$this->project_admin_representation]
        );

        $expected_packages = [
            new PackagePermissionPerGroupRepresentation(
                '/file/admin/package.php?func=edit&group_id=101&id=1',
                'Package 1',
                [$this->project_member_representation],
                [$release_representation_1]
            )
        ];

        $representation = $this->representation_builder->build($this->project, ProjectUGroup::PROJECT_ADMIN);
        $this->assertEquals($expected_packages, $representation);
    }

    public function testItDoesNotDisplayPackageWhenPackageHasNotTheFilteredPermission()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);

        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->withConsecutive(
            [$this->equalTo($this->project), $this->equalTo($this->package_id), FRSPackage::PERM_READ]
        )->willReturnOnConsecutiveCalls([ProjectUGroup::PROJECT_MEMBERS]);

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([]);

        $expected_packages = [];

        $representation = $this->representation_builder->build($this->project, ProjectUGroup::NEWS_WRITER);
        $this->assertEquals($expected_packages, $representation);
    }

    public function testItDoesNotDisplayReleaseWhenPackageHasTheFilteredPermissionAndTheReleaseHasNotTheFilteredPermission()
    {
        $this->package_factory->method('getFRSPackagesFromDb')->willReturn([$this->package]);

        $this->ugroup_representation_builder->method('build')->withConsecutive(
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_MEMBERS],
            [$this->equalTo($this->project), (int) ProjectUGroup::PROJECT_ADMIN]
        )->willReturnOnConsecutiveCalls(
            $this->project_member_representation,
            $this->project_admin_representation
        );

        $release_id = 1;

        $release = new FRSRelease(
            [
                'release_id' => $release_id,
                'package_id' => $this->package_id,
                'name'       => 'Release 1'
            ]
        );

        $this->permission_ugroup_retriever->method('getAllUGroupForObject')->withConsecutive(
            [$this->equalTo($this->project), $this->equalTo($this->package_id), FRSPackage::PERM_READ],
            [$this->equalTo($this->project), $this->equalTo($release_id), FRSRelease::PERM_READ]
        )->willReturnOnConsecutiveCalls([ProjectUGroup::PROJECT_MEMBERS], [ProjectUGroup::PROJECT_ADMIN]);

        $this->release_factory->method('getFRSReleasesFromDb')->willReturn([$release]);

        $expected_packages = [];

        $representation = $this->representation_builder->build($this->project, ProjectUGroup::NEWS_WRITER);
        $this->assertEquals($expected_packages, $representation);
    }
}
