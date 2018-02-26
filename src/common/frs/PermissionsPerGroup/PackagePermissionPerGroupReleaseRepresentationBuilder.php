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
use FRSRelease;
use FRSReleaseFactory;
use Project;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;

class PackagePermissionPerGroupReleaseRepresentationBuilder
{
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;

    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_ugroup_retriever;

    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_representation_builder;

    public function __construct(
        FRSReleaseFactory $release_factory,
        PermissionPerGroupUGroupRetriever $permission_ugroup_retriever,
        PermissionPerGroupUGroupRepresentationBuilder $ugroup_representation_builder
    ) {
        $this->release_factory               = $release_factory;
        $this->permission_ugroup_retriever   = $permission_ugroup_retriever;
        $this->ugroup_representation_builder = $ugroup_representation_builder;
    }

    public function build(
        Project $project,
        FRSPackage $package,
        array $package_permission_ids,
        $selected_ugroup_id
    ) {
        $releases_permissions        = [];
        $all_release_permissions_ids = [];
        $releases                    = $this->release_factory->getFRSReleasesFromDb($package->getPackageID());

        foreach ($releases as $release) {
            $release_permission_ids = $this->permission_ugroup_retriever->getAllUGroupForObject(
                $project,
                $release->getReleaseID(),
                FRSRelease::PERM_READ
            );

            $release_permission_ids = $this->getPackagePermissionWhenReleaseDontHavePermission(
                $package_permission_ids,
                $release_permission_ids
            );

            if ($this->shouldReleaseBeAdded($release_permission_ids, $selected_ugroup_id)) {
                $releases_permissions[] = $this->getReleasePermissionsRepresentation(
                    $project,
                    $release,
                    $release_permission_ids
                );
            }

            $all_release_permissions_ids = array_merge($all_release_permissions_ids, $release_permission_ids);
        }

        return [$all_release_permissions_ids, $releases_permissions];
    }

    private function getPackagePermissionWhenReleaseDontHavePermission(
        array $formatted_package_permissions,
        array $formatted_release_permissions
    ) {
        if (count($formatted_release_permissions) === 0) {
            return $formatted_package_permissions;
        }

        return $formatted_release_permissions;
    }

    private function getReleasePermissionsRepresentation(
        Project $project,
        FRSRelease $release,
        array $release_permission
    ) {
        return new PackagePermissionPerGroupReleaseRepresentation(
            '/file/admin/release.php?' . http_build_query(
                array(
                    "func"       => "edit",
                    "group_id"   => $project->getID(),
                    "package_id" => $release->getPackageID(),
                    "id"         => $release->getReleaseID()
                )
            ),
            $release->getName(),
            $this->getUGroupsReleasePermissionsRepresentations(
                $project,
                $release_permission
            )
        );
    }

    private function getUGroupsReleasePermissionsRepresentations(
        Project $project,
        array $release_permission
    ) {
        $formatted_release_permissions = [];
        foreach ($release_permission as $permission) {
            $formatted_release_permissions[] = $this->ugroup_representation_builder->build(
                $project,
                $permission
            );
        }

        return $formatted_release_permissions;
    }

    private function shouldReleaseBeAdded(array $release_permission, $selected_ugroup_id)
    {
        if (! $selected_ugroup_id) {
            return true;
        }

        return in_array($selected_ugroup_id, $release_permission);
    }
}
