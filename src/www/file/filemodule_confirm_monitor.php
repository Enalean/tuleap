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
require_once('common/frs/FileModuleMonitorFactory.class.php');

if (user_isloggedin()) {
    $vFilemodule_id = new Valid_UInt('filemodule_id');
    $vFilemodule_id->required();
    if($request->valid($vFilemodule_id)) {
        $filemodule_id = $request->get('filemodule_id');
        $pm = ProjectManager::instance();
        file_utils_header(array('title' => $Language->getText('file_showfiles', 'file_p_for', $pm->getProject($group_id)->getPublicName())));
        // @TODO: Display form in different way depending if the user has admin permissions or not
        echo '<form>';
        // @TODO: i18n
        echo 'Monitor anonymously (uncheck to inform admins that you are monitoring this package)';
        echo '<input type="checkbox" id="anonymous_frs_monitoring" name="anonymous_frs_monitoring" checked="checked" />';
        echo '<br />';
        echo '<a HREF="/file/filemodule_monitor.php?filemodule_id='.$filemodule_id.'">'.$GLOBALS['HTML']->getImage("ic/notification_start.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home', 'start_monitoring'), 'title'=>$GLOBALS['Language']->getText('include_project_home', 'start_monitoring'))).'</a>';
        echo '</form>';
        file_utils_footer($params);
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','choose_p'));
    }
} else {
    exit_not_logged_in();
}

?>