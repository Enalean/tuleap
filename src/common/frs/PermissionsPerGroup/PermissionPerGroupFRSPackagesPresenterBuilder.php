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

use FRSPackageFactory;
use FRSReleaseFactory;
use PFUser;
use Project;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupLoadAllButtonPresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
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

    public function getPanePresenter(Project $project, PFUser $user, $selected_ugroup_id)
    {
        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup_id);

        return new PermissionPerGroupLoadAllButtonPresenter(
            $user,
            $project,
            $ugroup
        );
    }
}
