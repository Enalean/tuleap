<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';

$request = HTTPRequest::instance();

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

$vExport = new Valid_WhiteList('export', array('user_groups', 'user_groups_format'));
$vExport->required();
if ($request->valid($vExport)) {
    $export = $request->get('export');
    
    $col_list = array('group', 'username', 'realname');
    $lbl_list = array('group'    => $GLOBALS['Language']->getText('project_export_user_groups', 'user_group'),
                      'username' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_username', array($GLOBALS['sys_name'])),
                      'realname' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_realname'));
    $um  = UserManager::instance();

    switch($export) {
        case 'user_groups':
            $sep = get_csv_separator();
            $eol = "\n";

            $name = 'export_user_groups_'.$project->getUnixName().'.csv';
            header('Content-Disposition: filename='.$name);
            header('Content-Type: text/csv');
            echo build_csv_header($col_list, $lbl_list).$eol;

            $ugs = ugroup_db_get_existing_ugroups($group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
            while($ugrp = db_fetch_array($ugs)) {
                if ($ugrp['ugroup_id'] <= 100) {
                    $sqlUsers = ugroup_db_get_dynamic_members($ugrp['ugroup_id'], false, $group_id);
                } else {
                    $sqlUsers = ugroup_db_get_members($ugrp['ugroup_id']);
                }
                $users = db_query($sqlUsers);
                while ($user = db_fetch_array($users)) {
                    $r = array('group'    => util_translate_name_ugroup($ugrp['name']),
                               'username' => $user['user_name'],
                               'realname' => $um->getUserById($user['user_id'])->getRealname());
                    echo build_csv_record($col_list, $r).$eol;
                }
            }
            break;

        case 'user_groups_format':
            echo '<h3>'.$Language->getText('project_export_user_groups','exp_format').'</h3>';
            echo '<p>'.$Language->getText('project_export_user_groups','exp_format_msg').'</p>';
            
            // Pick-up a random project member
            $sqlUsers = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_MEMBERS'], false, $group_id);
            $users = db_query($sqlUsers);
            $uRow = db_fetch_array($users);
            $user = $um->getUserById($uRow['user_id']);

            $dsc_list = array('group'    => $GLOBALS['Language']->getText('project_export_user_groups', 'user_group_desc'),
                              'username' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_username_desc', array($GLOBALS['sys_name'])),
                              'realname' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_realname_desc'));
            $record   = array('group'    => util_translate_name_ugroup('project_members'),
                              'username' => $user->getName(),
                              'realname' => $user->getRealName());
            display_exported_fields($col_list,$lbl_list,$dsc_list,$record);
            break;
    }
}

?>