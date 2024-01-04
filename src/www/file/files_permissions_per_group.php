<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

use Tuleap\FRS\PermissionsPerGroup\PackagePermissionPerGroupJSONRepresentationRetriever;
use Tuleap\FRS\PermissionsPerGroup\PackagePermissionPerGroupReleaseRepresentationBuilder;
use Tuleap\FRS\PermissionsPerGroup\PackagePermissionPerGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;

require_once __DIR__ . '/../include/pre.php';

if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
    $GLOBALS['Response']->send400JSONErrors(
        [
            'error' => [
                'message' => _(
                    "You don't have permissions to see user groups."
                ),
            ],
        ]
    );
}

$ugroup_manager                           = new UGroupManager();
$permission_ugroup_representation_builder = new PermissionPerGroupUGroupRepresentationBuilder(
    $ugroup_manager
);

$permission_ugroup_retriever = new PermissionPerGroupUGroupRetriever(PermissionsManager::instance());
$permissions_retriever       = new PackagePermissionPerGroupJSONRepresentationRetriever(
    new PackagePermissionPerGroupRepresentationBuilder(
        $permission_ugroup_retriever,
        new FRSPackageFactory(),
        $permission_ugroup_representation_builder,
        new PackagePermissionPerGroupReleaseRepresentationBuilder(
            FRSReleaseFactory::instance(),
            $permission_ugroup_retriever,
            $permission_ugroup_representation_builder
        )
    )
);

$request = HTTPRequest::instance();

$permissions_retriever->retrieve(
    $request->getProject(),
    $request->get('selected_ugroup_id')
);
