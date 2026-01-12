<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/file_utils.php';

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\ListPackagesPresenter;
use Tuleap\FRS\PackagePermissionManager;
use Tuleap\FRS\PackagePresenter;
use Tuleap\FRS\ReleasePermissionManager;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

$authorized_user = false;

$request  = \Tuleap\HTTPRequest::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$permission_manager = FRSPermissionManager::build();

$user_manager    = UserManager::instance();
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);
$user            = $user_manager->getCurrentUser();
if ($permission_manager->isAdmin($project, $user) || $permission_manager->userCanRead($project, $user)) {
    $authorized_user = true;
}

$service = $project->getService(Service::FILE);

if (! $service) {
    exit_error(
        $GLOBALS['Language']->getText(
            'project_service',
            'service_not_used',
            $GLOBALS['Language']->getText('project_admin_editservice', 'service_file_lbl_key')
        )
    );
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

$packages          = [];
$show_release_id   = $request->getValidated('show_release_id', 'uint', false);
$release_id        = $request->getValidated('release_id', 'uint', false);
$legacy_parameters = [$show_release_id, $release_id];
foreach ($legacy_parameters as $id) {
    if ($id === false) {
        continue;
    }

    $GLOBALS['HTML']->redirect('/file/shownotes.php?release_id=' . urlencode((string) $id));
}

$pv = false;
if ($request->valid(new Valid_Pv())) {
    $pv = $request->get('pv');
}

$fmmf                       = new FileModuleMonitorFactory();
$release_permission_manager = new ReleasePermissionManager($frsrf);
$package_permission_manager = new PackagePermissionManager($frspf);

$presenters      = [];
$stats_presenter = new \Tuleap\FRS\ProjectStatsPresenter(0, 0, 0, 0);

// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
$res = $frspf->getFRSPackagesFromDb($group_id);
foreach ($res as $package) {
    if ($frspf->userCanRead($package->getPackageID(), (int) $user->getId())) {
        $res_release = $package->getReleases();

        $presenters[] = PackagePresenter::fromPackage(
            $package,
            $fmmf->isMonitoring($package->getPackageID(), $user, false),
            count($res_release) > 0,
        );

        if ($request->existAndNonEmpty('release_id')) {
            if ($request->valid(new Valid_UInt('release_id'))) {
                $release_id = $request->get('release_id');
                $row3       = $frsrf->getFRSReleaseFromDb($release_id);
            }
        }
        if (! $request->existAndNonEmpty('release_id') || (isset($row3) && $row3->getPackageID() == $package->getPackageID())) {
            $is_collapsed = $pv !== false || $pv !== '0';

            if ($show_release_id !== false && $is_collapsed) {
                foreach ($package->getReleases() as $release) {
                    if ($release->getReleaseID() == $show_release_id) {
                        $is_collapsed = false;
                        break;
                    }
                }
            }

            $packages[$package->getPackageID()] = ['package' => $package, 'is_collapsed' => $is_collapsed];
        }

        $stats_presenter->releases += count($res_release);

        foreach ($res_release as $package_release) {
            if (! $release_permission_manager->canUserSeeRelease($user, $package_release)) {
                continue;
            }

            // get the files in this release....
            $res_file                = $frsff->getFRSFileInfoListByReleaseFromDb($package_release->getReleaseID());
            $stats_presenter->files += $res_file !== null ? count($res_file) : 0;

            if ($res_file) {
                foreach ($res_file as $file_release) {
                    $stats_presenter->size      += $file_release['file_size'];
                    $stats_presenter->downloads += $file_release['downloads'];
                }
            }
        }
    }
}

$GLOBALS['Response']->addJavascriptAsset(new JavascriptViteAsset(
    new IncludeViteAssets(
        __DIR__ . '/../../scripts/frs/frontend-assets',
        '/assets/core/frs',
    ),
    'src/frs.ts',
));

$hp = Codendi_HTMLPurifier::instance();

$params =  [
    'title' => sprintf(_('File packages for project "%s"'), $hp->purify($project->getPublicName())) ,
    'pv' => $pv,
];
$service->displayFRSHeader($project, $params['title'], new BreadCrumbCollection());

TemplateRendererFactory::build()
    ->getRenderer(__DIR__ . '/../../common/FRS/')
    ->renderToPage('list-packages', new ListPackagesPresenter(
        $project,
        $presenters,
        $stats_presenter,
        $permission_manager->isAdmin($project, $user),
        new CSRFSynchronizerToken('/file/?group_id=' . urlencode((string) $project->getID()))
    ));
file_utils_footer($params);
