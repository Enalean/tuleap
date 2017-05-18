<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once('pre.php');
require_once('www/my/my_utils.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Valid_Widget.class.php');
$GLOBALS['HTML']->includeJavascriptFile('/scripts/codendi/LayoutManager.js');
$hp = Codendi_HTMLPurifier::instance();
if (user_isloggedin()) {

    $request        = HTTPRequest::instance();
    $csrk_token     = new CSRFSynchronizerToken('widget_management');
    $layout_manager = new WidgetLayoutManager();
    $vLayoutId      = new Valid_UInt('layout_id');
    $vLayoutId->required();

    $use_new_dashboards = $request->get('dashboard_id') && ForgeConfig::get('sys_use_tlp_in_dashboards');

    $vOwner = new Valid_Widget_Owner('owner');
    $vOwner->required();
    if ($request->valid($vOwner)) {
        $owner = $request->get('owner');
        $owner_id   = (int)substr($owner, 1);
        $owner_type = substr($owner, 0, 1);

        switch($owner_type) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $owner_id = user_getid();

                $title = $Language->getText('my_index', 'title', array( $hp->purify(user_getrealname(user_getid()), CODENDI_PURIFIER_CONVERT_HTML) .' ('.user_getname().')'));
                my_header(array('title'=>$title, 'selected_top_tab' => '/my/'));
                if ($use_new_dashboards) {
                    $layout_manager->displayAvailableWidgetsForNewDashboards(
                        user_getid(),
                        WidgetLayoutManager::OWNER_TYPE_USER,
                        $request->get('dashboard_id'),
                        $csrk_token
                    );
                } else if ($request->valid($vLayoutId)) {
                    $layout_id = $request->get('layout_id');
                    $layout_manager->displayAvailableWidgets(
                        user_getid(),
                        WidgetLayoutManager::OWNER_TYPE_USER,
                        $layout_id,
                        $csrk_token
                    );
                }
                site_footer(array());

                break;
            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $pm = ProjectManager::instance();
                if ($project = $pm->getProject($owner_id)) {
                    $group_id = $owner_id;
                    $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                    $request->params['group_id'] = $group_id; //bad!
                    if (user_ismember($group_id, 'A') || user_is_super_user()) {
                        $title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();
                        site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));
                        if ($use_new_dashboards) {
                            $layout_manager->displayAvailableWidgetsForNewDashboards(
                                $group_id,
                                WidgetLayoutManager::OWNER_TYPE_GROUP,
                                $request->get('dashboard_id'),
                                $csrk_token
                            );
                        } else if ($request->valid($vLayoutId)) {
                            $layout_id = $request->get('layout_id');
                            $layout_manager->displayAvailableWidgets(
                                $group_id,
                                WidgetLayoutManager::OWNER_TYPE_GROUP,
                                $layout_id,
                                $csrk_token
                            );
                        }
                        site_footer(array());
                    } else {
                        $GLOBALS['Response']->redirect('/projects/'.$project->getUnixName().'/');
                    }
                }
                break;
            default:
                break;
        }
    }
} else {
    exit_not_logged_in();
}
?>
