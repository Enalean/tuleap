<?php
/**
 * Copyright (c) Enalean SAS, 2017 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Written for Codendi by Stephane Bouhet
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

// Inherited from old .htaccess (needed for reports, linked artifact view, etc)
ini_set('max_execution_time', 1800);

$sanitizer = new SimpleSanitizer();


$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId');
$atid     = $request->getValidated('atid', 'uint');

$hp = Codendi_HTMLPurifier::instance();

if ($group_id && ! $atid) {
    // Manage trackers: create and delete

    //    get the Group object
    $pm    = ProjectManager::instance();
    $group = $pm->getProject($group_id);
    if (! $group || ! is_object($group) || $group->isError()) {
        exit_no_group();
    }
    //    Create the ArtifactType object
    $ath = new ArtifactTypeHtml($group);
    if (! $ath || ! is_object($ath)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_at'));
    }
    if ($ath->isError()) {
        exit_error($Language->getText('global', 'error'), $ath->getErrorMessage());
    }

    $atf = new ArtifactTypeFactory($group);

    require('./admin_trackers.php');
} elseif ($group_id && $atid) {
    // Manage trackers: create and delete

    //    get the Group object
    $pm    = ProjectManager::instance();
    $group = $pm->getProject($group_id);
    if (! $group || ! is_object($group) || $group->isError()) {
        exit_no_group();
    }
    //    Create the ArtifactType object
    $ath = new ArtifactTypeHtml($group, $atid);
    if (! $ath || ! is_object($ath)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_at'));
    }
    if ($ath->isError()) {
        exit_error($Language->getText('global', 'error'), $ath->getErrorMessage());
    }
    // Check if this tracker is valid (not deleted)
    if (! $ath->isValid()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
    }

    $ach = new ArtifactCannedHtml($ath);
    if (! $ach || ! is_object($ach)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_create_canned'));
    }
    if ($ach->isError()) {
        exit_error($Language->getText('global', 'error'), $ach->getErrorMessage());
    }

    $atf = new ArtifactTypeFactory($group);

    // Create fieldset factory
    $art_fieldset_fact = new ArtifactFieldSetFactory($ath);
    // Create field factory
    $art_field_fact = new ArtifactFieldFactory($ath);

    $user_id = UserManager::instance()->getCurrentUser()->getId();

    $func = $request->getValidated('func', 'string', '');
    switch ($func) {
        case 'report':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            $report_id = $request->getValidated('report_id', 'uint', 0);

            $rid = isset($report_id) ? $report_id : 0;
            $arh = new ArtifactReportHtml($rid, $atid);
            if (! $arh) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_retrieved_report', $arh->getErrorMessage()));
            }

            if ($request->getValidated('post_changes')) {
                //Only tracker admin users can create 'P' scope reports
                if ($ath->userIsAdmin()) {
                    $validScope = new Valid_WhiteList('rep_scope', ['I', 'P']);
                } else {
                    $validScope = new Valid_WhiteList('rep_scope', ['I']);
                }
                $rep_scope = $request->getValidated('rep_scope', $validScope, 'I');

                    // apply update or create in bd
                $rep_name = $request->getValidated('rep_name', 'string', '');
                $rep_desc = $request->getValidated('rep_desc', 'text', '');
                if ($request->get('update_report')) {
                    if ($ath->userIsAdmin() && ($rep_scope == 'P') && ($request->exist('rep_default'))) {
                                $rep_default = 1;
                    } else {
                        $rep_default = 0;
                    }
                    $updated = $arh->recreate($user_id, $rep_name, $rep_desc, $rep_scope, $rep_default);
                    if (! $updated) {
                        if ($arh->isError()) {
                            exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_updated_report') . ': ' . $arh->getErrorMessage());
                        }
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_updated_report'));
                    }
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('tracker_index', 'report_definition_updated'));
                } else {
                    if ($ath->userIsAdmin() && ($rep_scope == 'P') && ($request->exist('rep_default'))) {
                        $rep_default = 1;
                    } else {
                        $rep_default = 0;
                    }
                    $report_id = $arh->create($user_id, $rep_name, $rep_desc, $rep_scope, $rep_default);
                    if (! $report_id) {
                        if ($arh->isError()) {
                            exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_created_report') . ': ' . $arh->getErrorMessage());
                        }
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_created_report'));
                    }
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'new_report_created'));
                }

            // now insert all the field entries in the artifact_report_field table
                $aff    = new ArtifactFieldFactory($ath);
                $fields = $aff->getAllUsedFields();
                foreach ($fields as $field) {
                    $cb_search   = 'CBSRCH_' . $field->getName();
                    $cb_report   = 'CBREP_' . $field->getName();
                    $tf_search   = 'TFSRCH_' . $field->getName();
                    $tf_report   = 'TFREP_' . $field->getName();
                    $tf_colwidth = 'TFCW_' . $field->getName();

                    $cb_search_val = ($request->getValidated($cb_search) ? '1' : '0');
                    $cb_report_val = ($request->getValidated($cb_report) ? '1' : '0');

                    $tf_search_val   = $request->getValidated($tf_search);
                    $tf_report_val   = $request->getValidated($tf_report);
                    $tf_colwidth_val = $request->getValidated($tf_colwidth);

                    if ($cb_search_val || $cb_report_val || $tf_search_val || $tf_report_val) {
                        $arh->add_report_field($field->getName(), $cb_search_val, $cb_report_val, $tf_search_val, $tf_report_val, $tf_colwidth_val);
                    }
                }
                $arh->fetchData($report_id);
            } elseif ($request->getValidated('delete_report')) {
                if (
                    ($arh->scope == 'P') &&
                    ! $ath->userIsAdmin()
                ) {
                    exit_permission_denied();
                }
                $arh->delete();
                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'report_deleted'));
            } elseif (isset($update_default)) {
                $arh->fetchData($update_default);
                if (($arh->scope == 'P') && $ath->userIsAdmin()) {
                      $arh->updateDefaultReport();
                      $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'update_success'));
                }
            }

            if ($request->getValidated('new_report')) {
                $arh->createReportForm();
            } elseif ($request->getValidated('show_report')) {
                if (
                    ($arh->scope == 'P') &&
                    ! $ath->userIsAdmin()
                ) {
                       exit_permission_denied();
                }
                if (
                    ($arh->scope == 'S') &&
                    ! user_is_super_user()
                ) {
                       exit_permission_denied();
                }
                $arh->showReportForm();
            } else {
   // Front page
                $reports = $arh->getReports($atid, $user_id);
                $arh->showAvailableReports($reports);
            }
            $ath->footer([]);
            break;

        case 'canned':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $artifact_canned_id = $request->getValidated('artifact_canned_id', 'uint', 0);
            if ($request->getValidated('post_changes')) {
                   $title = $request->getValidated('title', 'string', '');
                   $body  = $request->getValidated('body', 'text', '');
                if ($request->getValidated('create_canned')) {
                    $aci = $ach->create($title, $body);
                    if (! $aci) {
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_create_canneditem'));
                    }
                } elseif ($request->getValidated('update_canned')) {
                    $aci = $ach->fetchData($artifact_canned_id);
                    if (! $aci) {
                                 exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_found_canneditem', (int) $artifact_canned_id));
                    }
                    if (! $ach->update($title, $body)) {
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_update_canneditem', (int) $artifact_canned_id));
                    }
                    if ($ach->isError()) {
                        exit_error($Language->getText('global', 'error'), $ach->getErrorMessage());
                    }
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'updated_cannedresponse'));
                }
            } elseif ($request->getValidated('delete_canned')) {
                if (! $ach->delete($artifact_canned_id)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_delete_canneditem', (int) $artifact_canned_id));
                }
                if ($ach->isError()) {
                    exit_error($Language->getText('global', 'error'), $ach->getErrorMessage());
                }
                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'deleted_cannedresponse'));
            } // End of post_changes
        // Display the UI Form
            if ($request->getValidated('update_canned') && ! $request->getValidated('post_changes')) {
                      $ath->adminHeader(['title' => $Language->getText('tracker_admin_index', 'modify_cannedresponse'),
                      ]);
                $aci = $ach->fetchData($artifact_canned_id);
                if (! $aci) {
                              exit_error($Language->getText('global', 'error'), $Language->getText('tracker_admin_index', 'not_found_canneditem', (int) $artifact_canned_id));
                }
                $ach->displayUpdateForm();
            } else {
                $ath->adminHeader(['title' => $Language->getText('tracker_admin_index', 'create_modify_cannedresponse'),
                ]);
                $ach->displayCannedResponses();

                $ach->displayCreateForm();
            }
            $ath->footer([]);
            break;

        case 'notification':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            switch ($request->getValidated('action')) {
                case 'remove_global':
                    $ok                     = false;
                    $global_notification_id = $request->getValidated('global_notification_id', 'uint');
                    if ($global_notification_id) {
                        $agnf = new ArtifactGlobalNotificationFactory();
                        if ($agnf->removeGlobalNotificationForTracker($global_notification_id, $atid)) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('tracker_include_type', 'info_gn_deleted'));
                            $ok = true;
                            //Add a default if needed
                            if (! count($agnf->getGlobalNotificationsForTracker($atid))) {
                                if ($agnf->addGlobalNotificationForTracker($atid)) {
                                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('tracker_include_type', 'info_gn_default_added'));
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_include_type', 'info_gn_default_not_added'));
                                }
                            }
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_include_type', 'error_gn_not_deleted'));
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_include_type', 'error_missing_param'));
                    }
                    break;
                case 'add_global':
                    $agnf = new ArtifactGlobalNotificationFactory();
                    if (! ($ok = $agnf->addGlobalNotificationForTracker($atid))) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_include_type', 'error_gn_not_added'));
                    }
                    break;
                default:
                    break;
            }

            if ($request->getValidated('submit')) {
                $ok = true;
                if ($ath->userIsAdmin()) {
                      $ok = $ath->updateNotificationSettings(
                          $user_id,
                          $request->getValidated('watchees', 'string', ''),
                          $request->getValidated('stop_notification', new Valid_WhiteList('stop_notification', ['1']), 0)
                      );
                      //{{{ Global Notifications
                      $submitted_notifications = $request->get('global_notification');
                      /*
                      new Valid_MultidimensionalArray(
                              'global_notification',
                              array(
                        'addresses'         => 'string',
                        'all_updates'       => new Valid_WhiteList('', array(0, 1)),
                        'check_permissions' => new Valid_WhiteList('', array(0, 1))
                              )
                          )
                      );
                      */

                    if ($submitted_notifications) {
                        $agnf   = new ArtifactGlobalNotificationFactory();
                        $notifs = $agnf->getGlobalNotificationsForTracker($atid);
                        foreach ($notifs as $id => $nop) {
                            if (
                                isset($submitted_notifications[$id]) && (
                                 $submitted_notifications[$id]['addresses'] != $notifs[$id]->getAddresses() ||
                                 $submitted_notifications[$id]['all_updates'] != $notifs[$id]->isAllUpdates() ||
                                 $submitted_notifications[$id]['check_permissions'] != $notifs[$id]->isCheckPermissions()
                                )
                            ) {
                                     $ok = $agnf->updateGlobalNotification($id, $submitted_notifications[$id]) && $ok;
                            }
                        }
                    }

                    if (is_array($request->get('add_global_notification'))) {
                        foreach ($request->get('add_global_notification') as $new_global_notif) {
                            if (
                                (isset($new_global_notif['addresses']) && trim($new_global_notif['addresses'])) &&
                                   isset($new_global_notif['all_updates']) &&
                                   isset($new_global_notif['check_permissions'])
                            ) {
                                if ($id = $agnf->addGlobalNotificationForTracker($atid)) {
                                             $ok = $agnf->updateGlobalNotification($id, $new_global_notif) && $ok;
                                }
                            }
                        }
                    }

                //}}}
                }

               // Event/Role specific settings
               //echo "num_roles : ".$ath->num_roles.", num_events : ".$ath->num_events." <br>";

                $arr_notif = [];
                for ($i = 0; $i < $ath->num_roles; $i++) {
                    $role_id = $ath->arr_roles[$i]['role_id'];
                    for ($j = 0; $j < $ath->num_events; $j++) {
                        $event_id  = $ath->arr_events[$j]['event_id'];
                        $cbox_name = 'cb_' . $role_id . '_' . $event_id;
                        //echo "DBG $cbox_name -> '".$$cbox_name."'<br>";
                        $arr_notif[$role_id][$event_id] = ( $request->getValidated($cbox_name) ? 1 : 0);
                    }
                }

                $ath->deleteNotification($user_id);
                $res_notif = $ath->setNotification($user_id, $arr_notif);

               // Give Feedback
                if ($res_notif && $ok) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'update_success'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_admin_index', 'update_failed', $ath->getErrorMessage()));
                }
                $ath->fetchData($ath->getID());
            }
            $ath->adminHeader(
                ['title' => $Language->getText('tracker_admin_index', 'art_admin'),
                ]
            );
            $ath->displayNotificationForm($user_id);
            $ath->footer([]);
            break;

        case 'editoptions':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            if ($request->getValidated('update')) {
                $name                         = $sanitizer->sanitize($request->getValidated('name', 'string', ''));
                $description                  = $sanitizer->sanitize($request->getValidated('description', 'text', ''));
                $itemname                     = $request->getValidated('itemname', 'string', '');
                $allow_copy                   = $request->getValidated('allow_copy') ? 1 : 0;
                $submit_instructions          = $request->getValidated('submit_instructions', 'text', '');
                $browse_instructions          = $request->getValidated('browse_instructions', 'text', '');
                $instantiate_for_new_projects = $ath->Group->isTemplate() && $request->getValidated('instantiate_for_new_projects') ? 1 : 0;

                if (
                    ! $ath->update(
                        $name,
                        $description,
                        $itemname,
                        $allow_copy,
                        $submit_instructions,
                        $browse_instructions,
                        $instantiate_for_new_projects
                    )
                ) {
                        exit_error($Language->getText('global', 'error'), $ath->getErrorMessage());
                } else {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'update_success_title'));
                }
            }

            $ath->adminHeader([
                'title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_index', 'options'),
            ]);
            $ath->displayOptions($group_id, $atid);
            $ath->footer([]);
            break;


        case 'field_values':
            require('./field_values.php');
            break;

        case 'update_binding':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field && is_array($request->get('value_function'))) {
                if (! $field->updateValueFunction($atid, $request->get('value_function'))) {
                    exit_error($Language->getText('global', 'error'), $art_field_fact->getErrorMessage());
                } else {
                     $arm = new ArtifactRulesManager();
                     $arm->deleteRulesByFieldId($atid, $field_id);
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'values_updated'));
                }
            }
            require('./field_values.php');
            break;

        case 'update_default_value':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }
            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
          // For date fields, it is possible to give a computed default value (current date)
                if ($field->isDateField() && $request->get('default_date_type') == 'current_date') {
                      $computed_value = 'current_date';
                } else {
                    $computed_value = false;
                }

                if (
                    (! $field->isDateField() && $request->valid(new Valid_String('default_value')))
                    || ($field->isMultiSelectBox())
                    || ($request->valid(new Valid_String('default_value')))
                    || ($field->isTextArea() && $request->valid(new Valid_Text('default_value')))
                ) {
                    if (! $field->updateDefaultValue($atid, $request->get('default_value'), $computed_value)) {
                        exit_error($Language->getText('global', 'error'), $art_field_fact->getErrorMessage());
                    } else {
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'values_updated'));
                    }
                }
            }
            require('./field_values.php');
            break;

        case 'display_field_values':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                require('./field_values_details.php');
            }
            break;

        case 'display_field_value':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                $ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') .
                $Language->getText('tracker_admin_field_values_details', 'values_admin'),
                ]);
                echo "<H2>" . $Language->getText('tracker_import_admin', 'tracker') .
                ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . "&atid=" . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . "</a>'" .
                $Language->getText('tracker_admin_field_values_details', 'manage_for', $hp->purify($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML) . "'</H2>";

                $value_array = $field->getFieldValue($atid, $request->getValidated('value_id', 'uint'));
                $ath->displayFieldValueForm("value_update", $field_id, $value_array['value_id'], $value_array['value'], $value_array['order_id'], $value_array['status'], $value_array['description']);
                $ath->footer([]);
            }
            break;

        case 'value_create':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                $value       = $sanitizer->sanitize($request->getValidated('value', 'string', ''));
                $description = $sanitizer->sanitize($request->getValidated('description', 'text', ''));
                $order_id    = $request->getValidated('order_id', 'uint');
                if (! $field->createValueList($atid, $value, $description, $order_id)) {
                    exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
                } else {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'value_created'));
                }
                require('./field_values_details.php');
            }
            break;

        case 'value_update':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field && $request->valid(new Valid_WhiteList('status', ['A', 'H', 'P']))) {
                $value_id    = $request->getValidated('value_id', 'uint');
                $value       = $sanitizer->sanitize($request->getValidated('value', 'string', ''));
                $description = $sanitizer->sanitize($request->getValidated('description', 'text', ''));
                $order_id    = $request->getValidated('order_id', 'uint');
                $status      = $request->get('status');
                if (! $field->updateValueList($atid, $value_id, $value, $description, $order_id, $status)) {
                    exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
                } else {
                    if ($status == $ath->FIELD_VALUE_STATUS_HIDDEN) {
                        $arm = new ArtifactRulesManager();
                        $arm->deleteRulesByValueId($atid, $field_id, $value_id);
                    }
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'value_updated'));
                }
            }
            require('./field_values_details.php');
            break;

        case 'value_delete':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                $value_id = $request->getValidated('value_id', 'uint');
                if (! $field->deleteValueList($atid, $value_id)) {
                    exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
                } else {
                     $arm = new ArtifactRulesManager();
                     $arm->deleteRulesByValueId($atid, $field_id, $value_id);
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'value_deleted'));
                }
                require('./field_values_details.php');
            }
            break;

        case 'field_usage':
            require('./field_usage.php');
            break;

        case 'field_create':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }


            if (
                $request->valid(new Valid_WhiteList('data_type', [1, 2, 3, 4, 5])) //See data_type in ArtifactField.class.php
                    && $request->valid(new Valid_WhiteList('display_type', ['SB', 'MB', 'TF', 'TA', 'DF']))
            ) {
                       $label          = $sanitizer->sanitize($request->getValidated('label', 'string'));
                       $description    = $sanitizer->sanitize($request->getValidated('description', 'text'));
                       $data_type      = $request->get('data_type');
                       $display_type   = $request->get('display_type');
                       $display_size   = $request->getValidated('display_size', 'string', '');
                       $rank_on_screen = $request->getValidated('rank_on_screen', 'uint', 0);
                       $empty_ok       = $request->getValidated('empty_ok', new Valid_WhiteList('', [1]), 0);
                       $keep_history   = $request->getValidated('keep_history', new Valid_WhiteList('', [1]), 0);
                       $special        = $request->getValidated('special', new Valid_WhiteList('', [1]), 0);
                       $use_it         = $request->getValidated('use_it', new Valid_WhiteList('', [1]), 0);
                       $field_set_id   = $request->getValidated('field_set_id', 'uint');

                if (
                    ! $art_field_fact->createField(
                        $description,
                        $label,
                        $data_type,
                        $display_type,
                        $display_size,
                        $rank_on_screen,
                        (isset($empty_ok) ? $empty_ok : 0),
                        (isset($keep_history) ? $keep_history : 0),
                        $special,
                        $use_it,
                        $field_set_id
                    )
                ) {
                            exit_error($Language->getText('global', 'error'), $art_field_fact->getErrorMessage());
                } else {
                  // Reload the field factory
                    $art_field_fact = new ArtifactFieldFactory($ath);
                  // Reload the fieldset factory
                    $art_fieldset_fact = new ArtifactFieldSetFactory($ath);
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'field_created'));
                }
            }
            require('./field_usage.php');
            break;

        case 'field_update':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                if (
                    $request->valid(new Valid_WhiteList('data_type', [1, 2, 3, 4, 5])) //See data_type in ArtifactField.class.php
                    && $request->valid(new Valid_WhiteList('display_type', ['SB', 'MB', 'TF', 'TA', 'DF']))
                    && $request->valid(new Valid_String('field_name'))
                ) {
                     $field_name     = $request->get('field_name');
                     $label          = $sanitizer->sanitize($request->getValidated('label', 'string'));
                     $description    = $sanitizer->sanitize($request->getValidated('description', 'text'));
                     $data_type      = $request->get('data_type');
                     $display_type   = $request->get('display_type');
                     $display_size   = $request->getValidated('display_size', 'string', '');
                     $rank_on_screen = $request->getValidated('rank_on_screen', 'uint', 0);
                     $empty_ok       = $request->getValidated('empty_ok', new Valid_WhiteList('', [1]), 0);
                     $keep_history   = $request->getValidated('keep_history', new Valid_WhiteList('', [1]), 0);
                     $special        = $request->getValidated('special', new Valid_WhiteList('', [1]), 0);
                     $use_it         = $request->getValidated('use_it', new Valid_WhiteList('', [1]), 0);
                     $field_set_id   = $request->getValidated('field_set_id', 'uint');
                    if (
                        ! $field->update(
                            $atid,
                            $field_name,
                            $description,
                            $label,
                            $data_type,
                            $display_type,
                            ($display_size == "N/A" ? "" : $display_size),
                            $rank_on_screen,
                            $empty_ok,
                            $keep_history,
                            $special,
                            $use_it,
                            $field_set_id
                        )
                    ) {
                               exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
                    } else {
                        if (! (isset($use_it) && $use_it)) {
                            $arm = new ArtifactRulesManager();
                            $arm->deleteRulesByFieldId($atid, $field_id);
                        }
                               // Reload the field factory
                               $art_field_fact = new ArtifactFieldFactory($ath);
                        // Reload the fieldset factory
                               $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

                        $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'field_updated'));
                    }
                }
            }
            require('./field_usage.php');
            break;

        case 'field_delete':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                $em = EventManager::instance();
                $em->processEvent('tracker_admin_field_delete', ['field' => $field, 'ath' => $ath]);

          //clear permissions
                permission_clear_all_fields_tracker($group_id, $atid, $field->getID());

                if (! $field->delete($atid)) {
                    exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
                } else {
                     $arm = new ArtifactRulesManager();
                     $arm->deleteRulesByFieldId($atid, $field_id);

                   // Reload the field factory
                    $art_field_fact = new ArtifactFieldFactory($ath);
                   // Reload the fieldset factory
                    $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'field_deleted'));
                }
            }
            require('./field_usage.php');
            break;

        case 'display_field_update':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $field_id = $request->getValidated('field_id', 'uint', 0);
            $field    = $art_field_fact->getFieldFromId($field_id);
            if ($field) {
                $ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_index', 'modify_usage'),
                ]);
                echo "<H2>" . $Language->getText('tracker_import_admin', 'tracker') .
                ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . "&atid=" . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . "</a>' " .
                $Language->getText('tracker_admin_index', 'modify_usage_for', $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML)) . "</H2>";
                $ath->displayFieldUsageForm(
                    "field_update",
                    $field->getID(),
                    $field->getName(),
                    $field->getDescription(),
                    $field->getLabel(),
                    $field->getDataType(),
                    $field->getDefaultValue(),
                    $field->getDisplayType(),
                    $field->getDisplaySize(),
                    $field->getPlace(),
                    $field->getEmptyOk(),
                    $field->getKeepHistory(),
                    $field->isSpecial(),
                    $field->getUseIt(),
                    true,
                    $field->getFieldSetID()
                );
                $ath->footer([]);
            }
            break;

        case 'delete_tracker':
            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            if (! user_ismember($group_id, 'A')) {
                exit_permission_denied();
                return;
            }

            $ath->adminTrackersHeader(['title' => $ath->getName() . ' ' . $Language->getText('tracker_admin_field_usage', 'tracker_admin'),
            ]);
            if (! $ath->preDelete()) {
                      $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_admin_index', 'deletion_failed', $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML)));
            } else {
                 //@see  preDeleteArtifactType @common/tracker/ArtifactTypeFactory.class.php
                  $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'delete_success', $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML)));
                  echo $Language->getText('tracker_admin_index', 'tracker_deleted', [$hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML), ForgeConfig::get('sys_email_admin')]);
                $arm = new ArtifactRulesManager();
                $arm->deleteRulesByArtifactType($atid);
                  // Delete related reference if it exists
                  // NOTE: there is no way to know if the reference is actually related to this tracker.
                  $reference_manager = ReferenceManager::instance();
                  $ref               = $reference_manager->loadReferenceFromKeywordAndNumArgs(strtolower($ath->getItemName()), $group_id, 1);
                if ($ref) {
                    if ($reference_manager->deleteReference($ref)) {
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_reference', 't_r_deleted'));
                    }
                }
            }
            $ath->footer([]);
            break;
        case 'permissions':
            require('./tracker_permissions.php');
            break;
        case 'field_dependencies':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $armh = new ArtifactRulesManagerHtml($ath, '?group_id=' . (int) ($ath->getGroupID()) . '&atid=' . (int) ($ath->getID()) . '&func=field_dependencies');
            if ($request->getValidated('save') === 'save') {
                if ($request->valid(new Valid_UInt('source_field')) && $request->valid(new Valid_UInt('target_field'))) {
                    $armh->saveFromRequest($request);
                } else {
                    $armh->badRequest();
                }
            } else {
                $armh->displayRules(
                    $request->get('source_field'),
                    $request->get('target_field'),
                    $request->get('direction_type') == 'source' ? $request->get('value') : false,
                    $request->get('direction_type') == 'source' ? false : $request->get('value')
                );
            }
            break;
        case 'fieldsets':
            require('./field_sets.php');
            break;
        case 'fieldset_create':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }
            $name        = $sanitizer->sanitize($request->getValidated('name', 'string', ''));
            $description = $sanitizer->sanitize($request->getValidated('description', 'text', ''));
            $rank        = $request->getValidated('rank', 'uint', 0);
            if (! $art_fieldset_fact->createFieldSet($name, $description, $rank)) {
                exit_error($Language->getText('global', 'error'), $art_fieldset_fact->getErrorMessage());
            } else {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'fieldset_created'));
            }
            require('./field_sets.php');
            break;
        case 'display_fieldset_update':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $fieldset_id = $request->getValidated('fieldset_id', 'uint', 0);
            $fieldset    = $art_fieldset_fact->getFieldSetById($fieldset_id);

            if ($fieldset) {
                $ath->adminHeader(['title' => $Language->getText('tracker_admin_fieldset', 'tracker_admin') . $Language->getText('tracker_admin_index', 'modify_fieldset'),
                ]);
                echo "<H2>" . $Language->getText('tracker_import_admin', 'tracker') .
                ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . "&atid=" . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . "</a>' " .
                $Language->getText('tracker_admin_index', 'modify_fieldset_for', $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODENDI_PURIFIER_CONVERT_HTML)) . "</H2>";
                $ath->displayFieldSetCreateForm(
                    "fieldset_update",
                    $fieldset->getID(),
                    $fieldset->getLabel(),
                    $fieldset->getDescriptionText(),
                    $fieldset->getRank()
                );
                $ath->footer([]);
            }
            break;
        case 'fieldset_update':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $fieldset_id = $request->getValidated('fieldset_id', 'uint', 0);
            $fieldset    = $art_fieldset_fact->getFieldSetById($fieldset_id);
            if ($fieldset) {
                $name        = $sanitizer->sanitize($request->getValidated('name', 'string', ''));
                $description = $sanitizer->sanitize($request->getValidated('description', 'text', ''));
                $rank        = $request->getValidated('rank', 'uint', 0);

                // We check if there is a change with the name and description
                // If there is no changes, we keep the internationalized key, because in the interface,
                // the user don't see the i18n key, but the associated value (the l10n value).
                if ($name == $fieldset->getLabel()) {
                    // getName returns the key, getLabel returns the value (internationalized if so, same as name if not)
                    $name = $fieldset->getName();
                }
                if ($description == $fieldset->getDescriptionText()) {
                // getDescription returns the key, getDescriptionText returns the value (internationalized if so, same as description if not)
                    $description = $fieldset->getDescription();
                }

                if (! $fieldset->update($name, $description, $rank)) {
                    exit_error($Language->getText('global', 'error'), $fieldset->getErrorMessage());
                } else {
                // Reload the field factory
                    $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'fieldset_updated'));
                }
            }
            require('./field_sets.php');
            break;
        case 'fieldset_delete':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $fieldset_id = $request->getValidated('fieldset_id', 'uint', 0);
            $fieldset    = $art_fieldset_fact->getFieldSetById($fieldset_id);
            if ($fieldset) {
                if (! $art_fieldset_fact->deleteFieldSet($fieldset_id)) {
                    exit_error($Language->getText('global', 'error'), $art_fieldset_fact->getErrorMessage());
                } else {
                // Reload the fieldset factory
                    $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_admin_index', 'fieldset_deleted'));
                }
            }
            require('./field_sets.php');
            break;
        default:
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            $em = EventManager::instance();
            $em->processEvent('tracker_graphic_report_admin', ['ath' => $ath, 'atf' => $atf, 'art_field_fact' => $art_field_fact]);
            $ath->adminHeader(['title' => $ath->getName() . ' ' . $Language->getText('tracker_admin_field_usage', 'tracker_admin')]);
            $ath->displayAdminTracker($group_id, $atid);
            $ath->footer([]);
    } // switch
} else {
    //browse for group first message
    exit_no_group();
}
