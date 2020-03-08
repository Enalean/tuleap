<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\FRS\PackagePermissionManager;
use Tuleap\FRS\FRSPermissionManager;

require_once __DIR__ . '/../include/pre.php';

if (! user_isloggedin()) {
    exit_not_logged_in();
}
if (! isset($group_id)) {
    $group_id = 0;
}
$vFilemodule_id = new Valid_UInt('filemodule_id');
$vFilemodule_id->required();
if ($request->valid($vFilemodule_id)) {
    $filemodule_id      = $request->get('filemodule_id');
    $pm                 = ProjectManager::instance();
    $um                 = UserManager::instance();
    $userHelper         = new UserHelper();
    $current_user       = $um->getCurrentUser();
    $package_factory    = new FRSPackageFactory();
    $fmmf               = new FileModuleMonitorFactory();
    $permission_manager = FRSPermissionManager::build();

    $package_permission_manager = new PackagePermissionManager($permission_manager, $package_factory);
    $package                    = $package_factory->getFRSPackageFromDb($filemodule_id);

    if ($package_permission_manager->canUserSeePackage($current_user, $package, $request->getProject())) {
        $fmmf->processMonitoringActions($request, $current_user, $group_id, $filemodule_id, $um, $userHelper);

        file_utils_header(
            array(
                'title' => $GLOBALS['Language']->getText(
                    'file_showfiles',
                    'file_p_for',
                    $pm->getProject($group_id)->getPublicName()
                )
            )
        );
        echo $fmmf->getMonitoringHTML($current_user, $group_id, $filemodule_id, $um, $userHelper);
        file_utils_footer(array());
    } else {
        $GLOBALS['Response']->addFeedback(
            'error',
            $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_permission')
        );
        $GLOBALS['Response']->redirect('showfiles.php?group_id=' . $group_id);
    }
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'choose_p'));
    $GLOBALS['Response']->redirect('showfiles.php?group_id=' . $GLOBALS['Language']);
}
