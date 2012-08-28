<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');

if (user_isloggedin()) {
    $vFilemodule_id = new Valid_UInt('filemodule_id');
    $vFilemodule_id->required();
    if ($request->valid($vFilemodule_id)) {
        $filemodule_id = $request->get('filemodule_id');
        $pm            = ProjectManager::instance();
        $um            = UserManager::instance();
        $userHelper    = new UserHelper();
        $user          = $um->getCurrentUser();
        $frspf         = new FRSPackageFactory();
        $fmmf          = new FileModuleMonitorFactory();
        $historyDao    = new ProjectHistoryDao();

        if ($frspf->userCanRead($group_id, $filemodule_id, $user->getId())) {
            if ($request->get('action') == 'monitor_package') {
                if (!$fmmf->isMonitoring($filemodule_id, $user)) {
                    $anonymous  = false;
                    $vAnonymous = $request->get('anonymous_frs_monitoring');
                    if (isset($vAnonymous) && !empty($vAnonymous)) {
                        $anonymous = true;
                    }
                    $result = $fmmf->setMonitor($filemodule_id, $user, $anonymous);
                    if (!$result) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'insert_err'));
                    } else {
                        if (!$anonymous) {
                            $historyDao->groupAddHistory("frs_self_add_monitor_package", $filemodule_id, $group_id);
                        }
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'p_monitored'));
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'now_emails'));
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'turn_monitor_off'), CODENDI_PURIFIER_LIGHT);
                    }
                } else {
                    $result = $fmmf->stopMonitor($filemodule_id, $user);
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'monitor_turned_off'));
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'no_emails'));
                }
            }

            $editContent = "";
            if ($frspf->userCanAdmin($user, $group_id)) {
                if ($request->valid(new Valid_WhiteList('action', array('add_monitoring', 'delete_monitoring')))) {
                    $action = $request->get('action');
                    switch ($action) {
                        case 'add_monitoring' :
                            $users = array_map('trim', preg_split('/[,;]/', $request->get('listeners_to_add')));
                            foreach ($users as $userName) {
                                if (!empty($userName)) {
                                    $user = $um->findUser($userName);
                                    if ($user) {
                                        $publicly = true;
                                        if ($frspf->userCanRead($group_id, $filemodule_id, $user->getId())) {
                                            if (!$fmmf->isMonitoring($filemodule_id, $user, $publicly)) {
                                                $anonymous = false;
                                                $result = $fmmf->setMonitor($filemodule_id, $user, $anonymous);
                                                if ($result) {
                                                    $historyDao->groupAddHistory("frs_add_monitor_package", $filemodule_id."_".$user->getId(), $group_id);
                                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'monitoring_added', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                                } else {
                                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'insert_err'));
                                                }
                                            } else {
                                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_filemodule_monitor', 'already_monitoring', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'user_no_permission', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                        }
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'no_user', array($userName)));
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
                                        $publicly = true;
                                        if ($fmmf->isMonitoring($filemodule_id, $user, $publicly)) {
                                            $onlyPublic = true;
                                            $result = $fmmf->stopMonitor($filemodule_id, $user, $onlyPublic);
                                            if ($result) {
                                                $historyDao->groupAddHistory("frs_stop_monitor_package", $filemodule_id."_".$user->getId(), $group_id);
                                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'deleted', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            } else {
                                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'delete_error', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'not_monitoring', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                        }
                                    }
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_filemodule_monitor', 'no_delete'));
                            }
                            break;
                        default :
                            break;
                    }
                }

                $editContent = '<h3>'.$Language->getText('file_filemodule_monitor', 'list_title').'</h3>';
                $list = $fmmf->whoIsPubliclyMonitoringPackage($filemodule_id);
                if ($list->rowCount() == 0) {
                    $editContent .= $Language->getText('file_filemodule_monitor', 'no_list');
                } else {
                    $editContent    .= '<form id="filemodule_monitor_form_delete" method="post" >';
                    $editContent    .= '<input type="hidden" name="action" value="delete_monitoring">';
                    $editContent    .= html_build_list_table_top(array($Language->getText('file_filemodule_monitor', 'user'), $Language->getText('global', 'btn_delete').'?'), false, false, false);
                    $rowBgColor = 0;
                    foreach ($list as $entry) {
                        $user    = $um->getUserById($entry['user_id']);
                        $editContent .= '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td>'.$userHelper->getDisplayName($user->getName(), $user->getRealName()).'</td><td><input type="checkbox" name="delete_user[]" value="'.$entry['user_id'].'" /></td></tr>';
                    }
                    $editContent .= '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'"><td></td><td><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" /></td></tr>';
                    $editContent .= '</table>';
                    $editContent .= '</form>';
                }
                $editContent .= '<form id="filemodule_monitor_form_add" method="post" >';
                $editContent .= '<input type="hidden" name="action" value="add_monitoring">';
                $editContent .= '<input type="hidden" name="package_id" value="'.$filemodule_id.'">';
                $editContent .= '<h3>'.$Language->getText('file_filemodule_monitor', 'add_users').'</h3>';
                $editContent .= '<br /><textarea name="listeners_to_add" value="" id="listeners_to_add" rows="2" cols="50"></textarea>';
                $autocomplete = "new UserAutoCompleter('listeners_to_add', '".util_get_dir_image_theme()."', true);";
                $GLOBALS['Response']->includeFooterJavascriptSnippet($autocomplete);
                $editContent .= '<br /><input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
                $editContent .= '</form>';
            }
            file_utils_header(array('title' => $Language->getText('file_showfiles', 'file_p_for', $pm->getProject($group_id)->getPublicName())));
            echo '<h3>'.$Language->getText('file_filemodule_monitor', 'my_monitoring').'</h3>';
            echo '<form id="filemodule_monitor_form" method="post" >';
            echo '<input type="hidden" name="action" value="monitor_package">';
            echo '<input type="hidden" id="filemodule_id" name="filemodule_id" value="'.$filemodule_id.'" />';
            $anonymousOption = '';
            if ($fmmf->isMonitoring($filemodule_id, $user)) {
                $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" />';
            } else {
                $anonymousOption .= $Language->getText('file_filemodule_monitor', 'anonymous');
                $anonymousOption .= '<input type="checkbox" id="anonymous_frs_monitoring" name="anonymous_frs_monitoring" checked="checked" /><br />';
                $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
            }
            echo $anonymousOption;
            echo $submit;
            echo '</form>';
            echo $editContent;
            file_utils_footer($params);
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'no_permission'));
            $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'choose_p'));
        $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
    }
} else {
    exit_not_logged_in();
}

?>