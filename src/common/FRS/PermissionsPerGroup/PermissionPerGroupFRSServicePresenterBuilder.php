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

namespace Tuleap\FRS\PermissionsPerGroup;

use Project;
use Tuleap\FRS\FRSPermission;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupCollection;
use UGroupManager;

class PermissionPerGroupFRSServicePresenterBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerTypeExtractor
     */
    private $extractor;

    public function __construct(
        PermissionPerTypeExtractor $extractor,
        UGroupManager $ugroup_manager
    ) {
        $this->ugroup_manager = $ugroup_manager;
        $this->extractor      = $extractor;
    }

    public function getPanePresenter(Project $project, $selected_ugroup)
    {
        $permissions = new PermissionPerGroupCollection();
        $this->extractor->extractPermissionByType(
            $project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            $GLOBALS['Language']->getText('file_file_utils', 'administrators_title'),
            $selected_ugroup
        );
        $this->extractor->extractPermissionByType(
            $project,
            $permissions,
            FRSPermission::FRS_READER,
            $GLOBALS['Language']->getText('file_file_utils', 'readers_title'),
            $selected_ugroup
        );

        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup);

        return new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $ugroup
        );
    }
}
