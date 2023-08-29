<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDisplay;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksInvalidFormException;
use Tuleap\FRS\UploadedLinksRequestFormatter;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\FRS\UploadedLinksUpdater;
use Tuleap\FRS\UploadedLinkUpdateTablePresenter;

function file_utils_header($params)
{
    global $group_id,$Language;

    $params['toptab'] = 'file';
    $params['group']  = $group_id;

    if (! array_key_exists('pv', $params) || ! $params['pv']) {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($group_id);

        $service = $project->getService(Service::FILE);
        if ($service !== null) {
            assert($service instanceof ServiceFile);
            $service->displayFRSHeader($project, $params['title']);
        }
    }
}

function file_utils_footer($params)
{
    site_project_footer($params);
}

/*

 The following functions are for the FRS (File Release System)
 They were moved here from project_admin_utils.php since they can
 now be used by non-admins (e.g. file releases admins)

*/


/*

    pop-up box of supported frs statuses

*/

function frs_show_status_popup($name = 'status_id', $checked_val = "xzxz")
{
    global $Language;

    $package_factory = new FRSPackageFactory();
    $arr_id          = [$package_factory->STATUS_ACTIVE, $package_factory->STATUS_HIDDEN];
    $arr_status      = [
        $Language->getText('file_admin_editpackages', 'status_active'),
        $Language->getText('file_admin_editpackages', 'status_hidden'),
    ];

    return html_build_select_box_from_arrays($arr_id, $arr_status, $name, $checked_val, false);
}

/*

    pop-up box of supported frs filetypes

*/

function frs_show_filetype_popup($name = 'type_id', $checked_val = "xzxz")
{
    /*
        return a pop-up select box of the available filetypes
    */
    global $FRS_FILETYPE_RES,$Language;
    if (! isset($FRS_FILETYPE_RES)) {
// LJ Sort by type_id added so that new extensions goes
// LJ in the right place in the menu box
        $FRS_FILETYPE_RES = db_query("SELECT * FROM frs_filetype ORDER BY type_id");
    }
    return html_build_select_box($FRS_FILETYPE_RES, $name, $checked_val, true, $Language->getText('file_file_utils', 'must_choose_one'));
}

/*

    pop-up box of supported frs processor options

*/

function frs_show_processor_popup($group_id, $name = 'processor_id', $checked_val = "xzxz")
{
    /*
        return a pop-up select box of the available processors
    */
    global $FRS_PROCESSOR_RES,$Language;
    if (! isset($FRS_PROCESSOR_RES)) {
        $FRS_PROCESSOR_RES = db_query("SELECT * FROM frs_processor WHERE group_id=100 OR group_id=" . db_ei($group_id) . " ORDER BY `rank`");
    }
    return html_build_select_box($FRS_PROCESSOR_RES, $name, $checked_val, true, $Language->getText('file_file_utils', 'must_choose_one'), false, '', false, '', false, '', CODENDI_PURIFIER_CONVERT_HTML);
}


/*

    pop-up box of packages:releases for this group

*/


function frs_show_release_popup($group_id, $name = 'release_id', $checked_val = "xzxz")
{
    /*
        return a pop-up select box of releases for the project
    */
    global $FRS_RELEASE_ID_RES,$FRS_RELEASE_NAME_RES,$Language;
    $release_factory = new FRSReleaseFactory();
    if (! $group_id) {
        return $Language->getText('file_file_utils', 'g_id_err');
    } else {
        if (! isset($FRS_RELEASE_ID_RES)) {
            $res                  = $release_factory->getFRSReleasesInfoListFromDb($group_id);
            $FRS_RELEASE_ID_RES   = [];
            $FRS_RELEASE_NAME_RES = [];
            foreach ($res as $release) {
                $FRS_RELEASE_ID_RES[]   = $release['release_id'];
                $FRS_RELEASE_NAME_RES[] = $release['package_name'] . ':' . $release['release_name'];
            }
        }
        return html_build_select_box_from_arrays($FRS_RELEASE_ID_RES, $FRS_RELEASE_NAME_RES, $name, $checked_val, false);
    }
}
function frs_show_release_popup2($group_id, $name = 'release_id', $checked_val = "xzxz")
{
    /*
        return a pop-up select box of releases for the project
    */
    $release_factory = new FRSReleaseFactory();
    if (! $group_id) {
        return $GLOBALS['Language']->getText('file_file_utils', 'g_id_err');
    } else {
        $hp  = Codendi_HTMLPurifier::instance();
        $res = $release_factory->getFRSReleasesInfoListFromDb($group_id);
        $p   = [];
        foreach ($res as $release) {
            $p[$release['package_name']][$release['release_id']] = $release['release_name'];
        }

        $select = '<select name="' . $name . '">';
        foreach ($p as $package_name => $releases) {
            $select .= '<optgroup label="' . $package_name . '">';
            foreach ($releases as $id => $name) {
                $select .= '<option value="' . $id . '" ' . ($id == $checked_val ? 'selected="selected"' : '') . '>' . $hp->purify($name) . '</option>';
            }
            $select .= '</optgroup>';
        }
        $select .= '</select>';
        return $select;
    }
}

function file_utils_show_processors($result)
{
    global $group_id,$Language;
    $hp   = Codendi_HTMLPurifier::instance();
    $rows =  db_numrows($result);

    $title_arr   = [];
    $title_arr[] = $Language->getText('file_file_utils', 'proc_name');
    $title_arr[] = $Language->getText('file_file_utils', 'proc_rank');
    $title_arr[] = $Language->getText('file_file_utils', 'del');

    echo html_build_list_table_top($title_arr);

    for ($j = 0; $j < $rows; $j++) {
        $proc_id   = db_result($result, $j, 'processor_id');
        $proc_name = db_result($result, $j, 'name');
        $proc_rank = db_result($result, $j, 'rank');
        $gr_id     = db_result($result, $j, 'group_id');

        echo '<tr class="' . html_get_alt_row_color($j) . '">' . "\n";

        if ($gr_id == "100") {
            echo '<td>' . $hp->purify($proc_name) . '</td>';
        } else {
            echo '<td><A HREF="/file/admin/editproc.php?group_id=' . $group_id . '&proc_id=' . $proc_id . '" title="' . $hp->purify($proc_id . ' - ' . $proc_name) . '">' . $hp->purify($proc_name) . '</td>';
        }

        echo '<td>' . $proc_rank . "</td>\n";

        if ($gr_id == "100") {
            // pre-defined processors are not manageable
            echo '<TD align=center>-</TD>';
        } else {
            echo '<TD align=center>' .
            '<a href="/file/admin/manageprocessors.php?mode=delete&group_id=' . $group_id . '&proc_id=' . $proc_id . '" ' .
            '" onClick="return confirm(\'' . $Language->getText('file_file_utils', 'del_proc') . '\')">' .
            '<IMG SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="' . $Language->getText('file_file_utils', 'del') . '"></A></TD>';
        }

        echo "</tr>";
    }
    echo "</table>";
}

function file_utils_add_proc($pname, $prank)
{
    global $group_id,$Language;

    $sql    = sprintf(
        'INSERT INTO frs_processor' .
           ' (name,group_id,`rank`)' .
           ' VALUES' .
           '("%s",%d,%d)',
        db_es($pname),
        db_ei($group_id),
        db_ei($prank)
    );
    $result = db_query($sql);

    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils', 'add_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils', 'add_proc_fail'));
    }
}

function file_utils_update_proc($pid, $pname, $prank)
{
    global $group_id,$Language;

    $sql    = sprintf(
        'UPDATE frs_processor' .
           ' SET name = "%s",`rank` = %d' .
           ' WHERE processor_id=%d' .
           ' AND group_id=%d',
        db_es($pname),
        db_ei($prank),
        db_ei($pid),
        db_ei($group_id)
    );
    $result = db_query($sql);

    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils', 'update_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils', 'update_proc_fail'));
    }
}

function file_utils_delete_proc($pid)
{
    global $group_id,$Language;

    $sql    = sprintf(
        'DELETE FROM frs_processor' .
           ' WHERE group_id=%d' .
           ' AND processor_id=%d',
        db_ei($group_id),
        db_ei($pid)
    );
    $result = db_query($sql);

    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils', 'delete_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils', 'delete_proc_fail'));
    }
}

function frs_display_package_form(FRSPackage $package, $title, $url, $siblings)
{
    $hp                        = Codendi_HTMLPurifier::instance();
    $project                   = ProjectManager::instance()->getProject($package->getGroupID());
    $license_agreement_display = new LicenseAgreementDisplay(
        $hp,
        TemplateRendererFactory::build(),
        new LicenseAgreementFactory(
            new LicenseAgreementDao(),
        ),
    );

    file_utils_header(['title' => $title, 'help' => 'frs.html#delivery-manager-administration']);
    echo '<h3>' . $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML) . '</h3>

    <form action="' . $url . '" method="post">
    <table>
    <tr><th>' . $GLOBALS['Language']->getText('file_admin_editpackages', 'p_name') . ':</th>  <td>
        <input type="text" name="package[name]" data-test="frs-create-package" CLASS="textfield_small" value="' .
        $hp->purify(
            util_unconvert_htmlspecialchars($package->getName()),
            CODENDI_PURIFIER_CONVERT_HTML
        ) . '">';
    //{{{ Rank
    $nb_siblings = count($siblings);
    if ($nb_siblings && ($nb_siblings > 1 || $siblings[0] != $package->getPackageId())) {
        echo '</td></tr>';
        echo '<tr><th>' . $GLOBALS['Language']->getText('file_admin_editpackages', 'rank_on_screen') . ':</th><td>';
        echo $GLOBALS['HTML']->selectRank($package->getPackageId(), $package->getRank(), $siblings, ['name' => 'package[rank]']);
    } else {
        echo '<input type="hidden" name="package[rank]" value="0" />';
    }
    echo '</td></tr>';
    //}}}
    echo '<tr><th>' . $GLOBALS['Language']->getText('global', 'status') . ':</th>  <td data-test="status">' . frs_show_status_popup('package[status_id]', $package->getStatusID()) . '</td></tr>';
    echo $license_agreement_display->getPackageEditSelector($package, $project);

     //We cannot set permission on creation for now
    if ($package->getPackageID()) {
        echo '<tr style="vertical-align:top"><th>' . 'Permissions' . ':</th><td>';
        $package_controller = new FRSPackageController(
            FRSPackageFactory::instance(),
            FRSReleaseFactory::instance(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            PermissionsManager::instance(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            Codendi_HTMLPurifier::instance(),
        );

        $package_controller->displayUserGroups($project, FRSPackage::PERM_READ, $package->getPackageID());
        echo '</td></tr>';
    }
     echo '<tr><td></td><td> <br>
                <input class="btn btn-primary"
                       type="submit"
                       name="submit"
                       value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '"
                       data-test="frs-create-package-button"
                 /> ';
     echo '<input class="btn" type="submit" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" /></td></tr></table>
     </FORM>';

     file_utils_footer([]);
}

function frs_display_release_form($is_update, &$release, $group_id, $title, $url)
{
    global $package_factory, $release_factory, $files_factory;
    $hp = Codendi_HTMLPurifier::instance();
    if (is_array($release)) {
        if (isset($release['date'])) {
            $release_date = $release['date'];
        }
        $release = new FRSRelease($release);
    }
    if ($is_update) {
        $files = $release->getFiles();
        if (count($files) > 0) {
            for ($i = 0; $i < count($files); $i++) {
                if (! $files_factory->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'md5_fail', [basename($files[$i]->getFileName()), $files[$i]->getComputedMd5()]));
                }
            }
        }
    }

    file_utils_header([
        'title' => $GLOBALS['Language']->getText(
            'file_admin_editreleases',
            'release_new_file_version'
        ), 'help' => 'frs.html#delivery-manager-administration',
    ]);

    echo '<H3>' . $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML) . '</H3>';
    $sql          = "SELECT * FROM frs_processor WHERE (group_id = 100 OR group_id = " . db_ei($group_id) . ") ORDER BY `rank`";
    $result       = db_query($sql);
    $processor_id = util_result_column_to_array($result, 0);
    foreach ($processor_id as $key => $id) {
        $processor_id[$key] = $hp->purify($id, CODENDI_PURIFIER_JS_QUOTE);
    }
    $processor_name = util_result_column_to_array($result, 1);
    foreach ($processor_name as $key => $value) {
        $processor_name[$key] = $hp->purify($value, CODENDI_PURIFIER_JS_QUOTE);
    }
    $sql     = "SELECT * FROM frs_filetype ORDER BY type_id";
    $result1 = db_query($sql);
    $type_id = util_result_column_to_array($result1, 0);
    foreach ($type_id as $key => $id) {
        $type_id[$key] = $hp->purify($id, CODENDI_PURIFIER_JS_QUOTE);
    }
    $type_name = util_result_column_to_array($result1, 1);
    foreach ($type_name as $key => $name) {
        $type_name[$key] = $hp->purify($name, CODENDI_PURIFIER_JS_QUOTE);
    }
    $url_news = "/file/showfiles.php?group_id=" . $group_id;
    $script   = "var processor_id = ['" . implode("', '", $processor_id) . "'];";
    $script  .= "var processor_name = ['" . implode("', '", $processor_name) . "'];";
    $script  .= "var type_id = ['" . implode("', '", $type_id) . "'];";
    $script  .= "var type_name = ['" . implode("', '", $type_name) . "'];";
    $script  .= "var group_id = " . $hp->purify($group_id, CODENDI_PURIFIER_JS_QUOTE) . ";";
    $script  .= "var relname = '" . $hp->purify($GLOBALS['Language']->getOverridableText('file_admin_editreleases', 'relname'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var choose = '" . $hp->purify($GLOBALS['Language']->getText('file_file_utils', 'must_choose_one'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var browse = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'browse'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var local_file = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'local_file'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var scp_ftp_files = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'scp_ftp_files'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var upload_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'upload'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var add_file_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'add_file'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var add_change_log_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'add_change_log'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var view_change_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'view_change'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var refresh_files_list = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'refresh_file_list'), CODENDI_PURIFIER_JS_QUOTE) . "';";
    $script  .= "var release_mode = '" . $hp->purify($is_update ? 'edition' : 'creation', CODENDI_PURIFIER_JS_QUOTE) . "';";

    if ($is_update) {
        $pm  = PermissionsManager::instance();
        $dar = $pm->getAuthorizedUgroups($release->getReleaseID(), FRSRelease::PERM_READ);

        $ugroup_factory      = new User_ForgeUserGroupFactory(new UserGroupDao());
        $all_project_ugroups = $ugroup_factory->getAllForProject($release->getProject());
        $project_names       = [];
        foreach ($all_project_ugroups as $project_ugroup) {
            $project_names[$project_ugroup->getId()] = $project_ugroup->getName();
        }

        $ugroups_name = [];
        foreach ($dar as $row) {
            if (! isset($row['ugroup_id'], $project_names[$row['ugroup_id']])) {
                continue;
            }
            $ugroups_name[] = $hp->purify($project_names[$row['ugroup_id']], CODENDI_PURIFIER_JS_QUOTE);
        }
        $script .= "var ugroups_name = ' " . implode(", ", $ugroups_name) . " ';";
        $script .= "var default_permissions_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'release_perm'), CODENDI_PURIFIER_JS_QUOTE) . " ';";
    } else {
        $script .= "var default_permissions_text = '" . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'default_permissions'), CODENDI_PURIFIER_JS_QUOTE) . " ';";
    }
    $GLOBALS['Response']->includeFooterJavascriptSnippet($script);
    //set variables for news template
    $relname = $GLOBALS['Language']->getOverridableText('file_admin_editreleases', 'relname');
    if (! $is_update) {
        echo '<p>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'contain_multiple_files')) . '</p>';
    }
    ?>

    <FORM id="frs_form" NAME="frsRelease" ENCTYPE="multipart/form-data" METHOD="POST" ACTION="<?php echo $url; ?>" CLASS="form-inline">
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<?php echo $hp->purify(ForgeConfig::get('sys_max_size_upload')); ?>">
        <input type="hidden" name="postReceived" value="" />
        <?php
        if ($release->getReleaseId()) {
            echo '<input type="hidden" id="release_id" name="release[release_id]" value="' . $hp->purify($release->getReleaseId()) . '" />';
        }
        ?>
        <TABLE BORDER="0" width="100%">
        <TR><TD><FIELDSET><LEGEND><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_properties')); ?></LEGEND>
        <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
            <TR>
                <TD>
                    <B><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'p_name')); ?>:</B>
                </TD>
                <TD>
    <?php
    $res  = $package_factory->getFRSPackagesFromDb($group_id);
    $rows = count($res);
    if (! $res || $rows < 1) {
        echo '<p class="highlight">' . $hp->purify($GLOBALS['Language']->getText('file_admin_qrs', 'no_p_available')) . '</p>';
    } else {
        echo '<SELECT NAME="release[package_id]" id="package_id">';
        for ($i = 0; $i < $rows; $i++) {
            echo '<OPTION VALUE="' . $hp->purify($res[$i]->getPackageID()) . '"';
            if ($res[$i]->getPackageID() == $release->getPackageId()) {
                echo ' selected';
            }
            echo '>' . $hp->purify(util_unconvert_htmlspecialchars($res[$i]->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</OPTION>';
        }
        echo '</SELECT>';
    }
    ?>
                </TD><td></td>
                <TD>
                    <B><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'release_name')); ?>: <span class="highlight"><strong>*</strong></span></B>
                </TD>
                <TD>
                    <INPUT
                            TYPE="TEXT"
                            id="release_name"
                            name="release[name]"
                            onBlur="update_news()"
                            data-test="release-name"
                            value="<?php echo $hp->purify($release->getName()); ?>"
                    >
                </TD>
            </TR>
            <TR>
                <TD>
                    <B><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'release_date')); ?>:</B>
                </TD>
                <TD>
                <?php echo $GLOBALS['HTML']->getDatePicker('release_date', 'release[date]', isset($release_date) ? $hp->purify($release_date) : format_date('Y-m-d', $release->getReleaseDate())); ?>
                </TD>
                <td></td>
                <TD>
                    <B><?php echo $hp->purify($GLOBALS['Language']->getText('global', 'status')); ?>:</B>
                </TD>
                <TD>
                    <?php

                    print frs_show_status_popup($name = 'release[status_id]', $release->getStatusID()) . "<br>";
                    ?>
                </TD>
            </TR>

            <?php
                $additional_info   = '';
                $notes_in_markdown = false;

                $params = [
                    'release_id'        => $release->getReleaseId(),
                    'additional_info'   => &$additional_info,
                    'notes_in_markdown' => &$notes_in_markdown,
                ];

                EventManager::instance()->processEvent(
                    'frs_edit_form_additional_info',
                    $params
                );

            if ($additional_info) {
                echo '<tr>';
                echo $additional_info;
                echo '</tr>';
            }
            ?>

        </TABLE></FIELDSET>
        </TD></TR>
        <TR><TD><FIELDSET><LEGEND><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_uploaded_files')); ?></LEGEND>
    <?php

    $titles   =  [];
    $titles[] = $is_update ? $GLOBALS['Language']->getText('file_admin_editreleases', 'delete_col') : '';
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'filename');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'processor');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'file_type');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'md5sum');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'comment');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'user');
    if ($is_update) {
        $titles[] = $GLOBALS['Language']->getText('file_admin_editreleasepermissions', 'release');
        $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'release_date');
    }
    echo html_build_list_table_top($titles, false, false, false, 'files');
    ?>
            <tbody id="files_body">

    <?php
        $files = $release->getFiles();
    for ($i = 0; $i < count($files); $i++) {
        $fname    = $files[$i]->getFileName();
        $list     = explode('/', $fname);
        $fname    = $list[sizeof($list) - 1];
        $user_id  = $files[$i]->getUserID();
        $userName = (isset($user_id)) ? UserManager::instance()->getUserById($files[$i]->getUserID())->getRealName() : "";
        echo '<TR>';
        echo '<TD><INPUT TYPE="CHECKBOX" NAME="release_files_to_delete[]" VALUE="' . $hp->purify($files[$i]->getFileID()) . '"></TD>';
        echo '<TD>' . $hp->purify($fname, CODENDI_PURIFIER_CONVERT_HTML) . '<INPUT TYPE="HIDDEN" NAME="release_files[]" VALUE="' . $hp->purify($files[$i]->getFileID()) . '"></TD>';
        echo '<TD>' . frs_show_processor_popup($group_id, $name = 'release_file_processor[]', $files[$i]->getProcessorID()) . '</TD>';
        echo '<TD>' . frs_show_filetype_popup($name = 'release_file_type[]', $files[$i]->getTypeID()) . '</TD>';
        //In case of difference between the inserted md5 and the computed one
        //we dispaly an editable text field to let the user insert the right value
        //to avoid the error message next time
        $value = 'value = "' . $hp->purify($files[$i]->getReferenceMd5()) . '"';
        if ($files_factory->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
            $value = 'value = "' . $hp->purify($files[$i]->getComputedMd5()) . '" readonly="true"';
        }
        echo '<TD><INPUT TYPE="TEXT" NAME="release_reference_md5[]" ' . $value . ' SIZE="36" ></TD>';
        $comment = $files[$i]->getComment();
        echo '<TD><textarea NAME="release_comment[]" cols="20" rows="1">' . $hp->purify($comment) . '</textarea></TD>';
        echo '<TD><INPUT TYPE="TEXT" NAME="user" value = "' . $hp->purify($userName) . '" readonly="true"></TD>';
        echo '<TD>' . frs_show_release_popup2($group_id, $name = 'new_release_id[]', $files[$i]->getReleaseID()) . '</TD>';
        echo '<TD><INPUT TYPE="TEXT" NAME="release_time[]" VALUE="' . $hp->purify(format_date('Y-m-d', $files[$i]->getReleaseTime())) . '" SIZE="10" MAXLENGTH="10"></TD></TR>';
    }
        echo '<INPUT TYPE="HIDDEN" id="nb_files" NAME="nb_files" VALUE="' . $hp->purify(count($files)) . '">';
    ?>

                        <tr id="row_0">
                            <td></td>
                            <td>
                                <input type="hidden" name="js" value="no_js"/>
                                <select name="ftp_file[]" id="ftp_file_0">
                                    <option value="-1"><?php echo $hp->purify($GLOBALS['Language']->getText('file_file_utils', 'must_choose_one')); ?></option>
    <?php

    //iterate and show the files in the upload directory
    $file_list    = $files_factory->getUploadedFileNames($release->getProject());
    $file_list_js = [];
    foreach ($file_list as $file) {
        echo '<option value="' . $hp->purify($file) . '">' . $hp->purify($file, CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
        $file_list_js[] = $hp->purify($file, CODENDI_PURIFIER_JS_QUOTE);
    }
    $GLOBALS['Response']->includeFooterJavascriptSnippet("var available_ftp_files = ['" . implode("', '", $file_list_js) . "'];");

    ?>
                                </select>

                                <span id="or">or</span>
                                <input type="file" name="file[]" id="file_0" />
                            </td>
                            <td>
                                <?php print frs_show_processor_popup($group_id, $name = 'file_processor'); ?>
                            </td>
                            <td>
                                <?php print frs_show_filetype_popup($name = 'file_type'); ?>
                            </td>
                            <td>
                                <input name="reference_md5" value="" size="36" type="TEXT">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php
                echo '<span class="small" style="color:#666"><i>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'upload_file_msg', formatByteToMb(ForgeConfig::get('sys_max_size_upload')))) . '</i> </span>';

                $renderer = TemplateRendererFactory::build()->getRenderer(
                    ForgeConfig::get('codendi_dir') . '/src/templates/frs/'
                );

                $uploaded_links_retriever = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
                $existing_links           = $uploaded_links_retriever->getLinksForRelease($release);

                $uploaded_link_spresenter_builder = new \Tuleap\FRS\UploadedLinkPresentersBuilder();
                $existing_links_presenter         = $uploaded_link_spresenter_builder->build($existing_links);
                $uploaded_links_create_presenter  = new UploadedLinkUpdateTablePresenter($existing_links_presenter);

                echo $renderer->renderToString('uploaded-links-form', $uploaded_links_create_presenter);
                ?>
            </FIELDSET>
            </TD></TR>
            <TR><TD><FIELDSET><LEGEND><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_notes')); ?></LEGEND>
            <?php
            if ($notes_in_markdown) {
                echo '<p class="help">
                            <i class="fa fa-info-circle"></i>
                            ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'notes_in_markdown')) . '
                        </p>';
            }
            ?>
            <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2" WIDTH="100%">
            <TR id="notes_title">
                <TD VALIGN="TOP" width="10%">
                    <span id="release_notes"><B><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'release_notes')); ?>:  </B></span>
                </TD>
            </TR>
            <TR id="upload_notes">
                <TD>
                    <input id="uploaded_notes" type="file" name="uploaded_release_notes"  size="30">
                </TD>
            </TR>
            <TR id="release_notes_area">
                <TD width="100%">
                    <TEXTAREA NAME="release[release_notes]" rows="7" cols="70" data-test="release-note"><?php echo $hp->purify($release->getNotes(), CODENDI_PURIFIER_CONVERT_HTML);?></TEXTAREA>
                </TD>
            </TR>
            <TR id="change_log_title">
                <TD VALIGN="TOP" width="10%">
                    <span id="change_log"><B><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'change_log')); ?>:  </B></span>
                </TD>
            </TR>
            <TR id="upload_change_log">
                <TD>
                    <input type="file" id="uploaded_change_log" name="uploaded_change_log"  size="30">
                </TD>
            </TR>
            <TR id="change_log_area">
                <TD width="40%">
                    <TEXTAREA ID="text_area_change_log" NAME="release[change_log]" ROWS="7" COLS="70"><?php echo $hp->purify($release->getChanges(), CODENDI_PURIFIER_CONVERT_HTML);?></TEXTAREA>
                </TD>
            </TR>
            </TABLE></FIELDSET>
            </TD></TR>
            <TR>
                <TD>
                    <FIELDSET><LEGEND><?php echo $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_permissions')); ?></LEGEND>
                        <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">

                            <TR id="permissions">
                                <TD>
                                    <DIV id="permissions_list">
                                        <?php
                                        $project = ProjectManager::instance()->getProject($group_id);
                                        if ($is_update) {
                                            $release_controller = new FRSPackageController(
                                                FRSPackageFactory::instance(),
                                                FRSReleaseFactory::instance(),
                                                new User_ForgeUserGroupFactory(new UserGroupDao()),
                                                PermissionsManager::instance(),
                                                new LicenseAgreementFactory(
                                                    new LicenseAgreementDao()
                                                ),
                                                Codendi_HTMLPurifier::instance(),
                                            );

                                            $release_controller->displayUserGroups($project, FRSRelease::PERM_READ, $release->getReleaseID());
                                        } else {
                                            $package_controller = new FRSPackageController(
                                                FRSPackageFactory::instance(),
                                                FRSReleaseFactory::instance(),
                                                new User_ForgeUserGroupFactory(new UserGroupDao()),
                                                PermissionsManager::instance(),
                                                new LicenseAgreementFactory(
                                                    new LicenseAgreementDao()
                                                ),
                                                Codendi_HTMLPurifier::instance(),
                                            );

                                            $package_controller->displayUserGroups($project, FRSPackage::PERM_READ, $release->getPackageID());
                                        }
                                        ?>

                                    </DIV>
                                </TD>
                            </TR>
                        </TABLE>

                    </FIELDSET>
                </TD>
            </TR>
            <?php

            $is_user_allowed_to_send_news = user_ismember($group_id, 'A') || user_ismember($group_id, 'N2') || user_ismember($group_id, 'N1');
            if ($project->usesService(Service::NEWS) && $is_user_allowed_to_send_news) {
                echo '
            <TR><TD><FIELDSET><LEGEND>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_news')) . '</LEGEND>
                <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
                    <TR>
                        <TD VALIGN="TOP">
                            <B> ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'submit_news')) . ' :</B>
                        </TD>
                        <TD>
                            <INPUT ID="submit_news" TYPE="CHECKBOX" NAME="release_submit_news" VALUE="1">

                        </TD>
                    </TR>
                    <TR id="tr_subject">
                        <TD VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'subject')) . ' :</B>
                        </TD>
                        <TD>
                            <INPUT TYPE="TEXT" ID="release_news_subject" NAME="release_news_subject" VALUE=" ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'file_news_subject', $relname)) . '" SIZE="40" MAXLENGTH="60">
                        </TD>
                    </TR>
                    <TR id="tr_details">
                        <TD VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'details')) . ' :</B>
                        </TD>
                        <TD>
                            <TEXTAREA ID="release_news_details" NAME="release_news_details" ROWS="7" COLS="50">' . $hp->purify($GLOBALS['Language']->getOverridableText('file_admin_editreleases', 'file_news_details', [$relname, $url_news])) . ' </TEXTAREA>
                        </TD>
                    </TR>
                    <TR id="tr_public">
                        <TD ROWSPAN=2 VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $GLOBALS['Language']->getText('news_submit', 'news_privacy') . ' :</B>
                        </TD>
                        <TD>
                            <INPUT TYPE="RADIO" ID="publicnews" NAME="private_news" VALUE="0" CHECKED>' . $GLOBALS['Language']->getText('news_submit', 'public_news') . '
                        </TD>
                    </TR >
                    <TR id="tr_private">
                        <TD>
                            <INPUT TYPE="RADIO" ID="privatenews" NAME="private_news" VALUE="1">' . $GLOBALS['Language']->getText('news_submit', 'private_news') . '
                        </TD>
                    </TR></DIV>
                </TABLE></FIELDSET>
            </TD></TR>';
            }

            $fmmf  = new FileModuleMonitorFactory();
            $count = count($fmmf->getFilesModuleMonitorFromDb($release->getPackageId()));
            if ($count > 0) {
                echo '<TR><TD><FIELDSET><LEGEND>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_notification')) . '</LEGEND>';
                echo '<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">';
                echo '<TR><TD>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'users_monitor', $count)) . '</TD></TR>';
                echo '<TR><TD><B>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'mail_file_rel_notice')) . '</B><INPUT TYPE="CHECKBOX" NAME="notification" VALUE="1" CHECKED>';
                echo '</TD></TR>';
                echo '</TABLE></FIELDSET></TD></TR>';
            }
            ?>

            <TR>
                <TD ALIGN="CENTER">

                    <INPUT TYPE="HIDDEN" NAME="create" VALUE="bla">
                    <INPUT
                            TYPE="submit"
                            class="btn btn-primary"
                            ID="create_release"
                            data-test="create-release-button"
                            VALUE="<?php echo $is_update ? $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release')) : $hp->purify($GLOBALS['Language']->getText('file_admin_qrs', 'release_file')); ?>"
                    >
                    <?php
                        $cancel_url = "/file/showfiles.php?" . http_build_query(
                            ['group_id'   => $group_id, 'show_release_id' => $release->getReleaseID()]
                        );

                        echo '<a class="btn" ID="cancel_release" name="cancel" href="' . $cancel_url . '">' .
                            $hp->purify($GLOBALS['Language']->getText('global', 'btn_cancel'))
                        . '</a>';
                    ?>
                </TD>
            </TR>
        </TABLE>
    </FORM>

    <?php

    file_utils_footer([]);
}

function frs_process_release_form($is_update, $request, $group_id, $title, $url)
{
    global $package_factory, $release_factory, $files_factory;

    $project = ProjectManager::instance()->getProject($group_id);

    //get and filter all inputs from $request
    $release     = [];
    $res         = $request->get('release');
    $vName       = new Valid_String();
    $vPackage_id = new Valid_UInt();
    $vStatus_id  =  new Valid_UInt();

    if (
        $vName->validate($res['name']) &&
        $vPackage_id->validate($res['package_id']) &&
        $vStatus_id->validate($res['status_id'])
    ) {
        $release['status_id']  = $res['status_id'];
        $release['name']       = $res['name'];
        $release['package_id'] = $res['package_id'];
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed'));
        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id=' . $group_id);
    }

    $um   = UserManager::instance();
    $user = $um->getCurrentUser();

    $vDate = new Valid_String();
    if ($vDate->validate($res['date'])) {
        $release['date'] = $res['date'];
    } else {
        $release['date'] = "";
    }

    $vRelease_notes = new Valid_Text();
    if ($vRelease_notes->validate($res['release_notes'])) {
        $release['release_notes'] = $res['release_notes'];
    } else {
        $release['release_notes'] = "";
    }

    $vChange_log = new Valid_Text();
    if ($vChange_log->validate($res['change_log'])) {
        $release['change_log'] = $res['change_log'];
    } else {
        $release['change_log'] = "";
    }

    if ($request->valid(new Valid_String('js'))) {
        $js = $request->get('js');
    } else {
        $js = "";
    }

    if ($request->validArray(new Valid_String('ftp_file'))) {
        $ftp_file = $request->get('ftp_file');
    } else {
        $ftp_file = [];
    }

    if ($request->validArray(new Valid_UInt('file_processor'))) {
        $file_processor = $request->get('file_processor');
    } else {
        $file_processor = [];
    }

    if ($request->validArray(new Valid_UInt('file_type'))) {
        $file_type = $request->get('file_type');
    } else {
        $file_type = [];
    }

    if ($request->validArray(new Valid_String('reference_md5'))) {
        $reference_md5 = $request->get('reference_md5');
    } else {
        $reference_md5 = [];
    }

    if ($request->validArray(new Valid_String('comment'))) {
        $comment = $request->get('comment');
    } else {
        $comment = [];
    }

    if ($request->validArray(new Valid_UInt('ftp_file_processor'))) {
        $ftp_file_processor = $request->get('ftp_file_processor');
    } else {
        $ftp_file_processor = [];
    }

    if ($request->validArray(new Valid_UInt('ftp_file_type'))) {
        $ftp_file_type = $request->get('ftp_file_type');
    } else {
        $ftp_file_type = [];
    }

    if ($request->validArray(new Valid_String('ftp_reference_md5'))) {
        $ftp_reference_md5 = $request->get('ftp_reference_md5');
    } else {
        $ftp_reference_md5 = [];
    }

    if ($request->valid(new Valid_String('release_news_subject'))) {
        $release_news_subject = $request->get('release_news_subject');
    } else {
        $release_news_subject = "";
    }

    if ($request->valid(new Valid_Text('release_news_details'))) {
        $release_news_details = $request->get('release_news_details');
    } else {
        $release_news_details = "";
    }

    if ($request->valid(new Valid_WhiteList('private_news', [0, 1]))) {
        $private_news = $request->get('private_news');
    } else {
        $private_news = 0;
    }

    if ($project->usesService(Service::NEWS) && $request->valid(new Valid_WhiteList('release_submit_news', [0, 1]))) {
        $release_submit_news = (int) $request->get('release_submit_news');
    } else {
        $release_submit_news = 0;
    }

    if ($request->valid(new Valid_WhiteList('notification', [0, 1]))) {
        $notification = $request->get('notification');
    } else {
        $notification = 0;
    }

    if ($is_update) {
        if ($request->validArray(new Valid_UInt('release_files_to_delete'))) {
            $release_files_to_delete = $request->get('release_files_to_delete');
        } else {
            $release_files_to_delete = [];
        }
        $release_links_to_delete = [];
        if ($request->validArray(new Valid_UInt('release_links_to_delete'))) {
            $release_links_to_delete = $request->get('release_links_to_delete');
        }

        if ($request->validArray(new Valid_UInt('release_files'))) {
            $release_files = $request->get('release_files');
        } else {
            $release_files = [];
        }

        if ($request->validArray(new Valid_UInt('release_file_processor'))) {
            $release_file_processor = $request->get('release_file_processor');
        } else {
            $release_file_processor = [];
        }

        if ($request->validArray(new Valid_UInt('release_file_type'))) {
            $release_file_type = $request->get('release_file_type');
        } else {
            $release_file_type = [];
        }

        if ($request->validArray(new Valid_String('release_reference_md5'))) {
            $release_reference_md5 = $request->get('release_reference_md5');
        } else {
            $release_reference_md5 = [];
        }
        if ($request->validArray(new Valid_UInt('new_release_id'))) {
            $new_release_id = $request->get('new_release_id');
        } else {
            $new_release_id = [];
        }

        if ($request->validArray(new Valid_String('release_time'))) {
            $release_time = $request->get('release_time');
        } else {
            $release_time = [];
        }

        if ($request->validArray(new Valid_String('reference_md5'))) {
            $reference_md5 = $request->get('reference_md5');
        } else {
            $reference_md5 = [];
        }

        if ($request->validArray(new Valid_Text('release_comment'))) {
            $release_comment = $request->get('release_comment');
        } else {
            $release_comment = [];
        }

        if ($request->valid(new Valid_UInt('id'))) {
            $release['release_id'] = $request->get('id');
        } else {
            exit;
        }
    }

    $warning = [];
    $error   = [];
    $info    = [];

    $validator = new FRSValidator();

    if ($is_update) {
        $valid = $validator->isValidForUpdate($release, $group_id);
    } else {
        $valid = $validator->isValidForCreation($release, $group_id);
    }
    if ($valid) {
        //uplaod release_notes and change_log if needed
        $data_uploaded = false;
        if (isset($_FILES['uploaded_change_log']) && ! $_FILES['uploaded_change_log']['error']) {
            $code = addslashes(fread(fopen($_FILES['uploaded_change_log']['tmp_name'], 'r'), \filesize($_FILES['uploaded_change_log']['tmp_name'])));
            if ((strlen($code) > 0) && (strlen($code) < ForgeConfig::get('sys_max_size_upload'))) {
                //size is fine
                $info[]                = $GLOBALS['Language']->getText('file_admin_editreleases', 'data_uploaded');
                $data_uploaded         = true;
                $release['change_log'] = $code;
            } else {
                //too big or small
                $warning[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'length_err', ForgeConfig::get('sys_max_size_upload'));
            }
        }
        if (isset($_FILES['uploaded_release_notes']) && ! $_FILES['uploaded_release_notes']['error']) {
            $code = addslashes(fread(fopen($_FILES['uploaded_release_notes']['tmp_name'], 'r'), \filesize($_FILES['uploaded_release_notes']['tmp_name'])));
            if ((strlen($code) > 0) && (strlen($code) < ForgeConfig::get('sys_max_size_upload'))) {
                //size is fine
                if (! $data_uploaded) {
                    $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'data_uploaded');
                }
                $release['release_notes'] = $code;
            } else {
                //too big or small
                $warning[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'length_err', ForgeConfig::get('sys_max_size_upload'));
            }
        }

        if ($is_update) {
            // make sure that we don't change the date by error because of timezone reasons.
            // eg: release created in India (GMT +5:30) at 2004-06-03.
            // MLS in Los Angeles (GMT -8) changes the release notes
            // the release_date that we showed MLS is 2004-06-02.
            // with mktime(0,0,0,2,6,2004); we will change the unix time in the database
            // and the people in India will discover that their release has been created on 2004-06-02
            $rel = $release_factory->getFRSReleaseFromDb($release['release_id']);
            if (format_date('Y-m-d', $rel->getReleaseDate()) == $release['date']) {
                // the date didn't change => don't update it
                $unix_release_time = $rel->getReleaseDate();
            } else {
                $date_list         = explode("-", $release['date'], 3);
                $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
            }
        } else {
            //parse the date
            $date_list         = explode("-", $release['date'], 3);
            $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        }

        //now we create or update the release
        $array =  [
            'release_date' => $unix_release_time,
            'name' => $release['name'],
            'status_id' => $release['status_id'],
            'package_id' => $release['package_id'],
            'notes' => $release['release_notes'],
            'changes' => $release['change_log'],
        ];
        if ($is_update) {
            $array['release_id'] = $release['release_id'];
        }
        $release_id = 0;

        if ($is_update) {
            $res = $release_factory->update($array);
            if (! $res) {
                $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed');
            } else {
                $release_id   = $array['release_id'];
                $info_success = $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_updated', $release['name']);
            }
        } else {
            $res = $release_factory->create($array);
            if (! $res) {
                $error[] =  $GLOBALS['Language'] > getText('file_admin_editreleases', 'add_rel_fail');
                //insert failed - go back to definition screen
            } else {
                //release added - now show the detail page for this new release
                $release_id   = $res;
                $rel          = $release_factory->getFRSReleaseFromDb($release_id);
                $info_success = $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_added');
            }
        }
        if ($res && isset($rel)) {
            // extract cross references
            $reference_manager = ReferenceManager::instance();
            $reference_manager->extractCrossRef($release['release_notes'], $release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
            $reference_manager->extractCrossRef($release['change_log'], $release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);

            //set the release permissions
            $ugroups = [];
            if ($request->get('ugroups')) {
                $ugroups = $request->get('ugroups');
            }
            /** @psalm-suppress DeprecatedFunction */
            [$return_code, $feedbacks] = permission_process_selection_form($group_id, 'RELEASE_READ', $release_id, $ugroups);
            if (! $return_code) {
                $error[] = $GLOBALS['Language']->getText('file_admin_editpackages', 'perm_update_err');
                $error[] = $feedbacks;
            }

            //submit news if requested
            if ($release_id && user_ismember($group_id, 'A') && $release_submit_news) {
                require_once __DIR__ . '/../news/news_utils.php';
                news_submit($group_id, $release_news_subject, $release_news_details, $private_news, false);
            }

            // Send notification
            if ($notification) {
                $count = $release_factory->emailNotification($rel);
                if ($count === false) {
                    $error[] =  $GLOBALS['Language']->getText('global', 'mail_failed', [
                        ForgeConfig::get('sys_email_admin'),
                    ]);
                } else {
                    if ($count > 0) {
                        $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'email_sent', $count);
                    }
                }
            }

            if (isset($release_files_to_delete, $release_files) && $is_update) {
                $files = $rel->getFiles();

                //remove files
                foreach ($release_files_to_delete as $rel_file) {
                    $res   = $files_factory->getFRSFileFromDb($rel_file);
                    $fname = $res->getFileName();
                    $res   = $files_factory->delete_file($group_id, $rel_file);
                    if ($res == 0) {
                        $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'f_not_yours', basename($fname));
                    } else {
                        $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'file_deleted', basename($fname));
                    }
                }

                //update files
                $index = 0;
                foreach ($release_files as $rel_file) {
                    if (empty($release_files_to_delete) || ! in_array($rel_file, $release_files_to_delete)) {
                        $package_id = $release['package_id'];
                        $fname      = $files[$index]->getFileName();
                        $list       = explode('/', $fname);
                        $fname      = $list[sizeof($list) - 1];
                        if (! isset($new_release_id)) {
                            continue;
                        }
                        if ($new_release_id[$index] != $release_id) {
                            //changing to a different release for this file
                            //see if the new release is valid for this project
                            $res2 = $release_factory->getFRSReleaseFromDb($new_release_id[$index], $group_id);
                            if (! $res2 || count($res2) < 1) {
                                //release not found for this project
                                $warning[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_not_yours', $fname);
                            } else {
                                $package_id = $res2->getPackageID();
                            }
                        }
                        if ($new_release_id[$index] == $release_id || (isset($res2) && $res2)) {
                            if (! isset($release_time)) {
                                continue;
                            }
                            if (! preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $release_time[$index])) {
                                $warning[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'data_not_parsed_file', $fname);
                            } else {
                                $res2 = $files_factory->getFRSFileFromDb($rel_file);

                                if (format_date('Y-m-d', $res2->getReleaseTime()) == $release_time[$index]) {
                                    $unix_release_time = $res2->getReleaseTime();
                                } else {
                                    $date_list = explode("-", $release_time[$index], 3);
                                    assert(isset($date_list[1], $date_list[2], $date_list[0]));
                                    $unix_release_time = mktime(0, 0, 0, (int) $date_list[1], (int) $date_list[2], (int) $date_list[0]);
                                }
                                if (! isset($release_file_type, $release_file_processor, $release_comment, $release_reference_md5)) {
                                    continue;
                                }

                                $array =  [
                                    'release_id'    => $new_release_id[$index],
                                    'release_time'  => $unix_release_time,
                                    'type_id'       => $release_file_type[$index],
                                    'processor_id'  => $release_file_processor[$index],
                                    'file_id'       => $rel_file,
                                    'comment'       => $release_comment[$index],
                                    'filename'      => 'p' . $package_id . '_r' . $new_release_id[$index] . '/' . $fname,
                                    'filepath'      => 'p' . $package_id . '_r' . $new_release_id[$index] . '/' . $fname . '_' . $unix_release_time,
                                ];
                                if ($release_reference_md5[$index] && $release_reference_md5[$index] != '') {
                                    $array['reference_md5'] = $release_reference_md5[$index];
                                }
                                $res = $files_factory->update($array);
                                if ($res) {
                                    $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'file_updated', $fname);
                                }
                            }
                        }
                    }
                    $index++;
                }
            }

            if (! empty($release_links_to_delete)) {
                $uploaded_links_deletor = new \Tuleap\FRS\UploadedLinkDeletor(new UploadedLinksDao(), FRSLog::instance());
                $uploaded_links_deletor->deleteByIDsAndRelease($release_links_to_delete, $rel, $user);
            }

            $uploaded_links_updater   = new UploadedLinksUpdater(new UploadedLinksDao(), FRSLog::instance());
            $uploaded_links_formatter = new UploadedLinksRequestFormatter();
            try {
                $release_links = $uploaded_links_formatter->formatFromRequest($request);
                $uploaded_links_updater->update($release_links, $user, $rel, $rel->getReleaseDate());
            } catch (UploadedLinksInvalidFormException $e) {
                $error[] = _('An error occurred in form submission, a link is not valid. Please retry.');
            }

            $http_files_processor_type_list =  [];
            $ftp_files_processor_type_list  =  [];
            if (isset($js) && $js == 'no_js') {
                //if javascript is not allowed, there is maximum one file to upload
                // TODO : fix warnings due to array instead of string for "file_processor", "file_type" & "reference_md5"
                if ($ftp_file[0] != -1) {
                    $ftp_files_processor_type_list[] =  [
                        'name'          => $ftp_file[0],
                        'processor'     => $file_processor,
                        'type'          => $file_type,
                        'reference_md5' => $reference_md5,
                        'comment'       => $comment,
                    ];
                } elseif (trim($_FILES['file']['name'][0]) != '') {
                    $http_files_processor_type_list[] =  [
                        'error'         => $_FILES['file']['error'][0],
                        'name'          => stripslashes($_FILES['file']['name'][0]),
                        'tmp_name'      => $_FILES['file']['tmp_name'][0],
                        'processor'     => $file_processor,
                        'type'          => $file_type,
                        'reference_md5' => $reference_md5,
                        'comment'       => $comment,
                    ];
                }
            } else {
                //get http files with the associated processor type and file type in allowed javascript case
                $nb_files = isset($_FILES['file']) ? count($_FILES['file']['name']) : 0;
                for ($i = 0; $i < $nb_files; $i++) {
                    if (trim($_FILES['file']['name'][$i]) != '') {
                        $http_files_processor_type_list[] =  [
                            'error'         => $_FILES['file']['error'][$i],
                            'name'          => stripslashes($_FILES['file']['name'][$i]),
                            'tmp_name'      => $_FILES['file']['tmp_name'][$i],
                            'processor'     => $file_processor[$i],
                            'type'          => $file_type[$i],
                            'reference_md5' => $reference_md5[$i],
                            'comment'       => $comment[$i],
                        ];
                    }
                }
                //remove hidden ftp_file input (if the user let the select boxe on --choose file)
                $index = 0;
                foreach ($ftp_file as $file) {
                    if (trim($file) != '') {
                        $ftp_files_processor_type_list[] =  [
                            'name' => $file,
                            'processor' => $ftp_file_processor[$index],
                            'type' => $ftp_file_type[$index],
                            'reference_md5' => $ftp_reference_md5[$index],
                        ];
                        $index++;
                    }
                }
            }

            if (count($http_files_processor_type_list) > 0 || count($ftp_files_processor_type_list) > 0) {
                //see if this release belongs to this project
                $res1 = $release_factory->getFRSReleaseFromDb($release_id, $group_id);
                if ($res1 === null) {
                    //release not found for this project
                    $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_not_yours');
                } else {
                    $addingFiles = false;
                    //iterate and add the http files to the frs_file table
                    foreach ($http_files_processor_type_list as $file) {
                        $filename = $file['name'];
                        if (isset($file['error'])) {
                            switch ($file['error']) {
                                case UPLOAD_ERR_OK:
                                    // all is OK
                                    break;
                                case UPLOAD_ERR_INI_SIZE:
                                case UPLOAD_ERR_FORM_SIZE:
                                    $error[] = $GLOBALS['Language']->getText('global', 'error_upload_size', $file['error']);
                                    break;
                                case UPLOAD_ERR_PARTIAL:
                                    $error[] = $GLOBALS['Language']->getText('global', 'error_upload_partial', $file['error']);
                                    break;
                                case UPLOAD_ERR_NO_FILE:
                                    $error[] = $GLOBALS['Language']->getText('global', 'error_upload_nofile', $file['error']);
                                    break;
                                default:
                                    $error[] = $GLOBALS['Language']->getText('global', 'error_upload_unknown', $file['error']);
                            }
                        }
                        if (is_uploaded_file($file['tmp_name'])) {
                            $uploaddir  = $files_factory->getSrcDir($request->getProject());
                            $uploadfile = $uploaddir . "/" . basename($filename);
                            if (! file_exists($uploaddir) || ! is_writable($uploaddir) || ! move_uploaded_file($file['tmp_name'], $uploadfile)) {
                                $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename);
                            } else {
                                $newFile = new FRSFile();
                                $newFile->setRelease($res1);
                                $newFile->setFileName($filename);
                                $newFile->setProcessorID($file['processor']);
                                $newFile->setTypeID($file['type']);
                                $newFile->setReferenceMd5($file['reference_md5']);
                                $newFile->setUserId($user->getId());
                                $newFile->setComment($file['comment']);
                                try {
                                    $files_factory->createFile($newFile);
                                    $addingFiles = true;
                                } catch (Exception $e) {
                                    $error[] = $e->getMessage();
                                }
                            }
                        } else {
                            $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename);
                        }
                    }

                    //iterate and add the ftp files to the frs_file table
                    foreach ($ftp_files_processor_type_list as $file) {
                        $filename = $file['name'];

                        $newFile = new FRSFile();
                        $newFile->setRelease($res1);
                        $newFile->setFileName($filename);
                        $newFile->setProcessorID($file['processor']);
                        $newFile->setTypeID($file['type']);
                        $newFile->setReferenceMd5($file['reference_md5']);
                        $newFile->setUserId($user->getId());

                        try {
                            $files_factory->createFile($newFile, ~FRSFileFactory::COMPUTE_MD5);
                            $addingFiles = true;
                            $em          = EventManager::instance();
                            $em->processEvent(Event::COMPUTE_MD5SUM, ['fileId' => $newFile->getFileID()]);
                            $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'offline_md5', $filename);
                        } catch (Exception $e) {
                            $error[] = $e->getMessage();
                        }
                    }
                }
                if (isset($addingFiles) && $addingFiles) {
                    $info[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'add_files');
                }
            }

            $error_edit = '';
            $params     = [
                'release_id'      => $release_id,
                'release_request' => $request->get('release'),
                'error'           => &$error_edit,
            ];

            EventManager::instance()->processEvent(
                'frs_process_edit_form',
                $params
            );

            if ($error_edit) {
                $error[] = $error_edit;
            }
        }
    } else {
        $error[] = $validator->getErrors();
    }

    foreach ($warning as $warning_message) {
        $GLOBALS['Response']->addFeedback('warning', $warning_message);
    }

    foreach ($info as $info_message) {
        $GLOBALS['Response']->addFeedback('info', $info_message);
    }

    if (count($error) === 0 && isset($info_success)) {
        $GLOBALS['Response']->addFeedback('info', $info_success);
        http_build_query(['group_id' => $group_id]);
        $GLOBALS['Response']->redirect('/file/showfiles.php?' . http_build_query(
            ['group_id'   => $group_id, 'show_release_id' => $release_id]
        ));
    } else {
        foreach ($error as $error_message) {
            $GLOBALS['Response']->addFeedback('error', $error_message);
        }

        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id=' . urlencode($group_id));
    }
}

function detectSpecialCharactersInName($name, $type)
{
    if (preg_match('/\+/', $name)) {
        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_showfiles', 'warn_chars', [$type, $name]));
    }
}
