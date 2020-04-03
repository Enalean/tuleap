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

define("FRS_EXPANDED_ICON", util_get_image_theme("ic/toggle_minus.png"));
define("FRS_COLLAPSED_ICON", util_get_image_theme("ic/toggle_plus.png"));

use Tuleap\FRS\Events\GetReleaseNotesLink;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDisplay;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\PackagePermissionManager;
use Tuleap\FRS\ReleasePermissionManager;
use Tuleap\FRS\UploadedLinkPresentersBuilder;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\FRS\UploadedLinksTablePresenter;

$authorized_user = false;

$request  = HTTPRequest::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
    exit();
}

$permission_manager = FRSPermissionManager::build();

$user_manager    = UserManager::instance();
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);
$user            = $user_manager->getCurrentUser();
if ($permission_manager->isAdmin($project, $user) || $permission_manager->userCanRead($project, $user)) {
    $authorized_user = true;
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

$packages        = array();
$num_packages    = 0;
$show_release_id = $request->getValidated('show_release_id', 'uint', false);

$renderer = TemplateRendererFactory::build()->getRenderer(
    ForgeConfig::get('codendi_dir') . '/src/templates/frs/'
);

$uploaded_links_retriever         = new UploadedLinksRetriever(new UploadedLinksDao(), $user_manager);
$uploaded_link_presenters_builder = new UploadedLinkPresentersBuilder();

$pv = false;
if ($request->valid(new Valid_Pv())) {
    $pv = $request->get('pv');
}

function buildReleaseNotesLink(FRSRelease $release): string
{
    $event = new GetReleaseNotesLink($release);
    EventManager::instance()->dispatch($event);
    $link_url   = $event->getUrl();
    $img_src    = util_get_image_theme("ic/text.png");
    $alt_text   = $GLOBALS['Language']->getText('file_showfiles', 'read_notes');
    $title_text = $GLOBALS['Language']->getText('file_showfiles', 'read_notes');
    return '<a href="' . $link_url . '"><img src="' . $img_src . '" alt="' . $alt_text . '" title="' . $title_text . '" ></a>';
}

// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
$res = $frspf->getFRSPackagesFromDb($group_id);
foreach ($res as $package) {
    if (
        $frspf->userCanRead($group_id, $package->getPackageID(), $user->getId())
        && $permission_manager->userCanRead($project, $user)
    ) {
        if ($request->existAndNonEmpty('release_id')) {
            if ($request->valid(new Valid_UInt('release_id'))) {
                $release_id = $request->get('release_id');
                $row3       = $frsrf->getFRSReleaseFromDb($release_id);
            }
        }
        if (! $request->existAndNonEmpty('release_id') || $row3->getPackageID() == $package->getPackageID()) {
            $is_collapsed = ! $pv;

            if ($show_release_id !== false && $is_collapsed) {
                foreach ($package->getReleases() as $release) {
                    if ($release->getReleaseID() == $show_release_id) {
                        $is_collapsed = false;
                        break;
                    }
                }
            }

            $packages[$package->getPackageID()] = array('package' => $package, 'is_collapsed' => $is_collapsed);
            $num_packages++;
        }
    }
}

$hp = Codendi_HTMLPurifier::instance();


$params = array (
    'title' => $Language->getText(
        'file_showfiles',
        'file_p_for',
        $hp->purify($project_manager->getProject($group_id)->getPublicName())
), 'pv' => $pv);
$project->getService(Service::FILE)->displayFRSHeader($project, $params['title']);

if ($num_packages < 1) {
    echo '<h3>' . $Language->getText('file_showfiles', 'no_file_p') . '</h3><p>' . $Language->getText('file_showfiles', 'no_p_available');
    if ($permission_manager->isAdmin($project, $user)) {
        echo '<p><a href="admin/package.php?func=add&amp;group_id=' . $group_id . '" data-test="create-new-package">[' . $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') . ']</a></p>';
    }
    file_utils_footer($params);
    exit;
}

$html = '';

if ($pv) {
    $html .= '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ':</h3>';
} else {
    $html .= "<TABLE width='100%'><TR><TD>";
    $html .= '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ' ' . help_button('documents-and-files/frs.html#delivery-manager-jargon') . '</h3>';
    $html .= "</TD>";
    $html .= "<TD align='left'> ( <A HREF='showfiles.php?group_id=$group_id&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
    $html .= "</TR></TABLE>";

    $html .= '<p>' . $Language->getText('file_showfiles', 'select_release') . '</p>';
}
// get unix group name for path
$group_unix_name = $project->getUnixName();

$proj_stats['packages'] = $num_packages;
$pm   = PermissionsManager::instance();
$fmmf = new FileModuleMonitorFactory();

$javascript_packages_array = array();

if (!$pv && $permission_manager->isAdmin($project, $user)) {
    $html .= '<p><a href="admin/package.php?func=add&amp;group_id=' . $group_id . '" data-test="create-new-package">[' . $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') . ']</a></p>';
}

$package_permission_manager = new PackagePermissionManager($permission_manager, $frspf);
$release_permission_manager = new ReleasePermissionManager($permission_manager, $frsrf);
$license_agreement_display  = new LicenseAgreementDisplay(
    $hp,
    TemplateRendererFactory::build(),
    new LicenseAgreementFactory(
        new LicenseAgreementDao()
    ),
);

$html .= $license_agreement_display->getModals($project);

// Iterate and show the packages
foreach ($packages as $package_id => $package_for_display) {
    $package = $package_for_display['package'];

    $can_see_package = false;

    if ($package->isActive()) {
        $emphasis = 'strong';
    } else {
        $emphasis = 'em';
    }

    $can_see_package = $package_permission_manager->canUserSeePackage($user, $package, $project);

    if ($can_see_package) {
        detectSpecialCharactersInName($package->getName(), $GLOBALS['Language']->getText('file_showfiles', 'package'));
        $html .= '<fieldset class="package">';
        $html .= '<legend>';
        if (!$pv) {
            $frs_icon = $package_for_display['is_collapsed'] ? FRS_COLLAPSED_ICON : FRS_EXPANDED_ICON;
            $html    .= '<a href="#" onclick="javascript:toggle_package(\'p_' . $package_id . '\'); return false;" /><img src="' . $frs_icon . '" id="img_p_' . $package_id . '" /></a>&nbsp;';
        }
        $html             .= " <$emphasis data-test='package-name'>" . $hp->purify(util_unconvert_htmlspecialchars($package->getName()))
            . "</$emphasis>";
        if (!$pv) {
            if ($permission_manager->isAdmin($project, $user)) {
                $html .= '     <a href="admin/package.php?func=edit&amp;group_id=' . $group_id . '&amp;id=' .
                    $package_id . '" data-test="update-package" title="' .
                    $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML)  . '">';
                $html .= '       ' . $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) , 'title' => $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) ));
                $html .= '</a>';
            }
            $html .= ' &nbsp; ';
            $html .= '  <a href="filemodule_monitor.php?filemodule_id=' . $package_id . '&group_id=' . $group_id . '">';
            if ($fmmf->isMonitoring($package_id, $user, false)) {
                $html .= '<img src="' . util_get_image_theme("ic/notification_stop.png") . '" alt="' . $Language->getText('file_showfiles', 'stop_monitoring') . '" title="' . $Language->getText('file_showfiles', 'stop_monitoring') . '" />';
            } else {
                $html .= '<img src="' . util_get_image_theme("ic/notification_start.png") . '" alt="' . $Language->getText('file_showfiles', 'start_monitoring') . '" title="' . $Language->getText('file_showfiles', 'start_monitoring') . '" />';
            }
            $html .= '</a>';
            if ($permission_manager->isAdmin($project, $user)) {
                $html .= '     &nbsp;&nbsp;<a href="admin/package.php?func=delete&amp;group_id=' . $group_id . '&amp;id=' . $package_id . '" title="' .  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML)  . '" onclick="return confirm(\'' .  $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'warn'), CODENDI_PURIFIER_CONVERT_HTML)  . '\');" data-test="remove-package">'
                            . $GLOBALS['HTML']->getImage('ic/trash.png', array('alt' => $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) , 'title' =>  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) )) . '</a>';
            }
        }
        $html .= '</legend>';

        if ($package->isHidden()) {
            //TODO i18n
            $html .= '<div style="text-align:center"><em>' . $Language->getText('file_showfiles', 'hidden_package') . '</em></div>';
        }
        // get the releases of the package
        // Order by release_date and release_id in case two releases
        // are published the same day
        $res_release  = $package->getReleases();
        $num_releases = count($res_release);

        if (!isset($proj_stats['releases'])) {
            $proj_stats['releases'] = 0;
        }
        $proj_stats['releases'] += $num_releases;

        $javascript_releases_array = array();
        $package_class_collapsed   = $package_for_display['is_collapsed'] ? 'frs_collapsed' : '';
        $html .= '<div class="' . $package_class_collapsed . '" id="p_' . $package_id . '">';
        if (!$pv && $permission_manager->isAdmin($project, $user)) {
            $html .= '<p><a
                         href="admin/release.php?func=add&amp;group_id=' . $group_id . '&amp;package_id=' . $package_id . '"
                         data-test="create-release">
                         [' . $GLOBALS['Language']->getText('file_admin_editpackages', 'add_releases') . ']
                         </a></p>';
        }
        if (!$res_release || $num_releases < 1) {
            $html .= '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
        } else {
            $cpt_release = 0;
            // iterate and show the releases of the package
            foreach ($res_release as $package_release) {
                $can_see_release = false;

                if ($package_release->isActive()) {
                    $emphasis = 'strong';
                } else {
                    $emphasis = 'em';
                }

                $can_see_release = $release_permission_manager->canUserSeeRelease($user, $package_release, $project);
                if ($can_see_release) {
                    detectSpecialCharactersInName($package_release->getName(), $GLOBALS['Language']->getText('file_showfiles', 'release'));

                    $permission_exists = $pm->isPermissionExist($package_release->getReleaseID(), 'RELEASE_READ');

                    // Highlight the release if one was chosen
                    $bgcolor = 'boxitem';
                    if ($request->existAndNonEmpty('release_id')) {
                        if ($request->valid(new Valid_UInt('release_id'))) {
                            $release_id = $request->get('release_id');
                            if ($release_id == $package_release->getReleaseID()) {
                                $bgcolor = 'boxitemalt';
                            }
                        }
                    }

                    $is_release_collapsed = $package_release->getReleaseID() != $show_release_id;

                    $html .= '<table width="100%" class="release">';
                    $html .= ' <TR id="p_' . $package_id . 'r_' . $package_release->getReleaseID() . '">';
                    $html .= '  <TD>';
                    if (!$pv) {
                        $frs_icon = $is_release_collapsed ? FRS_COLLAPSED_ICON : FRS_EXPANDED_ICON;
                        $html .= '<a href="#" onclick="javascript:toggle_release(\'p_' . $package_id . '\', \'r_' . $package_release->getReleaseID() . '\'); return false;" /><img src="' . $frs_icon . '" id="img_p_' . $package_id . 'r_' . $package_release->getReleaseID() . '" /></a>';
                    }
                    $html .= "     <$emphasis data-test='release-name'>" .
                        $hp->purify($package_release->getName()) . "</$emphasis>";
                    if (!$pv) {
                        if ($permission_manager->isAdmin($project, $user)) {
                            $html .= '     <a
                            href="admin/release.php?func=edit&amp;group_id=' . $group_id . '&amp;package_id=' . $package_id . '&amp;id=' . $package_release->getReleaseID() . '"
                            title="' .  $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML)  . '"
                            data-test="edit-release"
                            >'
                            . $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) , 'title' => $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) )) . '</a>';
                        }
                        $html .= '&nbsp;';
                        $html .= buildReleaseNotesLink($package_release);
                    }
                    $html .= '  </td>';
                    $html .= ' <td style="text-align:center">';
                    if ($package_release->isHidden()) {
                        $html .= '<em>' . $Language->getText('file_showfiles', 'hidden_release') . '</em>';
                    }
                    $html .= '</td> ';
                    $html .= '  <TD class="release_date">' . format_date("Y-m-d", $package_release->getReleaseDate()) . '';
                    if (!$pv && $permission_manager->isAdmin($project, $user)) {
                        $html .= ' <a
                        href="admin/release.php?func=delete&amp;group_id=' . $group_id . '&amp;package_id=' . $package_id . '&amp;id=' . $package_release->getReleaseID() . '"
                        title="' .  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML)  . '"
                        data-test="release-delete-button"
                        onclick="return confirm(\'' .  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'warn'), CODENDI_PURIFIER_JS_QUOTE) . '\');"
                        >'
                        . $GLOBALS['HTML']->getImage('ic/trash.png', array('alt' => $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) , 'title' =>  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) )) . '</a>';
                    }
                    $html .= '</TD></TR>' . "\n";
                    $html .= '</table>';

                    // get the files in this release....
                    $res_file = $frsff->getFRSFileInfoListByReleaseFromDb($package_release->getReleaseID());
                    $num_files = 0;
                    if ($res_file !== null) {
                        $num_files = count($res_file);
                    }
                    $uploaded_links  = $uploaded_links_retriever->getLinksForRelease($package_release);

                    if (!isset($proj_stats['files'])) {
                        $proj_stats['files'] = 0;
                    }
                    $proj_stats['files'] += $num_files;

                    $javascript_files_array  = array();
                    $release_class_collapsed = $is_release_collapsed && ! $pv ? 'frs_collapsed' : '';
                    if ((!$res_file && !$uploaded_links) || ($num_files < 1 && count($uploaded_links) < 1)) {
                        $html .= '<span class="' . $release_class_collapsed . '" id="p_' . $package_id . 'r_' . $package_release->getReleaseID() . 'f_0"><B>' . $Language->getText('file_showfiles', 'no_files') . '</B></span>' . "\n";
                        $javascript_files_array[] = "'f_0'";
                    } else {
                        $javascript_files_array[] = "'f_0'";
                        //get the file_type and processor type
                        $q = "select * from frs_filetype";
                        $res_filetype = db_query($q);
                        while ($resrow = db_fetch_array($res_filetype)) {
                            $file_type[$resrow['type_id']] = $resrow['name'];
                        }

                        $q = "select * from frs_processor";
                        $res_processor = db_query($q);
                        while ($resrow = db_fetch_array($res_processor)) {
                            $processor[$resrow['processor_id']] = $resrow['name'];
                        }

                        $html .= '<span class="' . $release_class_collapsed . '" id="p_' . $package_id . 'r_' . $package_release->getReleaseID() . 'f_0">';

                        if ($res_file) {
                            $title_arr = array();
                            $title_arr[] = $Language->getText('file_admin_editreleases', 'filename');
                            $title_arr[] = $Language->getText('file_showfiles', 'size');
                            $title_arr[] = $Language->getText('file_showfiles', 'd_l');
                            $title_arr[] = $Language->getText('file_showfiles', 'arch');
                            $title_arr[] = $Language->getText('file_showfiles', 'type');
                            $title_arr[] = $Language->getText('file_showfiles', 'date');
                            $title_arr[] = $Language->getText('file_showfiles', 'md5sum');
                            $title_arr[] = $Language->getText('file_showfiles', 'user');
                            $html .= html_build_list_table_top(
                                $title_arr,
                                false,
                                false,
                                true,
                                null,
                                "files_table"
                            ) . "\n";

                            // colgroup is used here in order to avoid table resizing when expand or collapse files, with CSS properties.
                            $html .= '<colgroup>';
                            $html .= ' <col class="frs_filename_col">';
                            $html .= ' <col class="frs_size_col">';
                            $html .= ' <col class="frs_downloads_col">';
                            $html .= ' <col class="frs_architecture_col">';
                            $html .= ' <col class="frs_filetype_col">';
                            $html .= ' <col class="frs_date_col">';
                            $html .= ' <col class="frs_md5sum_col">';
                            $html .= ' <col class="frs_user_col">';
                            $html .= '</colgroup>';

                            // now iterate and show the files in this release....
                            foreach ($res_file as $file_release) {
                                $filename = $file_release['filename'];
                                $list     = explode('/', $filename);
                                $fname    = $list[sizeof($list) - 1];
                                $class    = $bgcolor . ' ' . $release_class_collapsed;
                                $html     .= "\t\t" . '<TR id="p_' . $package_id . 'r_' . $package_release->getReleaseID(
                                ) . 'f_' . $file_release['file_id'] . '" class="' . $class . '"><TD><B>';

                                $javascript_files_array[] = "'f_" . $file_release['file_id'] . "'";

                                $html .= $license_agreement_display->getDownloadLink($package, (int) $file_release['file_id'], $fname);

                                $size_precision = 0;
                                if ($file_release['file_size'] < 1024) {
                                    $size_precision = 2;
                                }
                                $owner = UserManager::instance()->getUserById($file_release['user_id']);
                                $html  .= '</B></TD>' . '<TD>' . FRSFile::convertBytesToKbytes(
                                    $file_release['file_size'],
                                    $size_precision
                                ) . '</TD>' . '<TD>' . ($file_release['downloads'] ? $file_release['downloads'] : '0') . '</TD>';
                                $html  .= '<TD>' . (isset($processor[$file_release['processor']]) ? $hp->purify(
                                    $processor[$file_release['processor']],
                                    CODENDI_PURIFIER_CONVERT_HTML
                                ) : "") . '</TD>';
                                $html  .= '<TD>' . (isset($file_type[$file_release['type']]) ? $hp->purify(
                                    $file_type[$file_release['type']]
                                ) : "") . '</TD>' . '<TD>' . format_date(
                                    "Y-m-d",
                                    $file_release['release_time']
                                ) . '</TD>' .
                                    '<TD>' . (isset($file_release['computed_md5']) ? $hp->purify(
                                        $file_release['computed_md5']
                                    ) : "") . '</TD>' .
                                    '<TD>' . (isset($file_release['user_id']) ? $hp->purify(
                                        $owner->getRealName()
                                    ) : "") . '</TD>'
                                    . '</TR>
                             <TR>
                                <TD class="frs_comment">
                                    <p class="help-block">' .
                                    $hp->purify($file_release['comment'], CODENDI_PURIFIER_BASIC, $group_id) . '
                                    </p>
                                </TD>
                            </TR>';
                                if (! isset($proj_stats['size'])) {
                                    $proj_stats['size'] = 0;
                                }
                                $proj_stats['size'] += $file_release['file_size'];
                                if (! isset($proj_stats['downloads'])) {
                                    $proj_stats['downloads'] = 0;
                                }
                                $proj_stats['downloads'] += $file_release['downloads'];
                            }
                        }

                        if (count($uploaded_links) > 0) {
                            $link_presenters = $uploaded_link_presenters_builder->build($uploaded_links);

                            $uploaded_links_presenter = new UploadedLinksTablePresenter($link_presenters);
                            $html .= $renderer->renderToString('uploaded-links', $uploaded_links_presenter);
                        }

                        $html .= '</table>';
                        $html .= '</span>';
                    }
                    $javascript_releases_array[] = "'r_" . $package_release->getReleaseID() . "': [" . implode(",", $javascript_files_array) . "]";
                    $cpt_release = $cpt_release + 1;
                }
            }
            if (!$cpt_release) {
                $html .= '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
            }
        }
        $html .= '</div>';
        $html .= '</fieldset>';
        $javascript_packages_array[] = "'p_" . $package_id . "': {" . implode(",", $javascript_releases_array) . "}";
    }
}

echo $html;

if (!$pv) {
    $javascript_array = 'var packages = {';
    $javascript_array .= implode(",", $javascript_packages_array);
    $javascript_array .= '}';
    print '<script language="javascript">' . $javascript_array . '</script>';
}
// project totals (statistics)
if (isset($proj_stats['size'])) {
    $total_size = FRSFile::convertBytesToKbytes($proj_stats['size']);

    print '<p>';
    print '<b>' . $Language->getText('file_showfiles', 'proj_total') . ': </b>';
    print $proj_stats['releases'] . ' ' . $Language->getText('file_showfiles', 'stat_total_nb_releases') . ', ';
    print $proj_stats['files'] . ' ' . $Language->getText('file_showfiles', 'stat_total_nb_files') . ', ';
    print $total_size . ' ' . $Language->getText('file_showfiles', 'stat_total_size') . ', ';
    print $proj_stats['downloads'] . ' ' . $Language->getText('file_showfiles', 'stat_total_nb_downloads') . '.';
    print '</p>';
}

?>

<script language="javascript">
<!--

function toggle_package(package_id) {
    var element = document.getElementById(package_id);
    if(element === null) {
        return;
    }
    element.classList.toggle('frs_collapsed');
    toggle_image(package_id);
}

function toggle_release(package_id, release_id) {
    $A(packages[package_id][release_id]).each(function(file_id) {
        // toggle the content of the release (the files)
        var element = document.getElementById(package_id + release_id + file_id);
        if(element === null) {
            return;
        }
        element.classList.toggle('frs_collapsed');
    });
    toggle_image(package_id + release_id);
}

function toggle_image(image_id) {
    var img_element = $('img_' + image_id);
    if (img_element.src.indexOf('<?php echo FRS_COLLAPSED_ICON; ?>') != -1) {
        img_element.src = '<?php echo FRS_EXPANDED_ICON; ?>';
    } else {
        img_element.src = '<?php echo FRS_COLLAPSED_ICON; ?>';
    }
}

(function($) {
    $('.frs-license-agreement-modal-link').click(function (event) {
        event.preventDefault();
        var file_id = $(this).data('file-id');
        var agreement_id = $(this).data('agreement-id');
        $('#frs-license-agreement-accept_'+agreement_id).data('download-file-id', file_id);
        $('#frs-license-agreement-modal_'+agreement_id).modal('show');
    });
    $('.frs-license-agreement-accept').click(function (event) {
        event.preventDefault();
        var file_id = $(this).data('download-file-id');
        var agreement_id = $(this).data('agreement-id');
        $('#frs-license-agreement-modal_'+agreement_id).modal('hide');
        window.open('/file/download/'+file_id);
    });
})(jQuery);

-->

</script>

<?php

file_utils_footer($params);
