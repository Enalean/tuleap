<?php
/**
 * Copyright (c) Enalean SAS, 2017-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once __DIR__ . '/../../include/pre.php';

use Tuleap\User\StatusPresenter;

// Inherited from old .htaccess
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '256M');

$request = HTTPRequest::instance();

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
    exit();
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

    $col_list = array('group', 'username', 'realname', 'email', 'status');
    $lbl_list = array('group'    => $GLOBALS['Language']->getText('project_export_user_groups', 'user_group'),
                      'username' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_username', array($GLOBALS['sys_name'])),
                      'realname' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_realname'),
                      'email'    => _('Email'),
                      'status'   => _('Status'));
    $um  = UserManager::instance();

    switch ($export) {
        case 'user_groups':
            $sep = get_csv_separator();
            $eol = "\n";

            $name = 'export_user_groups_' . $project->getUnixName() . '.csv';
            header('Content-Disposition: filename=' . $name);
            header('Content-Type: text/csv');
            echo build_csv_header($col_list, $lbl_list) . $eol;

            /** @psalm-suppress DeprecatedFunction */
            $ugs = ugroup_db_get_existing_ugroups($group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
            while ($ugrp = db_fetch_array($ugs)) {
                if ($ugrp['ugroup_id'] <= 100) {
                    $sqlUsers = ugroup_db_get_dynamic_members($ugrp['ugroup_id'], false, $group_id, false, null, true, true);
                } else {
                    $sqlUsers = ugroup_db_get_members($ugrp['ugroup_id']);
                }
                if ($sqlUsers === null) {
                    continue;
                }
                $users = db_query($sqlUsers);
                while ($user = db_fetch_array($users)) {
                    $user_status_presenter = new StatusPresenter($user['status']);
                    $r = array('group'    => util_translate_name_ugroup($ugrp['name']),
                               'username' => $user['user_name'],
                               'realname' => $um->getUserById($user['user_id'])->getRealname(),
                               'email'    => $user['email'],
                               'status'   => $user_status_presenter->status_label);
                    echo build_csv_record($col_list, $r) . $eol;
                }
            }
            break;

        case 'user_groups_format':
            echo '<h3>' . $Language->getText('project_export_user_groups', 'exp_format') . '</h3>';
            echo '<p>' . $Language->getText('project_export_user_groups', 'exp_format_msg') . '</p>';

            // Pick-up a random project member
            $sqlUsers = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_MEMBERS'], false, $group_id, false, null, true, true);
            if ($sqlUsers === null) {
                return;
            }
            $users = db_query($sqlUsers);
            $uRow = db_fetch_array($users);
            $user = $um->getUserById($uRow['user_id']);

            $dsc_list = array('group'    => $GLOBALS['Language']->getText('project_export_user_groups', 'user_group_desc'),
                              'username' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_username_desc', array($GLOBALS['sys_name'])),
                              'realname' => $GLOBALS['Language']->getText('project_export_user_groups', 'user_realname_desc'),
                              'email'    => _('User email address'),
                              'status'   => _('User status'));
            $user_status_presenter = new StatusPresenter($user->getStatus());
            $record   = array('group'    => util_translate_name_ugroup('project_members'),
                              'username' => $user->getName(),
                              'realname' => $user->getRealName(),
                              'email'    => $user->getEmail(),
                              'status'   => $user_status_presenter->status_label);
            display_exported_fields($col_list, $lbl_list, $dsc_list, $record);
            break;
    }
}
