<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once('pre.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');

if (user_isloggedin()) {
    $vFilemodule_id = new Valid_UInt('filemodule_id');
    $vFilemodule_id->required();
    if($request->valid($vFilemodule_id)) {
        $filemodule_id = $request->get('filemodule_id');
        $pm = ProjectManager::instance();
        file_utils_header(array('title' => $Language->getText('file_showfiles', 'file_p_for', $pm->getProject($group_id)->getPublicName())));
        $um    = UserManager::instance();
        $user  = $um->getCurrentUser();
        $frspf = new FRSPackageFactory();
        $fmmf  = new FileModuleMonitorFactory();
        // @TODO: i18n
        echo '<h3>Manage my package monitoring</h3>';
        echo '<form id="filemodule_monitor_form" method="post" action="filemodule_monitor.php" >';
        echo '<input type="hidden" id="filemodule_id" name="filemodule_id" value="'.$filemodule_id.'" />';
        $anonymousOption = '';
        if ($fmmf->isMonitoring($filemodule_id)) {
            $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" />';
        } else {
            // @TODO: i18n
            $anonymousOption .= 'Monitor anonymously (uncheck to inform admins that you are monitoring this package)';
            $anonymousOption .= '<input type="checkbox" id="anonymous_frs_monitoring" name="anonymous_frs_monitoring" checked="checked" /><br />';
            $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
        }
        echo $anonymousOption;
        echo $submit;
        echo '</form>';
        if ($frspf->userCanAdmin($user, $group_id)) {
            // @TODO: i18n
            echo '<h3>Manage list of people monitoring the package</h3>';
            $list = $fmmf->whoIsPubliclyMonitoringPackage($filemodule_id);
            if ($list->rowCount() == 0) {
                // @TODO: i18n
                echo 'No users publicly monitoring this package';
            } else {
                $userHelper = new UserHelper();
                echo '<form id="filemodule_monitor_form_delete" method="post" >';
                echo '<input type="hidden" name="action" value="delete_monitoring">';
                // @TODO: i18n
                echo html_build_list_table_top(array('User', 'Delete?'), false, false, false);
                $rowBgColor = 0;
                foreach ($list as $entry) {
                    $user = $um->getUserById($entry['user_id']);
                    echo '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td>'.$userHelper->getDisplayName($user->getName(), $user->getRealName()).'</td><td><input type="checkbox" name="'.$entry['user_id'].'" /></td></tr>';
                }
                // @TODO: put correct icon & text
                echo '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td></td><td><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" /></td></tr>';
                echo '</table>';
                echo '</form>';
                echo '<form id="filemodule_monitor_form_add" method="post" >';
                echo '<input type="hidden" name="action" value="add_monitoring">';
                echo '<input type="hidden" name="package_id" value="'.$filemodule_id.'">';
                // @TODO: i18n
                echo '<h4>Add users to the monitoring list :</h4>';
                echo '<br /><textarea name="listeners_to_add" value="" id="listeners_to_add" rows="2" cols="50"></textarea>';
                $autocomplete = "new UserAutoCompleter('listeners_to_add','".util_get_dir_image_theme()."',true);";
                $GLOBALS['Response']->includeFooterJavascriptSnippet($autocomplete);
                // @TODO: put correct icon & text
                echo '<br /><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
                echo '</form>';
            }
        }
        file_utils_footer($params);
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','choose_p'));
    }
} else {
    exit_not_logged_in();
}

?>