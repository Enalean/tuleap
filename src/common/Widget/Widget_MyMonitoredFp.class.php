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

require_once __DIR__ . '/../../www/my/my_utils.php';

/**
* Widget_MyMonitoredFp
*
* Filemodules that are actively monitored
*/
class Widget_MyMonitoredFp extends Widget
{

    public function __construct()
    {
        parent::__construct('mymonitoredfp');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_files');
    }
    public function getContent()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $frsrf = new FRSReleaseFactory();
        $html_my_monitored_fp = '';
        $sql = "SELECT groups.group_name,groups.group_id " .
            "FROM groups,filemodule_monitor,frs_package " .
            "WHERE groups.group_id=frs_package.group_id " .
            "AND frs_package.status_id !=" . db_ei($frsrf->STATUS_DELETED) . " " .
            "AND frs_package.package_id=filemodule_monitor.filemodule_id " .
            "AND filemodule_monitor.user_id='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "' ";
        $um = UserManager::instance();
        $current_user = $um->getCurrentUser();
        if ($current_user->isRestricted()) {
            $projects = $current_user->getProjects();
            $sql .= "AND groups.group_id IN (" . db_ei_implode($projects) . ") ";
        }
        $sql .= "GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

        $result = db_query($sql);
        $rows = db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_monitored_fp .= $GLOBALS['Language']->getText('my_index', 'my_files_msg');
        } else {
            $html_my_monitored_fp .= '<table class="tlp-table" style="width:100%">';
            $request = HTTPRequest::instance();
            for ($j = 0; $j < $rows; $j++) {
                $group_id = db_result($result, $j, 'group_id');

                $sql2 = "SELECT frs_package.name,filemodule_monitor.filemodule_id " .
                    "FROM groups,filemodule_monitor,frs_package " .
                    "WHERE groups.group_id=frs_package.group_id " .
                    "AND groups.group_id=" . db_ei($group_id) . " " .
                    "AND frs_package.status_id !=" . db_ei($frsrf->STATUS_DELETED) . " " .
                    "AND frs_package.package_id=filemodule_monitor.filemodule_id " .
                    "AND filemodule_monitor.user_id='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "'  LIMIT 100";
                $result2 = db_query($sql2);
                $rows2 = db_numrows($result2);

                $vItemId = new Valid_UInt('hide_item_id');
                $vItemId->required();
                if ($request->valid($vItemId)) {
                    $hide_item_id = $request->get('hide_item_id');
                } else {
                    $hide_item_id = null;
                }

                $vFrs = new Valid_WhiteList('hide_frs', array(0, 1));
                $vFrs->required();
                if ($request->valid($vFrs)) {
                    $hide_frs = $request->get('hide_frs');
                } else {
                    $hide_frs = null;
                }

                list($hide_now,$count_diff,$hide_url) = my_hide_url('frs', $group_id, $hide_item_id, $rows2, $hide_frs, $request->get('dashboard_id'));

                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '') .
                    $hide_url . '<A HREF="/project/?group_id=' . $group_id . '">' .
                    $purifier->purify(db_result($result, $j, 'group_name')) . '</A>&nbsp;&nbsp;&nbsp;&nbsp;';

                $html = '';
                $count_new = max(0, $count_diff);
                for ($i = 0; $i < $rows2; $i++) {
                    if (!$hide_now) {
                        $html .= '
                        <TR class="' . util_get_alt_row_color($i) . '">' .
                            '<TD WIDTH="99%">&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;<A HREF="/file/showfiles.php?group_id=' . $group_id . '">' .
                            db_result($result2, $i, 'name') . '</A></TD>' .
                            '<TD><A HREF="/file/filemodule_monitor.php?filemodule_id=' .
                            db_result($result2, $i, 'filemodule_id') . '&group_id=' . $group_id .
                            '" onClick="return confirm(\'' . $GLOBALS['Language']->getText('my_index', 'stop_file') . '\')">' .
                            '<i class="fa fa-trash-o" title="' . $GLOBALS['Language']->getText('my_index', 'stop_monitor') . '"></i></A></TD></TR>';
                    }
                }

                $html_hdr .= my_item_count($rows2, $count_new) . '</td></tr>';
                $html_my_monitored_fp .= $html_hdr . $html;
            }
            $html_my_monitored_fp .= '</table>';
        }
        return $html_my_monitored_fp;
    }

    public function getCategory()
    {
        return _('Files');
    }
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_my_monitored_fp', 'description');
    }
    public function isAjax()
    {
        return true;
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request  = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type, $dashboard_id);
        if ($request->exist('hide_item_id') || $request->exist('hide_frs')) {
            $ajax_url .= '&hide_item_id=' . urlencode($request->get('hide_item_id')) .
                '&hide_frs=' . urlencode($request->get('hide_frs'));
        }

        return $ajax_url;
    }
}
