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
use Project;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;

class PackagePermissionPerGroupRepresentationBuilder
{
    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_ugroup_retriever;
    /**
     * @var FRSPackageFactory
     */
    private $package_factory;

    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_representation_builder;

    /**
     * @var PackagePermissionPerGroupReleaseRepresentationBuilder
     */
    private $release_representation_builder;

    public function __construct(
        PermissionPerGroupUGroupRetriever $permission_ugroup_retriever,
        FRSPackageFactory $package_factory,
        PermissionPerGroupUGroupRepresentationBuilder $ugroup_representation_builder,
        PackagePermissionPerGroupReleaseRepresentationBuilder $release_representation_builder
    ) {
        $this->permission_ugroup_retriever    = $permission_ugroup_retriever;
        $this->package_factory                = $package_factory;
        $this->ugroup_representation_builder  = $ugroup_representation_builder;
        $this->release_representation_builder = $release_representation_builder;
    }

    /**
     * @param null $selected_ugroup_id
     * @return PackagePermissionPerGroupRepresentation[]
     */
    public function build(
        Project $project,
        $selected_ugroup_id = null
    ) {
        $permissions = [];

        $packages = $this->package_factory->getFRSPackagesFromDb($project->getID());
        foreach ($packages as $package) {
            $package_permission_ids = $this->permission_ugroup_retriever->getAllUGroupForObject(
                $project,
                $package->getPackageID(),
                FRSPackage::PERM_READ
            );

            $formatted_package_permissions = [];

            foreach ($package_permission_ids as $permission) {
                $formatted_package_permissions[] = $this->ugroup_representation_builder->build(
                    $project,
                    $permission
                );
            }

            list($release_permission_ids, $releases_permissions) = $this->release_representation_builder->build(
                $project,
                $package,
                $package_permission_ids,
                $selected_ugroup_id
            );

            $package_representations = $this->buildPackagePermissionRepresentation(
                $project,
                $package,
                $formatted_package_permissions,
                $releases_permissions
            );

            $this->addPermissionWhenNoFilterIsSelected($permissions, $selected_ugroup_id, $package_representations);
            $this->addPermissionWhenFilterIsDefinedAndPermissionMatchesPackageOrRelease(
                $permissions,
                $selected_ugroup_id,
                $package_permission_ids,
                $release_permission_ids,
                $package_representations
            );
        }

        return $permissions;
    }

    private function buildPackagePermissionRepresentation(
        Project $project,
        FRSPackage $package,
        array $formatted_package_permissions,
        array $releases_permissions
    ) {
        return new PackagePermissionPerGroupRepresentation(
            '/file/admin/package.php?' . http_build_query(
                array(
                    "func"     => "edit",
                    "group_id" => $project->getID(),
                    "id"       => $package->getPackageID()
                )
            ),
            $package->getName(),
            $formatted_package_permissions,
            $releases_permissions
        );
    }

    private function addPermissionWhenNoFilterIsSelected(
        array &$permissions,
        $selected_ugroup_id,
        PackagePermissionPerGroupRepresentation $package_permission_representation
    ) {
        if (! $selected_ugroup_id) {
            $permissions[] = $package_permission_representation;
        }
    }

    private function addPermissionWhenFilterIsDefinedAndPermissionMatchesPackageOrRelease(
        array &$permissions,
        $selected_ugroup_id,
        array $package_permissions_ids,
        array $release_permissions_ids,
        PackagePermissionPerGroupRepresentation $formatted_permission
    ) {
        $object_permissions = array_merge($package_permissions_ids, $release_permissions_ids);
        if ($selected_ugroup_id && in_array($selected_ugroup_id, $object_permissions)) {
            $permissions[] = $formatted_permission;
        }
    }
}
