<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\FRS\FRSPackageController;
use Tuleap\FRS\FRSValidator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../../project/admin/permissions.php';

$vAction = new Valid_WhiteList('action', ['permissions_frs_package', 'permissions_frs_release', 'validator_frs_create', 'validator_frs_update']);
if ($request->valid($vAction)) {
    $action = $request->get('action');
} else {
    exit_error('', '');
}

if ($action === 'permissions_frs_package') {
    $vPackageId = new Valid_UInt('package_id');
    $vPackageId->required();
    $vGroupId = new Valid_GroupId();
    $vGroupId->required();
    if ($request->valid($vPackageId) && $request->valid($vGroupId)) {
        $package_id         = $request->get('package_id');
        $group_id           = $request->get('group_id');
        $project            = ProjectManager::instance()->getProject($group_id);
        $package_controller = new FRSPackageController(
            FRSPackageFactory::instance(),
            FRSReleaseFactory::instance(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            PermissionsManager::instance(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
        );

        $package_controller->displayUserGroups($project, FRSPackage::PERM_READ, $package_id);
    }
} else {
    header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    if ($action === 'validator_frs_create') {
        $vName = new Valid_String('name');
        $vDate = new Valid_String('date');
        $vDate->required();
        $vPackageId = new Valid_UInt('package_id');
        $vPackageId->required();
        $vGroupId = new Valid_GroupId();
        $vGroupId->required();
        if (
            $request->valid($vName) &&
            $request->valid($vDate) &&
            $request->valid($vGroupId) &&
            $request->valid($vPackageId)
        ) {
            $name       = $request->get('name');
            $package_id = $request->get('package_id');
            $date       = $request->get('date');
            $group_id   = $request->get('group_id');
            $validator  = new FRSValidator();
            $release    = [
                'name' => $name,
                'package_id' => $package_id,
                'date' => $date,
            ];
            if (! $validator->isValidForCreation($release, $group_id)) {
                $errors = $validator->getErrors();
                $GLOBALS['Response']->send400JSONErrors($errors[0]);
            }
        }
    } elseif ($action === 'validator_frs_update') {
        $vName = new Valid_String('name');
        $vDate = new Valid_String('date');
        $vDate->required();
        $vPackageId = new Valid_UInt('package_id');
        $vPackageId->required();
        $vReleaseId = new Valid_UInt('release_id');
        $vReleaseId->required();
        $vGroupId = new Valid_GroupId();
        $vGroupId->required();
        if (
            $request->valid($vName) &&
            $request->valid($vDate) &&
            $request->valid($vGroupId) &&
            $request->valid($vPackageId) &&
            $request->valid($vReleaseId)
        ) {
            $name       = $request->get('name');
            $package_id = $request->get('package_id');
            $date       = $request->get('date');
            $group_id   = $request->get('group_id');
            $release_id = $request->get('release_id');
            $validator  = new FRSValidator();
            $release    = [
                'name' => $name,
                'release_id' => $release_id,
                'package_id' => $package_id,
                'date' => $date,
            ];
            if (! $validator->isValidForUpdate($release, $group_id)) {
                $errors = $validator->getErrors();
                $GLOBALS['Response']->send400JSONErrors($errors[0]);
            }
        }
    }
}
