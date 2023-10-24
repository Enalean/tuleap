<?php
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Nicolas Terray

if (! user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if (! $ath->userIsAdmin()) {
    exit_permission_denied();
    return;
}

//{{{ We check variables submitted by user
$perm_type = $request->getValidated('perm_type', new Valid_WhiteList('tracker', 'fields'), '');
//We aren't going to update, unless the user's asked to
$update = $request->getValidated('update', 'string') ? true : false;
//We aren't going to reset, unless the user's asked to
$reset = $request->getValidated('reset', 'string') ? true : false;
//We display by group, unless the user's asked to not
$group_first = $request->getValidated('group_first', 'string') ? true : false;
//We show the first group or the first field, unless the user's asked to show a specific
$selected_id = $request->getValidated('selected_id', 'uint', false);
//}}}
switch ($perm_type) {
    case 'tracker':
        if ($update || $reset) {
            if ($update) {
                permission_process_update_tracker_permissions($group_id, $atid, $_REQUEST);
            } elseif ($reset) {
                //The user want to clear permissions
                permission_clear_all_tracker($group_id, $atid);
            }
        }

        //display
        $ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_field_usage', 'usage_admin'),
        ]);
        $ugroups_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);
        $ath->displayPermissionsTracker($ugroups_permissions);
        break;
    case 'fields':
        if ($update) {
            if ($request->exist('permissions') && is_array($request->get('permissions'))) {
                $fields = $art_field_fact->getAllUsedFields();
                permission_process_update_fields_permissions($group_id, $atid, $fields, $request->get('permissions'));
            }
        }
        //display
        $ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_field_usage', 'usage_admin'),
        ]);
        $ugroups_permissions = permission_get_field_tracker_ugroups_permissions($group_id, $atid, $art_field_fact->getAllUsedFields(), false);
        $ath->displayPermissionsFieldsTracker($ugroups_permissions, $group_first, $selected_id);
        break;
    default:
        $ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_field_usage', 'usage_admin'),
        ]);
        $ath->displayPermissionsGeneralMenu();
        break;
}

$ath->footer([]);
