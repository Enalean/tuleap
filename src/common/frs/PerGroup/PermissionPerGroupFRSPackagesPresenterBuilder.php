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
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    public function __construct(
        UGroupManager $ugroup_manager,
        PermissionPerGroupUGroupRetriever $permission_ugroup_retriever,
        FRSPackageFactory $package_factory,
        PermissionPerGroupUGroupFormatter $formatter
    ) {
        $this->ugroup_manager              = $ugroup_manager;
        $this->permission_ugroup_retriever = $permission_ugroup_retriever;
        $this->package_factory             = $package_factory;
        $this->formatter                   = $formatter;
    }

    public function getPanePresenter(Project $project, $selected_ugroup)
    {
        $permissions = new PermissionPerGroupCollection();

        $this->addPackagePermissions($project, $permissions, $selected_ugroup);

        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup);

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
        $selected_ugroup = null
    ) {
        $packages = $this->package_factory->getFRSPackagesFromDb($project->getID());
        foreach ($packages as $package) {
            $package_permissions = $this->permission_ugroup_retriever->getAllUGroupForObject(
                $project,
                $package->getPackageID(),
                FRSPackage::PERM_READ
            );

            $this->addPackagePermissionForSelectedGroup(
                $project,
                $permissions,
                $package,
                $package_permissions,
                $selected_ugroup
            );
            $this->addPackagePermissionsWhenNoGroupIsSelected(
                $project,
                $permissions,
                $package,
                $package_permissions,
                $selected_ugroup
            );
        }
    }

    /**
     * @param Project                      $project
     * @param PermissionPerGroupCollection $permissions
     * @param FRSPackage                   $package
     * @param Int[]                        $package_permissions
     * @param                              $selected_ugroup
     */
    private function addPackagePermissionForSelectedGroup(
        Project $project,
        PermissionPerGroupCollection $permissions,
        FRSPackage $package,
        array $package_permissions,
        $selected_ugroup
    ) {
        if (! $selected_ugroup) {
            return;
        }

        if (in_array($selected_ugroup, $package_permissions)) {
            $this->formatPermission($project, $permissions, $package, $package_permissions);
        }
    }

    /**
     * @param Project                      $project
     * @param PermissionPerGroupCollection $permissions
     * @param FRSPackage                   $package
     * @param array                        $package_permissions
     */
    private function formatPermission(
        Project $project,
        PermissionPerGroupCollection $permissions,
        FRSPackage $package,
        array $package_permissions
    ) {
        $formatted_permissions = array();

        foreach ($package_permissions as $permission) {
            $formatted_permissions[] = $this->formatter->formatGroup($project, $permission);
        }

        $permissions->addPermissions(
            array(
                'package_url'  => '/file/admin/package.php?' . http_build_query(
                    array(
                        "func"     => "edit",
                        "group_id" => $project->getID(),
                        "id"       => $package->getPackageID()
                    )
                ),
                'package_name' => $package->getName(),
                'permissions'  => $formatted_permissions
            )
        );
    }

    /**
     * @param Project                      $project
     * @param PermissionPerGroupCollection $permissions
     * @param FRSPackage                   $package
     * @param Int[]                        $package_permissions
     * @param                              $selected_ugroup
     */
    private function addPackagePermissionsWhenNoGroupIsSelected(
        Project $project,
        PermissionPerGroupCollection $permissions,
        FRSPackage $package,
        array $package_permissions,
        $selected_ugroup
    ) {
        if ($selected_ugroup) {
            return;
        }

        $this->formatPermission($project, $permissions, $package, $package_permissions);
    }
}
