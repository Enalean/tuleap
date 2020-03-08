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
use Tuleap\FRS\FRSReleaseController;
use Tuleap\FRS\FRSValidator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\JSONHeader;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../../project/admin/permissions.php';

$vAction = new Valid_WhiteList('action', array('permissions_frs_package','permissions_frs_release','validator_frs_create','validator_frs_update','refresh_file_list'));
if ($request->valid($vAction)) {
    $action = $request->get('action');
} else {
    exit_error('', '');
}

if ($action == 'permissions_frs_package') {
    $vPackageId = new Valid_UInt('package_id');
    $vPackageId->required();
    $vGroupId = new Valid_GroupId();
    $vGroupId->required();
    if ($request->valid($vPackageId) && $request->valid($vGroupId)) {
        $package_id = $request->get('package_id');
        $group_id   = $request->get('group_id');
        $project    = ProjectManager::instance()->getProject($group_id);
        $package_controller = new FRSPackageController(
            FRSPackageFactory::instance(),
            FRSReleaseFactory::instance(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            PermissionsManager::instance(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
        );

        $package_controller->displayUserGroups($project, FRSPackage::PERM_READ);
    }
} else {
    if ($action == 'permissions_frs_release') {
           $vReleaseId = new Valid_UInt('release_id');
        $vReleaseId->required();
        $vGroupId = new Valid_GroupId();
        $vGroupId->required();
        if ($request->valid($vReleaseId) && $request->valid($vGroupId)) {
            $group_id   = $request->get('group_id');
            $release_id = $request->get('release_id');
            $project    = ProjectManager::instance()->getProject($group_id);
            $release_controller = new FRSReleaseController(
                FRSReleaseFactory::instance(),
                new User_ForgeUserGroupFactory(new UserGroupDao())
            );

            $release_controller->displayUserGroups($project, FRSRelease::PERM_READ);
        }
    } else {
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        if ($action == 'validator_frs_create') {
            $vName = new Valid_String('name');
            $vDate = new Valid_String('date');
            $vDate->required();
            $vPackageId = new Valid_UInt('package_id');
            $vPackageId->required();
            $vGroupId = new Valid_GroupId();
            $vGroupId->required();
            if ($request->valid($vName) &&
                $request->valid($vDate) &&
                $request->valid($vGroupId) &&
                $request->valid($vPackageId)) {
                $name = $request->get('name');
                $package_id = $request->get('package_id');
                $date       = $request->get('date');
                $group_id   = $request->get('group_id');
                $validator = new FRSValidator();
                $release = array (
                    'name' => $name,
                    'package_id' => $package_id,
                    'date' => $date
                );
                if ($validator->isValidForCreation($release, $group_id)) {
                    //frs valid
                    $header = array('valid' => true);
                } else {
                    //frs non valid
                    $errors = $validator->getErrors();
                    $feedback = new Feedback();
                    $feedback->log('error', $errors[0]);
                    $header = array('valid' => false, 'msg' => $feedback->fetch());
                }
                header(JSONHeader::getHeaderForPrototypeJS($header));
            }
        } else {
            if ($action == 'validator_frs_update') {
                $vName = new Valid_String('name');
                $vDate = new Valid_String('date');
                $vDate->required();
                $vPackageId = new Valid_UInt('package_id');
                $vPackageId->required();
                $vReleaseId = new Valid_UInt('release_id');
                $vReleaseId->required();
                $vGroupId = new Valid_GroupId();
                $vGroupId->required();
                if ($request->valid($vName) &&
                    $request->valid($vDate) &&
                    $request->valid($vGroupId) &&
                    $request->valid($vPackageId) &&
                    $request->valid($vReleaseId)) {
                    $name       = $request->get('name');
                    $package_id = $request->get('package_id');
                    $date       = $request->get('date');
                    $group_id   = $request->get('group_id');
                    $release_id = $request->get('release_id');
                    $validator = new FRSValidator();
                    $release = array (
                        'name' => $name,
                        'release_id' => $release_id,
                        'package_id' => $package_id,
                        'date' => $date
                    );
                    if ($validator->isValidForUpdate($release, $group_id)) {
                        //frs valid
                        $header = array('valid' => true);
                    } else {
                        //frs non valid
                        $errors = $validator->getErrors();
                        $feedback = new Feedback();
                        $feedback->log('error', $errors[0]);
                        $header = array('valid' => false, 'msg' => $feedback->fetch());
                    }
                    header(JSONHeader::getHeaderForPrototypeJS($header));
                }
            } else {
                if ($action == 'refresh_file_list') {
                    $project = $request->getProject();
                    $frsff = new FRSFileFactory();
                    $file_list = $frsff->getUploadedFileNames($project);
                    $available_ftp_files = implode(",", $file_list);
                    $purifier = Codendi_HTMLPurifier::instance();
                    $available_ftp_files = $purifier->purify($available_ftp_files, CODENDI_PURIFIER_JS_DQUOTE);
                    echo '{"valid":true, "msg":"' . $available_ftp_files . '"}';
                }
            }
        }
    }
}
