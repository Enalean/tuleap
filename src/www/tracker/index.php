<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/admin/project_admin_utils.php';

// Inherited from old .htaccess (needed for reports, linked artifact view, etc)
ini_set('max_execution_time', 1800);

$aid      = $request->getValidated('aid', 'uint', 0);
$atid     = $request->getValidated('atid', 'uint', 0);
$group_id = $request->getValidated('group_id', 'GroupId', 0);

$em        = EventManager::instance();
$sanitizer = new SimpleSanitizer();
$hp        = Codendi_HTMLPurifier::instance();

if ($aid && ! $atid) {
    // We have the artifact id, but not the tracker id
    $sql    = "SELECT group_artifact_id FROM artifact WHERE artifact_id= " . db_ei($aid);
    $result = db_query($sql);
    if (db_numrows($result) > 0) {
        $row  = db_fetch_array($result);
        $atid = $row['group_artifact_id'];
    }
}

if ($atid && ! $group_id) {
    // We have the artifact group id, but not the group id
    $sql    = "SELECT group_id FROM artifact_group_list WHERE group_artifact_id=" . db_ei($atid);
    $result = db_query($sql);
    if (db_numrows($result) > 0) {
        $row      = db_fetch_array($result);
        $group_id = $row['group_id'];
    }
}


//define undefined variables
$func = $request->getValidated('func', 'string', '');

if ($func == 'gotoid') {
    // Direct access to an artifact
    if (! $aid) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'art_id_necessary'));
    } else {
        require('./gotoid.php');
    }
} elseif ($group_id && $atid) {
        //      get the Group object
        $pm    = ProjectManager::instance();
        $group = $pm->getProject($group_id);
    if (! $group || ! is_object($group) || $group->isError()) {
            exit_no_group();
    }
        //      Create the ArtifactType object
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
        //Check if the user can view the artifact
    if (! $ath->userCanView()) {
        exit_permission_denied();
    }

        // Create field factory
        $art_field_fact    = new ArtifactFieldFactory($ath);
        $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

    switch ($func) {
        case 'rss':
            if ($aid) {
                $ah = new ArtifactHtml($ath, $aid);
                if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
                } else {
                    $ah->displayRSS();
                }
            } else {
                require('./browse.php');
            }
            break;
        case 'add':
                $ah = new ArtifactHtml($ath);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } else {
                    require('./add.php');
            }
            break;

        case 'postadd':
                //              Create a new Artifact
                $ah = new ArtifactHtml($ath);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } else {
                    // Check if a user can submit a new without loggin
                if (! user_isloggedin() && ! $ath->allowsAnon()) {
                        exit_not_logged_in();
                        return;
                }

                    //  make sure this person has permission to add artifacts
                if (! $ath->userCanSubmit()) {
                        exit_permission_denied();
                }

                    // First check parameters

                    // CC
                    $add_cc       = $request->get('add_cc');
                    $array_add_cc = preg_split('/[,;]/D', $add_cc);
                if ($add_cc && ! util_validateCCList($array_add_cc, $message)) {
                    exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
                }
            // Files
                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && ! util_check_fileupload($_FILES['input_file']['tmp_name'])) {
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_filename'));
                }

                    //Check Field Dependencies
                    $arm = new ArtifactRulesManager();
                if (! $arm->validate($atid, $art_field_fact->extractFieldList(), $art_field_fact)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_field_dependency'));
                }

                    // Artifact creation
                if (! $ah->create()) {
                        exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
                } else {
                    $changes = [];
                        //      Attach file to this Artifact.
                    if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                            $afh = new ArtifactFileHtml($ah);
                        if (! $afh || ! is_object($afh)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file'));
                        } elseif ($afh->isError()) {
                                $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                        } else {
                            if (
                                ! $afh->upload(
                                    $_FILES['input_file']['tmp_name'],
                                    $_FILES['input_file']['name'],
                                    $_FILES['input_file']['type'],
                                    $sanitizer->sanitize($request->get('file_description')),
                                    $changes
                                )
                            ) {
                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_attach_file', $afh->getErrorMessage()));
                            }
                        }
                    }

                        // Add new cc if any
                    if ($add_cc) {
                        $ah->addCC($add_cc, $sanitizer->sanitize($request->get('cc_comment')), $changes);
                    }

                        // send an email to notify the user of the artifact add
                        $agnf      = new ArtifactGlobalNotificationFactory();
                        $addresses = $agnf->getAllAddresses($ath->getID());
                        $ah->mailFollowupWithPermissions($addresses);

                        $em->processEvent('tracker_postadd', ['ah' => $ah, 'ath' => $ath]);

                        $itemname = $ath->getItemName();
                        $GLOBALS['Response']->addFeedback('info', $Language->getText(
                            'tracker_index',
                            'create_success',
                            '<a href="/goto?key=' . $itemname . '&val=' . $ah->getID() . '&group_id=' . $group_id . '">' . $itemname . ' #' . $ah->getID() . '</a>'
                        ), CODENDI_PURIFIER_LIGHT);
                        $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
                }
            }
            break;
        case 'postcopy':
                //              Create a new Artifact
                $ah = new ArtifactHtml($ath);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } else {
                    // Check if a user can submit a new without loggin
                if (! user_isloggedin() && ! $ath->allowsAnon()) {
                        exit_not_logged_in();
                        return;
                }

                    //  make sure this person has permission to copy artifacts
                    //  !!!! verify with new permission scheme !!!!
                if (! $ath->userCanSubmit()) {
                        exit_permission_denied();
                }

                    // First check parameters

                    // CC
                    $add_cc       = $request->get('add_cc');
                    $array_add_cc = preg_split('/[,;]/D', $add_cc);
                if ($add_cc && ! util_validateCCList($array_add_cc, $message)) {
                    exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
                }

            // Files
                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && ! util_check_fileupload($_FILES['input_file']['tmp_name'])) {
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_filename'));
                }

                    // Artifact creation
                if (! $ah->create()) {
                        exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
                } else {
                    $changes = [];
                        //      Attach file to this Artifact.
                    if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                            $afh = new ArtifactFileHtml($ah);
                        if (! $afh || ! is_object($afh)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file'));
                        } elseif ($afh->isError()) {
                               $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                        } else {
                            if (
                                ! $afh->upload(
                                    $_FILES['input_file']['tmp_name'],
                                    $_FILES['input_file']['name'],
                                    $_FILES['input_file']['type'],
                                    $sanitizer->sanitize($request->get('file_description')),
                                    $changes
                                )
                            ) {
                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_attach_file', $afh->getErrorMessage()));
                            }
                        }
                    }

                        // Add new cc if any
                    if ($add_cc) {
                        $ah->addCC($add_cc, $sanitizer->sanitize($request->get('cc_comment')), $changes);
                    }

                // Add new dependencies if any
                        $artifact_id_dependent = $request->get('artifact_id_dependent');
                    if ($artifact_id_dependent) {
                        $ah->addDependencies($artifact_id_dependent, $changes, false, false);
                    }

                // Add follow-up comments if any
                        $follow_up_comment = $request->get('follow_up_comment');
                        $comment_type_id   = $request->get('comment_type_id');
                        $canned_response   = $request->get('canned_response');
                        $vFormat           = new Valid_WhiteList('comment_format', [Artifact::FORMAT_HTML, Artifact::FORMAT_TEXT]);
                        $comment_format    = $request->getValidated('comment_format', $vFormat, Artifact::FORMAT_TEXT);
                        $ah->addFollowUpComment($follow_up_comment, $comment_type_id, $canned_response, $changes, $comment_format);

                        // send an email to notify the user of the artifact update
                            $agnf      = new ArtifactGlobalNotificationFactory();
                            $addresses = $agnf->getAllAddresses($ath->getID());
                            $ah->mailFollowupWithPermissions($addresses);

                            $em->processEvent('postcopy', ['ah' => $ah, 'ath' => $ath]);

                            $itemname = $ath->getItemName();
                            $GLOBALS['Response']->addFeedback('info', $Language->getText(
                                'tracker_index',
                                'create_success',
                                '<a href="/goto?key=' . $itemname . '&val=' . $ah->getID() . '&group_id=' . $group_id . '">' . $itemname . ' #' . $ah->getID() . '</a>'
                            ), CODENDI_PURIFIER_LIGHT);
                    if ($ath->getStopNotification()) {
                        $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_index', 'notification_stopped'));
                    }
                        $GLOBALS['Response']->redirect('?group_id=' . $group_id . '&atid=' . $atid . '&func=browse');
                }
            }
            break;
        case 'delete_cc':
                $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                    exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
                $changes        = [];
                $artifact_cc_id = $request->get('artifact_cc_id');
                    $cc_array   = $ah->getCC($artifact_cc_id);
                    $user_id    = UserManager::instance()->getCurrentUser()->getId();
                    // Perform CC deletion if one of the condition is met:
                    // (a) current user is a artifact admin
                    // (b) then CC name is the current user
                    // (c) the CC email address matches the one of the current user
                    // (d) the current user is the person who added a gieven name in CC list
                if (
                    user_ismember($group_id) ||
                    (user_getname($user_id) == $cc_array['email']) ||
                    (user_getemail($user_id) == $cc_array['email']) ||
                    (user_getname($user_id) == $cc_array['user_name'] )
                ) {
                        $changed = $ah->deleteCC($artifact_cc_id, $changes);
                    if ($changed) {
                        $agnf      = new ArtifactGlobalNotificationFactory();
                        $addresses = $agnf->getAllAddresses($ath->getID(), true);
                        $ah->mailFollowupWithPermissions($addresses, $changes);
                    }

                        $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&aid=' . (int) $aid . '&func=detail');
                } else {
                        // Invalid permission
                        exit_permission_denied();
                        return;
                }
            }
            break;
        case 'delete_comment':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

            if (! user_ismember($group_id)) {
                exit_permission_denied();
                return;
            }

                $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
                $artifact_history_id = $request->get('artifact_history_id');
                if ($ah->userCanEditFollowupComment($artifact_history_id)) {
                    $ah->deleteFollowupComment($aid, $artifact_history_id);
                    $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&aid=' . (int) $aid . '&func=detail');
                } else {
                    // Invalid permission
                    exit_permission_denied();
                    return;
                }
            }
            break;
        case 'delete_dependent':
            if (! user_isloggedin()) {
                    exit_not_logged_in();
                    return;
            }

            if (! user_ismember($group_id)) {
                        exit_permission_denied();
                        return;
            }

            $changes = [];

                $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
                $dependent_on_artifact_id = $request->get('dependent_on_artifact_id');
                $changed                  = $ah->deleteDependency($dependent_on_artifact_id, $changes);
                if ($changed) {
                        $agnf      = new ArtifactGlobalNotificationFactory();
                        $addresses = $agnf->getAllAddresses($ath->getID(), true);
                        $ah->mailFollowupWithPermissions($addresses, $changes);
                }

                $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&aid=' . (int) $aid . '&func=detail');
            }
            break;
        case 'delete_file':
                //      Delete a file from this artifact
                $ah = new ArtifactHtml($ath, $aid);

                // Check permissions
                $id         = $request->get('id');
                $file_array = $ah->getAttachedFile($id);
            if (
                user_ismember($group_id) ||
                (user_getname(UserManager::instance()->getCurrentUser()->getId()) == $file_array['user_name'] )
            ) {
                        $afh = new ArtifactFileHtml($ah, $id);
                if (! $afh || ! is_object($afh)) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file_obj', $afh->getName()));
                } elseif ($afh->isError()) {
                        $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage() . '::' . $hp->purify($afh->getName(), CODENDI_PURIFIER_CONVERT_HTML));
                } else {
                    if (! $afh->delete()) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'file_delete', $afh->getErrorMessage()));
                    } else {
                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index', 'file_delete_success'));
                    }
                }
                        $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&aid=' . (int) $aid . '&func=detail');
            } else {
                    // Invalid permission
                    exit_permission_denied();
                    return;
            }

            break;
        case 'postmod':
            $changes = [];
                //      Modify an Artifact
                $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                    exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
                    // Check if users can update anonymously
                if (! user_isloggedin() && ! $ath->allowsAnon()) {
                    exit_not_logged_in();
                }

                    // Check timestamp
                    $artifact_timestamp = $request->get('artifact_timestamp');
                if (
                    isset($artifact_timestamp) &&
                         ($ah->getLastUpdateDate() > $artifact_timestamp)
                ) {
                    // Artifact was updated between the time it was sent to the user, and the time it was submitted
                    exit_error($Language->getText('tracker_index', 'artifact_has_changed_title'), $Language->getText('tracker_index', 'artifact_has_changed', "/tracker/?func=detail&aid=$aid&atid=$atid&group_id=$group_id"));
                }

                    // First check parameters

                    // CC
                    $add_cc       = $request->get('add_cc');
                    $array_add_cc = preg_split('/[,;]/D', $add_cc);
                if ($add_cc && ! util_validateCCList($array_add_cc, $message)) {
                    exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
                }
                    // Files
                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && ! util_check_fileupload($_FILES['input_file']['tmp_name'])) {
                        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_filename'));
                }

                    //Check Field Dependencies
                    $arm = new ArtifactRulesManager();
                if (! $arm->validate($atid, $art_field_fact->extractFieldList(), $art_field_fact)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_field_dependency'));
                }

                    //data control layer
                    $canned_response = $request->get('canned_response');
                    $changed         = $ah->handleUpdate($request->get('artifact_id_dependent'), $canned_response, $changes);
                if (! $changed) {
                        $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
                        exit();
                }

                    //  Attach file to this Artifact.
                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                        $afh = new ArtifactFileHtml($ah);
                    if (! $afh || ! is_object($afh)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file'));
                    } elseif ($afh->isError()) {
                        $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                    } else {
                        if (
                            ! $afh->upload(
                                $_FILES['input_file']['tmp_name'],
                                $_FILES['input_file']['name'],
                                $_FILES['input_file']['type'],
                                $sanitizer->sanitize($request->get('file_description')),
                                $changes
                            )
                        ) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'file_upload_err', $afh->getErrorMessage()));
                                $was_error = true;
                        } else {
                             // Remove verbose feedback
                             //$GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','file_upload_success'));
                        }
                    }
                }

                    // Add new cc if any
                if ($add_cc) {
                    $changed |= $ah->addCC($add_cc, $sanitizer->sanitize($request->get('cc_comment')), $changes);
                }
                if ($changed && $changes) {
                    $agnf      = new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($ath->getID(), true);
                    $ah->mailFollowupWithPermissions($addresses, $changes);
                }

                    // Update the 'last_update_date' artifact field
                    $res_last_up = $ah->update_last_update_date();

                    $em->processEvent('tracker_postmod', ['ah' => $ah, 'ath' => $ath]);

                    //      Show just one feedback entry if no errors
                if (! isset($was_error) || ! $was_error) {
                    $itemname = $ath->getItemName();
                    $GLOBALS['Response']->addFeedback('info', $Language->getText(
                        'tracker_index',
                        'update_success',
                        '<a href="/goto?key=' . $itemname . '&val=' . $ah->getID() . '&group_id=' . $group_id . '">' . $itemname . ' #' . $ah->getID() . '</a>'
                    ), CODENDI_PURIFIER_LIGHT);
                    if ($ah->ArtifactType->getStopNotification()) {
                        $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_index', 'notification_stopped'));
                    }
                }
                if ($request->isAjax()) {
                    if ($field = $art_field_fact->getFieldFromName($request->get('field'))) {
                        $field_html = $ah->_getFieldLabelAndValueForUser($group_id, $atid, $field, UserManager::instance()->getCurrentUser()->getId(), true);
                        echo $field_html['value'];
                    }
                } else {
                    $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
                }
            }
            break;
        case 'postmasschange':
            $was_error = false;
                //      Modify several Artifacts
                //
                // Check if users can update anonymously
            if (! user_isloggedin() && ! $ath->allowsAnon()) {
                exit_not_logged_in();
            }

            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
                return;
            }

            $changes = [];

         // First check parameters

                // CC
                $add_cc       = $request->get('add_cc');
                $array_add_cc = preg_split('/[,;]/D', $add_cc);
            if ($add_cc && ! util_validateCCList($array_add_cc, $message)) {
                exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
            }
         // Files
            if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && ! util_check_fileupload($_FILES['input_file']['tmp_name'])) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_filename'));
            }
                $report_id = $request->get('report_id');
            if ($report_id) {
             // Create factories
                $report_fact = new ArtifactReportFactory();
             // Create the HTML report object
                $art_report_html = $report_fact->getArtifactReportHtml($report_id, $atid);
                $query           = $art_field_fact->extractFieldList(true, 'query_');
                $advsrch         = $request->get('advsrch');
                $from            = '';
                $where           = '';
                $art_report_html->getQueryElements($query, $advsrch, $from, $where);
                $sql = "select distinct a.artifact_id " . $from . " " . $where;

                $result     = db_query($sql);
                $number_aid = db_numrows($result);
            } else {
                $mass_change_ids = $request->get('mass_change_ids');
                reset($mass_change_ids);
                $number_aid = count($mass_change_ids);
            }

            $feedback        = '';
            $canned_response = $request->get('canned_response');
            for ($i = 0; $i < $number_aid; $i++) {
                if ($report_id) {
                    $row = db_fetch_array($result);
                    $aid = $row['artifact_id'];
                } else {
                    $aid = $mass_change_ids[$i];
                }

                $ah = new ArtifactHtml($ath, $aid);
                if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
                } elseif ($ah->isError()) {
                    exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
                } else {
                  //data control layer
                    $changed = $ah->handleUpdate($request->get('artifact_id_dependent'), $canned_response, $changes, true);
                    if ($changed) {
                        if ($i > 0) {
                            $feedback .= ",";
                        }
                        if ($i == 0) {
                            $feedback .= $Language->getText('tracker_index', 'updated_aid');
                        }
                        $feedback .= " " . (int) $aid;
                    }
            //  Attach file to this Artifact.
                    if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                        $afh = new ArtifactFileHtml($ah);
                        if (! $afh || ! is_object($afh)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file'));
                        } elseif ($afh->isError()) {
                            $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                        } else {
                            if (
                                ! $afh->upload(
                                    $_FILES['input_file']['tmp_name'],
                                    $_FILES['input_file']['name'],
                                    $_FILES['input_file']['type'],
                                    $sanitizer->sanitize($request->get('file_description')),
                                    $changes
                                )
                            ) {
                                  $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'file_upload_err', $afh->getErrorMessage()));
                                  $was_error = true;
                            }
                        }
                    }

            // Add new cc if any
                    if ($add_cc) {
                        $changed |= $ah->addCC($add_cc, $sanitizer->sanitize($request->get('cc_comment')), $changes, true);
                    }

                  // Update the 'last_update_date' artifact field
                  // Should check that the artifact was really modified?
                    $res_last_up = $ah->update_last_update_date();
                }
            }
            $GLOBALS['Response']->addFeedback('info', $feedback);

        //Delete cc if any
            $delete_cc = $request->get('delete_cc');
            if ($delete_cc) {
                      $ath->deleteCC($delete_cc);
            }

        //Delete attached files
            $delete_attached = $request->get('delete_attached');
            if ($delete_attached) {
                $ath->deleteAttachedFiles($delete_attached);
            }

        //Delete dependencies if any
            $delete_depend = $request->get('delete_depend');
            if ($delete_depend) {
                      $ath->deleteDependencies($delete_depend);
            }


        //update group history
            $old_value = $ath->getName();
            (new ProjectHistoryDao())->groupAddHistory('mass_change', $old_value, $group_id);

        //      Show just one feedback entry if no errors
            if (! $was_error) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index', 'mass_update_success'));
                if ($ath->getStopNotification()) {
                            $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_index', 'notification_stopped'));
                }
            }
            require('./browse.php');
            break;
        case 'postaddcomment':
            //  Attach a comment to an artifact
            //  Used by non-admins
            $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            }

            $comment = $request->get('comment');
            $email   = $request->get('email');
            $changes = [];
            if ($comment) {
                $vFormat        = new Valid_WhiteList('comment_format', [Artifact::FORMAT_HTML, Artifact::FORMAT_TEXT]);
                $comment_format = $request->getValidated('comment_format', $vFormat, Artifact::FORMAT_TEXT);
                if (! $ah->addComment($comment, $email, $changes, $comment_format)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_saved_comment'));
                }
            }

            // Add CC
            if ($add_cc = trim($request->get('add_cc'))) {
                $ah->addCC($add_cc, $sanitizer->sanitize($request->get('cc_comment')), $changes);
            }


            //  Attach file to this Artifact.
            if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                if (! util_check_fileupload($_FILES['input_file']['tmp_name'])) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'invalid_filename_attach'));
                }

                $afh = new ArtifactFileHtml($ah);
                if (! $afh || ! is_object($afh)) {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_create_file'));
                } elseif ($afh->isError()) {
                    $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                } else {
                    if (
                        ! $afh->upload(
                            $_FILES['input_file']['tmp_name'],
                            $_FILES['input_file']['name'],
                            $_FILES['input_file']['type'],
                            $sanitizer->sanitize($request->get('file_description')),
                            $changes
                        )
                    ) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_attach_file', $afh->getErrorMessage()));
                    }
                }
            }

            // send an email to notify the user of the bug update
            $agnf      = new ArtifactGlobalNotificationFactory();
            $addresses = $agnf->getAllAddresses($ath->getID(), true);
            $ah->mailFollowupWithPermissions($addresses, $changes);
            $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
            break;
        case 'editcomment':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }
            $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } else {
                require('./edit_comment.php');
            }
            break;
        case 'getcomment':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }
            $ah = new ArtifactHtml($ath, $aid);
            if ($ah) {
                require('./get_comment.php');
            } else {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            }
            break;
        case 'import':
            if (! user_isloggedin()) {
                exit_not_logged_in();
                return;
            }

       //  make sure this person has permission to import artifacts
            if (! $ath->userIsAdmin()) {
                exit_permission_denied();
            }
            $user_id = UserManager::instance()->getCurrentUser()->getId();



            if ($group_id && $atid && $user_id) {
                $import          = new ArtifactImportHtml($ath, $art_field_fact, $group);
                 $mode           = $request->get('mode');
                 $artifacts_data = [];
                if ($mode == "parse") {
                    $import->displayParse($_FILES['csv_filename']['tmp_name']);
                } elseif ($mode == "import") {
                    $count_artifacts = $request->getValidated('count_artifacts', 'uint', 0);
                    $parsed_labels   = $request->get('parsed_labels');
                    $aid_column      = $request->get('aid_column');
                    for ($i = 0; $i < $count_artifacts; $i++) {
                        for ($c = 0; $c < count($parsed_labels); $c++) {
                            $label           = $parsed_labels[$c];
                               $var_name     = "artifacts_data_" . $i . "_" . $c;
                               $data[$label] = $request->get($var_name);
                               //echo "insert $label,".$$var_name." into data<br>";
                        }
                        $artifacts_data[] = $data;
                    }
                    $import->displayImport($parsed_labels, $artifacts_data, $aid_column, $count_artifacts);
                    require('./browse.php');
                } elseif ($mode == "showformat") {
                    $import->displayShowFormat();
                } else {
                    $import->displayCSVInput($atid, $user_id);
                }
            } else {
                exit_no_group();
            }
            break;
        case 'export':
            require('./export.php');
            break;
        case 'updatecomment':
            $artifact_id = $request->get('artifact_id');
            if (user_isloggedin() && $request->exist('followup_update')) {
                $followup_update = $request->get('followup_update');
                $ah              = new ArtifactHtml($ath, $artifact_id);
                $vFormat         = new Valid_WhiteList('comment_format', [Artifact::FORMAT_HTML, Artifact::FORMAT_TEXT]);
                $comment_format  = $request->getValidated('comment_format', $vFormat, Artifact::FORMAT_TEXT);
                $changes         = [];
                if ($ah->updateFollowupComment($request->get('artifact_history_id'), $followup_update, $changes, $comment_format)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('tracker_common_artifact', 'followup_upd_succ'));
                    $agnf      = new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($ath->getID(), true);
                    $ah->mailFollowupWithPermissions($addresses, $changes);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'followup_upd_fail'));
                }
            }
            $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&aid=' . (int) $artifact_id . '&func=detail');
            break;
        case 'browse':
                $masschange = false;
            if ($request->get('change_report_column')) {
                $report_id  = $request->getValidated('report_id', 'uint');
                $field_name = $request->getValidated('change_report_column', 'string');
                $arf        = new ArtifactReportFactory();
                if ($report = $arf->getArtifactReportHtml($report_id, $atid)) {
                    $report->toggleFieldColumnUsage($field_name);
                }
                $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
            } elseif ($request->get('change_report_query')) {
                $report_id  = $request->getValidated('report_id', 'uint');
                $field_name = $request->getValidated('change_report_query', 'string');
                $arf        = new ArtifactReportFactory();
                if ($report = $arf->getArtifactReportHtml($report_id, $atid)) {
                    $report->toggleFieldQueryUsage($field_name);
                }
                $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&func=browse');
            } elseif ($reordercolumns = $request->get('reordercolumns')) {
                if (is_array($reordercolumns)) {
                    $report_id = $request->getValidated('report_id', 'uint');
                    $arf       = new ArtifactReportFactory();
                    if ($report = $arf->getArtifactReportHtml($report_id, $atid)) {
                        //Todo: check that the user can update the report
                        $id           = key($reordercolumns);
                        $new_position = current($reordercolumns);
                        next($reordercolumns);
                        $dao = new ArtifactReportFieldDao(CodendiDataAccess::instance());
                        if ($new_position == '-1') {
                            $new_position = 'end';
                        } else {
                            $dar = $dao->searchByReportIdAndFieldName($report_id, $new_position);
                            if ($dar && ($row = $dar->getRow())) {
                                $new_position = $row['place_result'];
                            } else {
                                $new_position = '--'; //don't change anything
                            }
                        }
                        $dao->updateResultRanking($id, $report_id, $new_position);
                    }
                }
                if ($request->isAjax()) {
                    exit;
                }
            } elseif ($resizecolumns = $request->get('resizecolumns')) {
                if (is_array($resizecolumns)) {
                    $report_id = $request->getValidated('report_id', 'uint');
                    $arf       = new ArtifactReportFactory();
                    if ($report = $arf->getArtifactReportHtml($report_id, $atid)) {
                        //Todo: check that the user can update the report
                        $dao = new ArtifactReportFieldDao(CodendiDataAccess::instance());
                        $dao->resizeColumns($report_id, $resizecolumns);
                    }
                }
                if ($request->isAjax()) {
                    exit;
                }
            } else {
                require('./browse.php');
            }
            break;
        case 'masschange':
            $masschange = true;
            $export     = false;
            require('./browse.php');
            break;

        case 'masschange_detail':
            $ah = new ArtifactHtml($ath);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } else {
                require('./masschange_detail.php');
            }
            break;
        case 'detail':
                //      users can modify their own tickets if they submitted them
                //      even if they are not artifact admins
                $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                    exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
                    // Check if users can browse anonymously
                if (! user_isloggedin() && ! $ath->userCanView()) {
                    exit_not_logged_in();
                }

                if (user_ismember($group_id)) {
                        require('./mod.php');
                } else {
                        require('./detail.php');
                }
            }
            break;
        case 'copy':
            $ah = new ArtifactHtml($ath, $aid);
            if (! $ah || ! is_object($ah)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
            } elseif ($ah->isError()) {
                exit_error($Language->getText('global', 'error'), $ah->getErrorMessage());
            } else {
          // Check if users can browse anonymously
                if (! user_isloggedin() && ! $ath->allowsAnon()) {
                    exit_not_logged_in();
                }

              // !!!! need to specify here for which users we allow to copy artifacts !!!!
                if (user_ismember($group_id)) {
                            require('./copy.php');
                } else {
                     exit_error($Language->getText('global', 'error'), $Language->getText('tracker_index', 'not_create_art'));
                }
            }
            break;
        case 'toggle_section':
            $collapsable_sections = ['results', 'query'];
            $em->processEvent('tracker_collapsable_sections', ['sections' => &$collapsable_sections]);
            if (in_array($request->get('section'), $collapsable_sections)) {
                $current_user = UserManager::instance()->getCurrentUser();
                $pref_name    = 'tracker_' . (int) $atid . '_hide_section_' . $request->get('section');
                if ($current_user->getPreference($pref_name)) {
                    $current_user->delPreference($pref_name);
                } else {
                    $current_user->setPreference($pref_name, 1);
                }
            }
            if (! $request->isAjax()) {
                require('./browse.php');
            }
            break;
        default:
            require('./browse.php');
            break;
    } // switch
} elseif ($group_id) {
        //  get the Group object
        $pm    = ProjectManager::instance();
        $group = $pm->getProject($group_id);
    if (! $group || ! is_object($group) || $group->isError()) {
            exit_no_group();
    }
        $atf = new ArtifactTypeFactory($group);
    if (! $group || ! is_object($group) || $group->isError()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_import_admin', 'not_get_atf'));
    }

        // Get the artfact type list
        $at_arr = $atf->getArtifactTypes();

        $pv = $request->get('pv');

        //required params for site_project_header();
        $params['toptab']   = 'tracker';
        $params['pagename'] = 'trackers';
        $params['title']    = $Language->getText('tracker_index', 'trackers_for', $group->getPublicName());
        $params['help']     = 'tracker-v3.html';
        $params['pv']       = $pv ? $pv : '';

        site_project_header($group, $params);
        echo '<strong>';
        // Admin link and create link are only displayed if the user is a project administrator
    if (user_ismember($group_id, 'A')) {
        echo '<a href="/tracker/admin/?group_id=' . (int) $group_id . '">' . $Language->getText('tracker_index', 'admin_all_trackers') . '</a>';
        if ($params['help']) {
            echo ' | ';
        }
    }
        echo "</strong><p>";

    if (! $at_arr || count($at_arr) < 1) {
        echo '<h2>' . $Language->getText('tracker_index', 'no_accessible_trackers_hdr') . '</h2>';

        echo "<p><div class='alert alert-danger'> " . $Language->getText('tracker_index', 'feature_is_deprecated')  .  "</div></p>";

        echo '<p>' . $Language->getText('tracker_index', 'no_accessible_trackers_msg') . '</p>';
    } else {
        echo "<p><div class='alert alert-danger'> " . $Language->getText('tracker_index', 'feature_is_deprecated')  .  "</div></p>";

        echo "<p>" . $Language->getText('tracker_index', 'choose_tracker');
        if (! $pv) {
            echo " ( <A HREF='?group_id=" . (int) $group_id . "&pv=1'><img src='" . util_get_image_theme("ic/printer.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> )";
        }
        echo "<p>";

        // Put the result set (list of trackers for this group) into a column with folders
        for ($j = 0; $j < count($at_arr); $j++) {
            if ($at_arr[$j]->userCanView()) {
                echo '
                        <a href="/tracker/?atid=' . (int) ($at_arr[$j]->getID()) .
                    '&group_id=' . (int) $group_id . '&func=browse">' .
                    html_image("ic/tracker20w.png", ["border" => "0", "width" => "20", "height" => "20"], 0) .
                    '&nbsp;' .
                     $hp->purify(SimpleSanitizer::unsanitize($at_arr[$j]->getName()), CODENDI_PURIFIER_CONVERT_HTML)  . '</a> ';
                // Only show number of artifacts if the user has full access on the tracker.
                if ($at_arr[$j]->userHasFullAccess()) {
                    echo '( <strong>' . (int) ($at_arr[$j]->getOpenCount()) . ' ' . $Language->getText('tracker_index', 'open') . ' / ' . (int) ($at_arr[$j]->getTotalCount()) . ' ' . $Language->getText('tracker_index', 'total') . '</strong> )';
                }
                echo '<br />' . $hp->purify(SimpleSanitizer::unsanitize($at_arr[$j]->getDescription()), CODENDI_PURIFIER_BASIC, $group_id)  . '<p>';
            }
        }
    }
        echo site_project_footer($params);
} else {
    exit_no_group();
}
