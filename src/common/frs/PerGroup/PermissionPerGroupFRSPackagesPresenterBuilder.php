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
use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use Project;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupCollection;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;
use UGroupManager;

class PermissionPerGroupFRSPackagesPresenterBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_ugroup_retriever;
    /**
     * @var FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    public function __construct(
        UGroupManager $ugroup_manager,
        PermissionPerGroupUGroupRetriever $permission_ugroup_retriever,
        FRSPackageFactory $package_factory,
        PermissionPerGroupUGroupFormatter $formatter,
        FRSReleaseFactory $release_factory
    ) {
        $this->ugroup_manager              = $ugroup_manager;
        $this->permission_ugroup_retriever = $permission_ugroup_retriever;
        $this->package_factory             = $package_factory;
        $this->release_factory             = $release_factory;
        $this->formatter                   = $formatter;
    }

    public function getPanePresenter(Project $project, $selected_ugroup_id)
    {
        $permissions = new PermissionPerGroupCollection();

        $this->addPackagePermissions($project, $permissions, $selected_ugroup_id);

        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup_id);

        return new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $ugroup
        );
    }

    /**
     * @param Project $project
     * @param         $permissions
     */
    private function addPackagePermissions(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id = null
    ) {
        $packages = $this->package_factory->getFRSPackagesFromDb($project->getID());
        foreach ($packages as $package) {
            $package_permission_ids = $this->permission_ugroup_retriever->getAllUGroupForObject(
                $project,
                $package->getPackageID(),
                FRSPackage::PERM_READ
            );

            $formatted_package_permissions = [];
            foreach ($package_permission_ids as $permission) {
                $formatted_package_permissions[] = $this->formatter->formatGroup($project, $permission);
            }

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


                $releases_permissions[] = $this->getReleasePermissions(
                    $project,
                    $selected_ugroup_id,
                    $release,
                    $release_permission_ids
                );

                $all_release_permissions_ids = array_merge($all_release_permissions_ids, $release_permission_ids);
            }

            $releases_permissions = array_values(array_filter($releases_permissions));

            $formatted_permission = $this->formatPackagePermissions(
                $project,
                $package,
                $formatted_package_permissions,
                $releases_permissions
            );

            $this->addPermissionWhenNoFilterIsSelected($permissions, $selected_ugroup_id, $formatted_permission);
            $this->addPermissionWhenFilterIsDefinedAndPermissionMatchesPackageOrRelease(
                $permissions,
                $selected_ugroup_id,
                $package_permission_ids,
                $all_release_permissions_ids,
                $formatted_permission
            );
        }
    }

    private function formatReleasePermissionsForPresenter(
        Project $project,
        FRSRelease $release,
        array $formatted_release_permissions
    ) {
        $releases_permissions = array(
            'release_url'         => '/file/admin/release.php?' . http_build_query(
                array(
                    "func"       => "edit",
                    "group_id"   => $project->getID(),
                    "package_id" => $release->getPackageID(),
                    "id"         => $release->getReleaseID()
                )
            ),
            'release_name'        => $release->getName(),
            'release_permissions' => $formatted_release_permissions
        );

        return $releases_permissions;
    }

    private function formatPackagePermissions(
        Project $project,
        FRSPackage $package,
        array $formatted_package_permissions,
        array $releases_permissions
    ) {
        $formatted_permission = array(
            'package_url'  => '/file/admin/package.php?' . http_build_query(
                array(
                    "func"     => "edit",
                    "group_id" => $project->getID(),
                    "id"       => $package->getPackageID()
                )
            ),
            'package_name' => $package->getName(),
            'permissions'  => $formatted_package_permissions,
            'releases'     => $releases_permissions
        );

        return $formatted_permission;
    }

    private function addPermissionWhenNoFilterIsSelected(
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id,
        array $formatted_permission
    ) {
        if (! $selected_ugroup_id) {
            $permissions->addPermissions($formatted_permission);
        }
    }

    private function addPermissionWhenFilterIsDefinedAndPermissionMatchesPackageOrRelease(
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id,
        array $package_permissions,
        array $release_permissions,
        array $formatted_permission
    ) {
        $object_permissions = array_merge($package_permissions, $release_permissions);
        if ($selected_ugroup_id && in_array($selected_ugroup_id, $object_permissions)) {
            $permissions->addPermissions($formatted_permission);
        }
    }

    private function getFormattedUGroupsReleasePermissions(
        Project $project,
        $release_permission
    ) {
        $formatted_release_permissions = [];
        foreach ($release_permission as $permission) {
            $formatted_release_permissions[] = $this->formatter->formatGroup($project, $permission);
        }

        return $formatted_release_permissions;
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

    private function getReleasePermissions(
        Project $project,
        $selected_ugroup_id,
        FRSRelease $release,
        array $release_permission
    ) {
        if (! $selected_ugroup_id) {
            return $this->formatReleasePermissionsForPresenter(
                $project,
                $release,
                $this->getFormattedUGroupsReleasePermissions(
                    $project,
                    $release_permission
                )
            );
        }

        if ($selected_ugroup_id && in_array($selected_ugroup_id, $release_permission)) {
            return $this->formatReleasePermissionsForPresenter(
                $project,
                $release,
                $this->getFormattedUGroupsReleasePermissions(
                    $project,
                    $release_permission
                )
            );
        }

        return [];
    }
}
