<?php
// Copyright (c) Enalean, 2016-Present. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../cvs/commit_utils.php';


// ######################## table for summary info

$request  = HTTPRequest::instance();
$func     = $request->get('func');
$group_id = $request->get('group_id');

if (! $func) {
    $func = "";
}

switch ($func) {
    case 'browse':
        require('../cvs/browse_commit.php');
        break;
    case 'detailcommit':
        require('../cvs/detail_commit.php');
        break;
    case 'admin':
        require('../cvs/admin_commit.php');
        break;
    case 'setAdmin':
        $sql = "SELECT cvs_is_private FROM groups WHERE group_id=" . db_ei($group_id);
        $result = db_query($sql);
        $initial_settings = db_fetch_array($result);

        $feedback .= $GLOBALS['Language']->getText('cvs_index', 'config_updated');
        $status = $GLOBALS['Language']->getText('cvs_index', 'full_success');

        $tracked               = $request->get('tracked');
        $watches               = $request->get('watches');
        $mailing_list          = $request->get('mailing_list');
        $custom_mailing_header = $request->get('custom_mailing_header');
        $form_preamble         = $request->get('form_preamble');

        if (trim($custom_mailing_header) == '') {
            $mailing_header = 'NULL';
        } else {
            $mailing_header = $custom_mailing_header;
        }
        if (trim($mailing_list) == '') {
            $mailing_list = 'NULL';
        } else {
            if (!validate_emails($mailing_list)) {
                  $mailing_list = 'NULL';
                  $status = $GLOBALS['Language']->getText('cvs_index', 'partial_success');
            }
        }
        $feedback = $feedback . ' ' . $status;
        $is_private = '';
        if ($request->exist('private')) {
           //TODO check that the project is public (else the cvs is always private)
            $private = $request->get('private') ? 1 : 0;
            $is_private = ', cvs_is_private = ' . $private;
           //Raise an event if needed
            if ($initial_settings['cvs_is_private'] != $private) {
                EventManager::instance()->processEvent('cvs_is_private', array(
                  'group_id'       => $group_id,
                  'cvs_is_private' => $private,
                ));
            }
        }

        if ($mailing_list !== 'NULL') {
            $mailing_list = '"' . db_es($mailing_list) . '"';
        }
        if ($mailing_header !== 'NULL') {
            $mailing_header = '"' . db_es($mailing_header) . '"';
        }
        $query = 'update groups 
             set cvs_tracker="' . db_es($tracked) . '",
                 cvs_watch_mode="' . db_es($watches) . '",
                 cvs_events_mailing_list=' . $mailing_list . ',
                 cvs_events_mailing_header=' . $mailing_header . ',
                 cvs_preamble="' . db_es(htmlspecialchars($form_preamble)) . '" ' .
                 $is_private . '
             where group_id=' . db_ei($group_id);
        $result = db_query($query);
        require('../cvs/admin_commit.php');
        break;
    default:
      // ############################ developer access
        if (isset($commit_id)) {
            $_commit_id = $commit_id;
            require('../cvs/browse_commit.php');
        } else {
           // cvs_intro depends on the user shell access
            $shell = get_user_shell(UserManager::instance()->getCurrentUser()->getId());
            require('../cvs/cvs_intro.php');
        }

        break;
}
