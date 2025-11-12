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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

function file_utils_header(array $params, BreadCrumbCollection $breadcrumbs): void
{
    global $group_id;

    $GLOBALS['Response']->addJavascriptAsset(new JavascriptViteAsset(
        new IncludeViteAssets(
            __DIR__ . '/../../scripts/frs/frontend-assets',
            '/assets/core/frs',
        ),
        'src/frs.ts',
    ));

    if (! array_key_exists('pv', $params) || ! $params['pv']) {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($group_id);

        $service = $project->getService(Service::FILE);
        if ($service !== null) {
            assert($service instanceof ServiceFile);
            $service->displayFRSHeader($project, $params['title'], $breadcrumbs);
        }
    }

    echo '<h1 class="project-administration-title">' . Codendi_HTMLPurifier::instance()->purify($params['title']) . '</h1>';
    echo '<div class="tlp-framed">';
}

function file_utils_footer($params)
{
    echo '</div>';
    site_project_footer($params);
}

/*

 The following functions are for the FRS (File Release System)
 They were moved here from project_admin_utils.php since they can
 now be used by non-admins (e.g. file releases admins)

*/

/*

    pop-up box of supported frs filetypes

*/

function frs_show_filetype_popup(array $filetypes, $checked_val = 'xzxz')
{
    $purifier = Codendi_HTMLPurifier::instance();
    $html     = '<select class="tlp-select tlp-select-adjusted tlp-select-small" name="release_file_type[]">
        <option value="100">' . $purifier->purify($GLOBALS['Language']->getText('file_file_utils', 'must_choose_one')) . '</option>';
    foreach ($filetypes as $row) {
        $selected = (int) $row['id'] === (int) $checked_val ? 'selected' : '';
        $html    .= '<option value="' . $purifier->purify($row['id']) . '" ' . $selected . '>' . $purifier->purify($row['name']) . '</option>';
    }
    $html .= '</select>';

    return $html;
}

/*

    pop-up box of supported frs processor options

*/

function frs_show_processor_popup(array $processors, $checked_val = 'xzxz')
{
    $purifier = Codendi_HTMLPurifier::instance();
    $html     = '<select class="tlp-select tlp-select-adjusted tlp-select-small" name="release_file_processor[]">
        <option value="100">' . $purifier->purify($GLOBALS['Language']->getText('file_file_utils', 'must_choose_one')) . '</option>';
    foreach ($processors as $row) {
        $selected = (int) $row['id'] === (int) $checked_val ? 'selected' : '';
        $html    .= '<option value="' . $purifier->purify($row['id']) . '" ' . $selected . '>' . $purifier->purify($row['name']) . '</option>';
    }
    $html .= '</select>';

    return $html;
}


/*

    pop-up box of packages:releases for this group

*/

function frs_show_release_popup2($group_id, $name = 'release_id', $checked_val = 'xzxz')
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

        $select = '<select name="' . $hp->purify($name) . '" class="tlp-select tlp-select-adjusted tlp-select-small">';
        foreach ($p as $package_name => $releases) {
            $select .= '<optgroup label="' . $hp->purify($package_name) . '">';
            foreach ($releases as $id => $name) {
                $select .= '<option value="' . $hp->purify($id) . '" ' . ($id == $checked_val ? 'selected="selected"' : '') . '>' . $hp->purify($name) . '</option>';
            }
            $select .= '</optgroup>';
        }
        $select .= '</select>';
        return $select;
    }
}

function file_utils_show_processors($result, CSRFSynchronizerToken $csrf_token): void
{
    global $group_id,$Language;
    $hp   = Codendi_HTMLPurifier::instance();
    $rows =  db_numrows($result);

    echo '<table class="tlp-table">
        <thead>
            <tr>
                <th>' . $hp->purify($Language->getText('file_file_utils', 'proc_name')) . '</th>
                <th>' . $hp->purify($Language->getText('file_file_utils', 'proc_rank')) . '</th>
                <th></th>
            </tr>
        </thead>
        <tbody>';

    for ($j = 0; $j < $rows; $j++) {
        $proc_id   = db_result($result, $j, 'processor_id');
        $proc_name = db_result($result, $j, 'name');
        $proc_rank = db_result($result, $j, 'rank');
        $gr_id     = db_result($result, $j, 'group_id');

        echo '<tr class="' . html_get_alt_row_color($j) . '">' . "\n";

        echo '<td>' . $hp->purify($proc_name) . '</td>';
        echo '<td>' . $hp->purify($proc_rank) . '</td>';

        if ($gr_id == '100') {
            // pre-defined processors are not manageable
            echo '<td class="tlp-table-cell-actions"></td>';
        } else {
            echo '<td class="tlp-table-cell-actions">
                <a href="/file/admin/editproc.php?group_id=' . urlencode((string) $group_id) . '&proc_id=' . urlencode((string) $proc_id) . '"
                    class="tlp-table-cell-actions-button tlp-button-primary tlp-button-outline tlp-button-small"
                >
                    <i class="tlp-button-icon fa-solid fa-pencil" aria-hidden="true"></i>
                    ' . _('Edit') . '
                </a>
                <form method="post" onsubmit="return confirm(\'' . $Language->getText('file_file_utils', 'del_proc') . '\')" style="display: inline;">
                    ' . $csrf_token->fetchHTMLInput() . '
                    <input type="hidden" name="mode" value="delete">
                    <input type="hidden" name="group_id" value="' . $hp->purify((string) $group_id) . '">
                    <input type="hidden" name="proc_id" value="' . $hp->purify((string) $proc_id) . '">
                    <button
                        type="submit"
                        class="tlp-table-cell-actions-button tlp-button-danger tlp-button-outline tlp-button-small"
                    >
                         <i class="tlp-button-icon fa-regular fa-trash-alt" aria-hidden="true"></i>
                         ' . _('Delete') . '
                    </button>
                </form>
            </td>';
        }

        echo '</tr>';
    }
    echo '</tbody></table>';
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
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $Language->getText('file_file_utils', 'add_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('file_file_utils', 'add_proc_fail'));
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
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $Language->getText('file_file_utils', 'update_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('file_file_utils', 'update_proc_fail'));
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
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $Language->getText('file_file_utils', 'delete_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('file_file_utils', 'delete_proc_fail'));
    }
}

function frs_display_package_form(FRSPackage $package, string $title, string $url_submit, array $siblings, string $url_frs_home): void
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

    $csrf_token = new CSRFSynchronizerToken('/file/?group_id=' . urlencode((string) $project->getID()));

    file_utils_header(['title' => $title], new BreadCrumbCollection());
    echo <<<EOS
    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="fa-solid fa-plus tlp-pane-title-icon" aria-hidden="true"></i>
                    {$hp->purify($title)}
                </h1>
            </div>
    EOS;
    echo '
    <form action="' . $hp->purify($url_submit) . '" method="post" class="tlp-pane-section">
        ' . $csrf_token->fetchHTMLInput() . '
        <div class="tlp-form-element">
            <label class="tlp-label" for="package">
                ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'p_name')) . '
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>
            <input type="text"
                id="package"
                name="package[name]"
                data-test="frs-create-package"
                size="40"
                required
                class="tlp-input" value="' . $hp->purify(util_unconvert_htmlspecialchars($package->getName())) . '"
            >
        </div>';

    $nb_siblings = count($siblings);
    if ($nb_siblings && ($nb_siblings > 1 || $siblings[0] != $package->getPackageId())) {
        echo '<div class="tlp-form-element">
            <label class="tlp-label" for="rank">
                ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'rank_on_screen')) . '
            </label>
            <select id="rank" name="package[rank]" class="tlp-select tlp-select-adjusted" data-test="status">
                <option value="beginning">' . $hp->purify($GLOBALS['Language']->getText('global', 'at_the_beginning')) . '</option>
                <option value="end">' . $hp->purify($GLOBALS['Language']->getText('global', 'at_the_end')) . '</option>';

        foreach ($siblings as $item) {
            $value = $item['rank'] + 1;

            $selected = '';
            if ($item['id'] === $package->getPackageId()) {
                $selected = 'selected="selected"';
            }
            echo '<option value="' . $hp->purify($value) . '" ' . $selected . '>';
            echo $GLOBALS['Language']->getText('global', 'after', $hp->purify($item['name']));
            echo '</option>';
        }

        echo '</select>
            </div>';
    } else {
        echo '<input type="hidden" name="package[rank]" value="0" />';
    }

    $active_selected = $package->isActive() ? 'selected' : '';
    $hidden_selected = $package->isHidden() ? 'selected' : '';
    echo '<div class="tlp-form-element">
            <label class="tlp-label" for="status">
                ' . $hp->purify(_('Status')) . '
            </label>
            <select id="status" name="package[status_id]" class="tlp-select tlp-select-adjusted" data-test="status">
                <option value="' . $hp->purify(FRSPackage::STATUS_ACTIVE) . '" ' . $active_selected . '>
                    ' . $hp->purify(_('Active')) . '
                </option>
                <option value="' . $hp->purify(FRSPackage::STATUS_HIDDEN) . '" ' . $hidden_selected . '>
                    ' . $hp->purify(_('Hidden')) . '
                </option>
            </select>
        </div>';

    echo $license_agreement_display->getPackageEditSelector($package, $project);

     //We cannot set permission on creation for now
    if ($package->getPackageID()) {
        $package_controller = new FRSPackageController(
            FRSPackageFactory::instance(),
            FRSReleaseFactory::instance(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            PermissionsManager::instance(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
        );

        $package_controller->displayUserGroups($project, FRSPackage::PERM_READ, $package->getPackageID());
    }

    echo '<div class="tlp-pane-section-submit">
            <button type="submit" name="submit" value="1" class="tlp-button-primary" data-test="frs-create-package-button">
                <i class="fa-solid fa-save tlp-button-icon" aria-hidden="true"></i>
                ' . $hp->purify(_('Submit')) . '
            </button>
            <a href="' . $hp->purify($url_frs_home) . '" class="tlp-button-primary tlp-button-outline">
                ' . $hp->purify(_('Cancel')) . '
            </a>
        </div>
    </form>';

    echo '</div></section>';
    file_utils_footer([]);
}

function frs_display_release_form(bool $is_update, FRSRelease $release, int $group_id, string $title, string $subtitle, string $url): void
{
    global $package_factory, $files_factory;
    $hp = Codendi_HTMLPurifier::instance();
    if ($is_update) {
        $files = $release->getFiles();
        if (count($files) > 0) {
            for ($i = 0; $i < count($files); $i++) {
                if (! $files_factory->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_admin_editreleases', 'md5_fail', [basename($files[$i]->getFileName()), $files[$i]->getComputedMd5()]));
                }
            }
        }
    }

    $breadcrumbs  = new BreadCrumbCollection();
    $package_link = '/file/' . urlencode((string) $release->getGroupID()) . '/package/' . urlencode((string) $release->getPackageID());
    $breadcrumbs->addBreadCrumb(new BreadCrumb(
        new BreadCrumbLink($release->getPackage()->getName(), $package_link),
    ));
    file_utils_header(
        [
            'title' => $title,
        ],
        $breadcrumbs,
    );

    $csrf_token = new CSRFSynchronizerToken($package_link);

    echo '<form class="tlp-pane"
        enctype="multipart/form-data"
        method="POST"
        action="' . $url . '"
        id="frs-release-form"
        data-is-update="' . ($is_update ? '1' : '0') . '"
        data-size-error="' . $hp->purify(_('You exceed the max file size')) . '"
        data-project-id="' . $hp->purify($group_id) . '"
    >
        ' . $csrf_token->fetchHTMLInput() . '
        <div class="tlp-pane-container frs-release-form-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="fa-solid fa-pencil tlp-pane-title-icon" aria-hidden="true"></i>
                    ' . $hp->purify($subtitle) . '
                </h1>
            </div>
            <section class="tlp-pane-section">
                <div id="frs-release-feedback" class="tlp-alert-danger frs-release-feedback"></div>';
    ?>

        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<?php echo $hp->purify(ForgeConfig::get('sys_max_size_upload')); ?>">
        <input type="hidden" name="postReceived" value="" />
        <?php
        if ($release->getReleaseId()) {
            echo '<input type="hidden" id="release_id" name="release[release_id]" value="' . $hp->purify($release->getReleaseId()) . '" />';
        }

        echo '<h2>' . $hp->purify(_('Properties')) . '</h2>';

        echo '<div class="tlp-form-element">
        <label class="tlp-label" for="package_id">
            ' . $hp->purify(_('Package')) . '
        </label>';
        $res  = $package_factory->getFRSPackagesFromDb($group_id);
        $rows = count($res);
        if (! $res || $rows < 1) {
            echo '<p class="tlp-text-danger">' . _('No package available') . '</p>';
        } else {
            echo '<select name="release[package_id]" id="package_id" class="tlp-select tlp-select-adjusted" data-project-id="' . $hp->purify($group_id) . '">';
            for ($i = 0; $i < $rows; $i++) {
                echo '<OPTION VALUE="' . $hp->purify($res[$i]->getPackageID()) . '"';
                if ($res[$i]->getPackageID() == $release->getPackageId()) {
                    echo ' selected';
                }
                echo '>' . $hp->purify(util_unconvert_htmlspecialchars($res[$i]->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</OPTION>';
            }
            echo '</select>';
        }
        echo '</div>';

        echo '<div class="tlp-form-element">
        <label class="tlp-label" for="release_name">
            ' . $hp->purify(_('Name')) . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <input type="text"
            class="tlp-input"
            size="40"
            id="release_name"
            name="release[name]"
            data-test="release-name"
            required
            value="' . $hp->purify($release->getName()) . '"
        >
    </div>';

        echo '<div class="tlp-form-element">
        <label class="tlp-label" for="frs-release-date-picker">
            ' . $hp->purify(_('Date')) . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
            </span>
            <input type="text"
                class="tlp-input"
                size="11"
                id="frs-release-date-picker"
                name="release[date]"
                required
                value="' . $hp->purify(format_date('Y-m-d', $release->getReleaseDate())) . '"
            >
        </div>
    </div>';

        $package_factory = new FRSPackageFactory();
        $active_selected = $release->isActive() ? 'selected' : '';
        $hidden_selected = $release->isHidden() ? 'selected' : '';
        echo '<div class="tlp-form-element">
            <label class="tlp-label" for="status">
                ' . $hp->purify(_('Status')) . '
            </label>
            <select id="status" name="release[status_id]" class="tlp-select tlp-select-adjusted">
                <option value="' . $hp->purify(FRSPackage::STATUS_ACTIVE) . '" ' . $active_selected . '>
                    ' . $hp->purify(_('Active')) . '
                </option>
                <option value="' . $hp->purify(FRSPackage::STATUS_HIDDEN) . '" ' . $hidden_selected . '>
                    ' . $hp->purify(_('Hidden')) . '
                </option>
            </select>
        </div>';

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
            echo $additional_info;
        }

        echo '</section>';

        echo '<section class="tlp-pane-section">';
        echo '<h2>' . $hp->purify(_('Uploaded files')) . '</h2>';


        $processors = [];
        foreach (db_query('SELECT * FROM frs_processor WHERE group_id=100 OR group_id=' . db_ei($group_id) . ' ORDER BY `rank`') as $row) {
            $processors[] = [
                'id'    => $row['processor_id'],
                'name'  => $row['name'],
            ];
        }
        $filetypes = [];
        foreach (db_query('SELECT * FROM frs_filetype ORDER BY type_id') as $row) {
            $filetypes[] = [
                'id'   => $row['type_id'],
                'name' => $row['name'],
            ];
        }

        $files              = $release->getFiles();
        $has_files          = count($files) > 0;
        $with_extra_columns = $is_update && $has_files;
        echo '<div class="frs-release-files-container">
            <table class="tlp-table frs-release-files-table ' . ($with_extra_columns ? 'frs-release-files-table-with-extra-columns' : '') . '">
                <thead>
                    <tr>
                        <th>' . $hp->purify(_('Delete')) . '</th>
                        <th class="frs-release-file-name-column">' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'filename')) . '</th>
                        <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'processor')) . '</th>
                        <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'file_type')) . '</th>
                        <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'md5sum')) . '</th>
                        <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'comment')) . '</th>
                        ' . ($with_extra_columns  ? '
                            <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'user')) . '</th>
                            <th>' . $hp->purify(_('Release')) . '</th>
                            <th>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'release_date')) . '</th>'
                            : ''
                        ) . '
                    </tr>
                </thead>
                <tbody id="frs-release-files-table-tbody"
                    class="frs-release-files-table-tbody-files"
                    data-processors="' . $hp->purify(json_encode($processors)) . '"
                    data-filetypes="' . $hp->purify(json_encode($filetypes)) . '"
                    data-choose-label="' . $hp->purify($GLOBALS['Language']->getText('file_file_utils', 'must_choose_one')) . '"
                    data-delete-label="' . $hp->purify(_('Delete')) . '"
                >';

        for ($i = 0; $i < count($files); $i++) {
            $fname    = $files[$i]->getFileName();
            $list     = explode('/', $fname);
            $fname    = $list[sizeof($list) - 1];
            $user_id  = $files[$i]->getUserID();
            $userName = (isset($user_id)) ? UserManager::instance()->getUserById($files[$i]->getUserID())->getRealName() : '';
            echo '<tr class="frs-release-file-row">';
            echo '<TD><INPUT TYPE="CHECKBOX" NAME="release_files_to_delete[]" VALUE="' . $hp->purify($files[$i]->getFileID()) . '" class="frs-release-file-delete-checkbox"></TD>';
            echo '<TD class="frs-release-file-name-column" title="' . $hp->purify($fname) . '">' . $hp->purify($fname) . '<INPUT TYPE="HIDDEN" NAME="release_files[]" VALUE="' . $hp->purify($files[$i]->getFileID()) . '"></TD>';
            echo '<TD>' . frs_show_processor_popup($processors, $files[$i]->getProcessorID()) . '</TD>';
            echo '<TD>' . frs_show_filetype_popup($filetypes, $files[$i]->getTypeID()) . '</TD>';
            //In case of difference between the inserted md5 and the computed one
            //we dispaly an editable text field to let the user insert the right value
            //to avoid the error message next time
            $value = 'value = "' . $hp->purify($files[$i]->getReferenceMd5()) . '"';
            if ($files_factory->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
                $value = 'value = "' . $hp->purify($files[$i]->getComputedMd5()) . '" readonly="true"';
            }
            echo '<TD><INPUT TYPE="TEXT" NAME="release_reference_md5[]" ' . $value . ' SIZE="32" class="tlp-input tlp-input-small"></TD>';
            $comment = $files[$i]->getComment();
            echo '<TD><textarea NAME="release_comment[]" cols="20" rows="1" class="tlp-textarea tlp-textarea-small">' . $hp->purify($comment) . '</textarea></TD>';
            if ($with_extra_columns) {
                echo '<TD><INPUT TYPE="TEXT" NAME="user" value="' . $hp->purify($userName) . '" readonly class="tlp-input tlp-input-small frs-release-file-owner-input"></TD>';
                echo '<TD>' . frs_show_release_popup2($group_id, $name = 'new_release_id[]', $files[$i]->getReleaseID()) . '</TD>';
                echo '<TD><INPUT TYPE="TEXT" NAME="release_time[]" VALUE="' . $hp->purify(format_date('Y-m-d', $files[$i]->getReleaseTime())) . '" SIZE="10" MAXLENGTH="10" class="tlp-input tlp-input-small"></TD>';
            }
            echo '</tr>';
        }
        echo '</tbody>
                <tbody class="frs-release-files-table-tbody-empty">
                    <tr>
                        <td colspan="' . ($with_extra_columns ? 9 : 6) . '" class="tlp-table-cell-empty">
                            ' . $hp->purify(_('No files are part of this release')) . '
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>';

        $file_list        = $files_factory->getUploadedFileNames($release->getProject());
        $has_staging_area = count($file_list) > 0;

        if ($has_staging_area) {
            echo '<div class="tlp-form-element">
                <label class="tlp-label" for="frs-release-staging-area-select">
                    ' . $hp->purify(_('Add file from staging area')) . '
                </label>';
            echo '<select class="tlp-select tlp-select-adjusted" id="frs-release-staging-area-select" data-test="file-selector">';
            echo '<option value="" disabled selected>' . $hp->purify(_('Choose...')) . '</option>';
            foreach ($file_list as $file) {
                echo '<option value="' . $hp->purify($file) . '">' . $hp->purify($file) . '</option>';
            }
            echo '</select>';
            echo '</div>';
        }

        echo '<div class="tlp-form-element">
            <label class="tlp-label" for="frs-release-upload-file-input">
                ' . $hp->purify(_('Upload new file')) . '
            </label>';
        echo '<span class="frs-release-upload-file-input-container"><input type="file" name="file[]" id="frs-release-upload-file-input" data-test="file-input"></span>';
        echo '<p class="tlp-text-info">' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'upload_file_msg', formatByteToMb(ForgeConfig::get('sys_max_size_upload')))) . '</p>';
        echo '</div>';


                echo '</section>';

                echo '<section class="tlp-pane-section">';
                $renderer = TemplateRendererFactory::build()->getRenderer(
                    __DIR__ . '/../../templates/frs/'
                );

                $uploaded_links_retriever = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
                $existing_links           = $uploaded_links_retriever->getLinksForRelease($release);

                $uploaded_link_spresenter_builder = new \Tuleap\FRS\UploadedLinkPresentersBuilder();
                $existing_links_presenter         = $uploaded_link_spresenter_builder->build($existing_links);
                $uploaded_links_create_presenter  = new UploadedLinkUpdateTablePresenter($existing_links_presenter);

                echo $renderer->renderToString('uploaded-links-form', $uploaded_links_create_presenter);

                echo '</section>';

                echo '<section class="tlp-pane-section">';
                echo '<h2>' . $hp->purify(_('Notes')) . '</h2>';

        if ($notes_in_markdown) {
            echo '<p class="tlp-alert-info">
            ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'notes_in_markdown')) . '
        </p>';
        }

                echo '<div class="tlp-form-element">
        <div class="frs-release-notes-label">
            <label class="tlp-label" for="release-note">
                ' . $hp->purify(_('Release notes')) . '
            </label>
            <div class="tlp-switch-with-label-on-right">
                <div class="tlp-switch tlp-switch-mini">
                    <input type="checkbox" id="frs-release-notes-upload-release-note" class="tlp-switch-checkbox">
                    <label for="frs-release-notes-upload-release-note" class="tlp-switch-button"></label>
                </div>
                <label class="tlp-label" for="frs-release-notes-upload-release-note">
                    ' . $hp->purify(_('Upload')) . '
                </label>
            </div>
        </div>
        <input id="uploaded_notes" type="file" name="uploaded_release_notes" class="frs-release-notes-hidden" size="30">
        <textarea id="release-note"
            name="release[release_notes]"
            rows="7"
            class="tlp-textarea"
            data-test="release-note"
        >' . $hp->purify($release->getnotes()) . '</textarea>
    </div>';

                echo '<div class="tlp-form-element">
        <div class="frs-release-notes-label">
            <label class="tlp-label frs-release-notes-label" for="text_area_change_log">
                ' . $hp->purify(_('Change log')) . '
            </label>
            <div class="tlp-switch-with-label-on-right">
                <div class="tlp-switch tlp-switch-mini">
                    <input type="checkbox" id="frs-release-notes-upload-changelog" class="tlp-switch-checkbox">
                    <label for="frs-release-notes-upload-changelog" class="tlp-switch-button"></label>
                </div>
                <label class="tlp-label" for="frs-release-notes-upload-changelog">
                    ' . $hp->purify(_('Upload')) . '
                </label>
            </div>
        </div>
        <input type="file" id="uploaded_change_log" name="uploaded_change_log" class="frs-release-notes-hidden" size="30">
        <textarea id="text_area_change_log"
            name="release[change_log]"
            rows="7"
            class="tlp-textarea"
        >' . $hp->purify($release->getChanges()) . '</textarea>
    </div>';

                echo '</section>';

                echo '<section class="tlp-pane-section">';
                echo '<h2>' . $hp->purify(_('Permissions')) . '</h2>';

        ?>
                <div id="permissions_list">
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
                        );

                        $package_controller->displayUserGroups($project, FRSPackage::PERM_READ, $release->getPackageID());
                    }
                    ?>

                </div>
            </section>
            <?php

            $fmmf  = new FileModuleMonitorFactory();
            $count = count($fmmf->getFilesModuleMonitorFromDb($release->getPackageId()));
            if ($count > 0) {
                echo '<section class="tlp-pane-section">';
                echo '<h2>' . $hp->purify(_('Notification')) . '</h2>';
                echo '<p>' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'users_monitor', $count)) . '</p>';
                echo '<div class="tlp-form-element">
                    <label class="tlp-label tlp-checkbox">
                        <input type="checkbox" name="notification" value="1" checked>
                        ' . $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'mail_file_rel_notice')) . '
                    </label>
                </div>';
                echo '</section>';
            }
            ?>

        <section class="tlp-pane-section tlp-pane-section-submit">
                    <INPUT TYPE="HIDDEN" NAME="create" VALUE="bla">
                    <button
                            type="submit"
                            class="tlp-button-primary"
                            data-test="create-release-button"
                    ><?php echo $is_update ? $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release')) : $hp->purify(_('Create release')); ?>
                    </button>
                    <?php
                        $cancel_url = '/file/' . urlencode((string) $group_id) . '/package/' . urlencode((string) $release->getPackageID());

                        echo '<a class="tlp-button-primary tlp-button-outline" ID="cancel_release" name="cancel" href="' . $cancel_url . '">' .
                            $hp->purify($GLOBALS['Language']->getText('global', 'btn_cancel'))
                        . '</a>';
                    ?>
        </section>
    </div>
</FORM>

    <?php

    file_utils_footer([]);
}

function frs_process_release_form($is_update, \Tuleap\HTTPRequest $request, $group_id, $title, $url): void
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
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed'));
        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id=' . urlencode((string) $project->getID()));
        exit;
    }

    $package_link = '/file/' . urlencode((string) $project->getID()) . '/package/' . urlencode((string) $res['package_id']);

    if (! $request->isPost()) {
        $GLOBALS['Response']->redirect($package_link);
    }
    (new CSRFSynchronizerToken($package_link))->check();

    $um   = UserManager::instance();
    $user = $um->getCurrentUser();

    $vDate = new Valid_String();
    if ($vDate->validate($res['date'])) {
        $release['date'] = $res['date'];
    } else {
        $release['date'] = '';
    }

    $vRelease_notes = new Valid_Text();
    if ($vRelease_notes->validate($res['release_notes'])) {
        $release['release_notes'] = $res['release_notes'];
    } else {
        $release['release_notes'] = '';
    }

    $vChange_log = new Valid_Text();
    if ($vChange_log->validate($res['change_log'])) {
        $release['change_log'] = $res['change_log'];
    } else {
        $release['change_log'] = '';
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

    if ($request->validArray(new Valid_String('ftp_comment'))) {
        $ftp_comment = $request->get('ftp_comment');
    } else {
        $ftp_comment = [];
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
                $date_list         = explode('-', $release['date'], 3);
                $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
            }
        } else {
            //parse the date
            $date_list         = explode('-', $release['date'], 3);
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
                $error[] =  $GLOBALS['Language']->getText('file_admin_editreleases', 'add_rel_fail');
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
                            if (! preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $release_time[$index])) {
                                $warning[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'data_not_parsed_file', $fname);
                            } else {
                                $res2 = $files_factory->getFRSFileFromDb($rel_file);

                                if (format_date('Y-m-d', $res2->getReleaseTime()) == $release_time[$index]) {
                                    $unix_release_time = $res2->getReleaseTime();
                                } else {
                                    $date_list = explode('-', $release_time[$index], 3);
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
                        'comment' => $ftp_comment[$index],
                    ];
                    $index++;
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
                            $uploadfile = $uploaddir . '/' . basename($filename);
                            if (! file_exists($uploaddir) || ! is_writable($uploaddir) || ! move_uploaded_file($file['tmp_name'], $uploadfile)) {
                                $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ': ' . basename($filename);
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
                            $error[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ': ' . basename($filename);
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
                        $newFile->setComment($file['comment']);

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
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $warning_message);
    }

    foreach ($info as $info_message) {
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $info_message);
    }

    if (count($error) === 0 && isset($info_success)) {
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $info_success);
        $GLOBALS['Response']->redirect($package_link);
    } else {
        foreach ($error as $error_message) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $error_message);
        }

        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id=' . urlencode($group_id));
    }
}
