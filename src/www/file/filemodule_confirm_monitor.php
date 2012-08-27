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
        $user  = UserManager::instance()->getCurrentUser();
        $frspf = new FRSPackageFactory();
        $fmmf  = new FileModuleMonitorFactory();
        if ($frspf->userCanAdmin($user, $group_id)) {
            // @TODO: Display  list of users publicly monitoring & form to edit that list
        }
        echo '<form id="filemodule_monitor_form" method="post" action="filemodule_monitor.php" >';
        echo '<input type="hidden" id="filemodule_id" name="filemodule_id" value="'.$filemodule_id.'" />';
        $anonymousOption = '';
        if ($fmmf->isMonitoring($filemodule_id)) {
            $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" />';
        } else {
            // @TODO: i18n
            $anonymousOption .= 'Monitor anonymously (uncheck to inform admins that you are monitoring this package)';
            // @TODO: checked is verified from DB & keep default value as checked
            $anonymousOption .= '<input type="checkbox" id="anonymous_frs_monitoring" name="anonymous_frs_monitoring" checked="checked" /><br />';
            $submit = '<input id="filemodule_monitor_submit" type="image" src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
        }
        echo $anonymousOption;
        echo $submit;
        echo '</form>';
        file_utils_footer($params);
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','choose_p'));
    }
} else {
    exit_not_logged_in();
}

?>