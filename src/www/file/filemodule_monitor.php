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
        $editContent = "";
        if ($frspf->userCanAdmin($user, $group_id)) {
            if ($request->valid(new Valid_WhiteList('action', array('monitor_package', 'add_monitoring','delete_monitoring')))) {
                $action = $request->get('action');
                switch ($action) {
                    case 'monitor_package' :
                        if (!$fmmf->isMonitoring($filemodule_id, $user)) {
                            $anonymous  = false;
                            $vAnonymous = $request->get('anonymous_frs_monitoring');
                            if (isset($vAnonymous) && !empty($vAnonymous)) {
                                $anonymous = true;
                            }
                            $result = $fmmf->setMonitor($filemodule_id, $user, $anonymous);
                            if (!$result) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','insert_err'));
                            } else {
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','p_monitored'));
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','now_emails'));
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','turn_monitor_off'), CODENDI_PURIFIER_LIGHT);
                            }
                        } else {
                            $result = $fmmf->stopMonitor($filemodule_id, $user);
                            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','monitor_turned_off'));
                            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','no_emails'));
                        }
                        break;
                    case 'add_monitoring' :
                        $users = array_map('trim', preg_split('/[,;]/', $request->get('listeners_to_add')));
                        foreach ($users as $userName) {
                            $user = $um->findUser($userName);
                            if ($user) {
                                // @TODO: feedback after action
                                $publicly = true;
                                if (!$fmmf->isMonitoring($filemodule_id, $user, $publicly)) {
                                    if ($frspf->userCanRead($group_id, $filemodule_id, $user->getId())) {
                                        $anonymous = false;
                                        $result = $fmmf->setMonitor($filemodule_id, $user, $anonymous);
                                    }
                                }
                            }
                        }
                        break;
                    case 'delete_monitoring' :
                        $users = $request->get('delete_user');
                        if ($users && !empty($users) && is_array($users)) {
                            foreach ($users as $userId) {
                                $user = $um->getUserById($userId);
                                if ($user) {
                                    if (true) {
                                        // @TODO: feedback after action
                                        $onlyPublic = true;
                                        $result = $fmmf->stopMonitor($filemodule_id, $user, $onlyPublic);
                                    }
                                }
                            }
                        }
                        break;
                    default :
                        break;
                }
            }

            // @TODO: i18n
            $editContent = '<h3>Manage list of people monitoring the package</h3>';
            $list    = $fmmf->whoIsPubliclyMonitoringPackage($filemodule_id);
            if ($list->rowCount() == 0) {
                // @TODO: i18n
                $editContent .= 'No users publicly monitoring this package';
            } else {
                $userHelper = new UserHelper();
                $editContent    .= '<form id="filemodule_monitor_form_delete" method="post" >';
                $editContent    .= '<input type="hidden" name="action" value="delete_monitoring">';
                // @TODO: i18n
                $editContent    .= html_build_list_table_top(array('User', 'Delete?'), false, false, false);
                $rowBgColor = 0;
                foreach ($list as $entry) {
                    $user    = $um->getUserById($entry['user_id']);
                    $editContent .= '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td>'.$userHelper->getDisplayName($user->getName(), $user->getRealName()).'</td><td><input type="checkbox" name="delete_user[]" value="'.$entry['user_id'].'" /></td></tr>';
                }
                // @TODO: put correct icon & text
                $editContent .= '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td></td><td><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" /></td></tr>';
                $editContent .= '</table>';
                $editContent .= '</form>';
            }
            $editContent .= '<form id="filemodule_monitor_form_add" method="post" >';
            $editContent .= '<input type="hidden" name="action" value="add_monitoring">';
            $editContent .= '<input type="hidden" name="package_id" value="'.$filemodule_id.'">';
            // @TODO: i18n
            $editContent .= '<h3>Add users to the monitoring list :</h3>';
            $editContent .= '<br /><textarea name="listeners_to_add" value="" id="listeners_to_add" rows="2" cols="50"></textarea>';
            // @TODO: Add this to combined
            $autocomplete = "new UserAutoCompleter('listeners_to_add','".util_get_dir_image_theme()."',true);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($autocomplete);
            // @TODO: put correct icon & text
            $editContent .= '<br /><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
            $editContent .= '</form>';
        }
        // @TODO: i18n
        echo '<h3>Manage my package monitoring</h3>';
        echo '<form id="filemodule_monitor_form" method="post" >';
        echo '<input type="hidden" name="action" value="monitor_package">';
        echo '<input type="hidden" id="filemodule_id" name="filemodule_id" value="'.$filemodule_id.'" />';
        $anonymousOption = '';
        if ($fmmf->isMonitoring($filemodule_id, $user)) {
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
        echo $editContent;
        file_utils_footer($params);
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','choose_p'));
    }
} else {
    exit_not_logged_in();
}

?>