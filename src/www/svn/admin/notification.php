<?php
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
//    Originally written by Laurent Julliard 2004, Codendi Team, Xerox
$svnNotification = new SvnNotification();
$pm              = ProjectManager::instance();
$disabled        = "";

// CAUTION!!
// Make the changes before calling svn_header_admin because
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));

$vPath = new Valid_String('path');
if ($request->exist('path') && $request->valid($vPath)) {
    $path = $request->get('path');
} else {
    $path = '/';
}

if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $postChanges = $request->get('post_changes');
    switch ($postChanges) {
        case 'subject_header':
            $vHeader = new Valid_String('form_mailing_header');
            if ($request->valid($vHeader)) {
                $mailingHeader = $request->get('form_mailing_header');
                if ($pm->setSvnHeader($group_id, $mailingHeader)) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification', 'upd_header_success'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification', 'upd_header_fail'));
                }
            }
            break;
        case 'list_of_paths':
            if ($request->exist('paths_to_delete')) {
                $vPathToDelete = new Valid_Array('paths_to_delete');
                if ($request->valid($vPathToDelete)) {
                    $PathsToDelete = $request->get('paths_to_delete');
                    $svnNotification->removeSvnNotification($group_id, $PathsToDelete);
                }
            }
            break;
        case 'path_mailing_list':
            $vPath       = new Valid_String('form_path');
            $formPath    = $request->get('form_path');
            $result      = util_cleanup_email_list($request->get('form_mailing_list'));
            $mailingList = join(', ', $result['clean']);
            $badList     = join(', ', $result['bad']);
            if (!empty($mailingList) && !empty($formPath) && $request->valid($vPath)) {
                if ($svnNotification->setSvnMailingList($group_id, $mailingList, $formPath)) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification', 'upd_email_success'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification', 'upd_email_fail'));
            }
            if (!empty($badList)) {
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('svn_admin_notification', 'upd_email_bad_adr', $badList));
            }
            break;
        default:
            break;
    }

    // Redirect to the same page just to refresh it !
    header("Location: " . $_SERVER['SCRIPT_URI'] . "?" . $_SERVER['QUERY_STRING']);
    exit();
}

$hp = Codendi_HTMLPurifier::instance();

$project = $pm->getProject($group_id);
$svn_mailing_header = $project->getSVNMailingHeader();

// testing SVN tracking
if (!$project->isSVNTracked()) {
    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification', 'svn_tracking_comment'));
    $disabled = "disabled";
}

// Display the form
svn_header_admin(array(
    'title' => $Language->getText('svn_admin_general_settings', 'gen_settings'),
    'help' => 'svn.html#subversion-email-notification'
    ));

// Mail header
echo '
       <h2>' . $Language->getText('svn_admin_notification', 'email') . '</h2>
       ' . $Language->getText('svn_admin_notification', 'mail_comment') . '
       <p><i>' . $Language->getText('svn_admin_notification', 'star_operator') . '</i></p>
       <form action="" method="post">
           <input type="hidden" name="group_id" value="' . $group_id . '">
           <input type="hidden" name="post_changes" value="subject_header">
           <label>' . $Language->getText('svn_admin_notification', 'header') . '</label>
           <input type="text" name="form_mailing_header" value="' . $hp->purify($svn_mailing_header) . '" ' . $disabled . '>
           <br/>
           <input type="submit" name="submit" value="' . $Language->getText('global', 'btn_submit') . '" class="btn" ' . $disabled . '>
       </form>';

// List of paths & mail addresses (+delete)
$svnNotificationsDetails = $svnNotification->getSvnEventNotificationDetails($group_id);
$content = '<table>';
if ($svnNotificationsDetails) {
    $content .= '<th align="left">' . $Language->getText('svn_admin_notification', 'existent_notifications') . '</th><tbody>';
    $content .= '<input type="hidden" name="group_id" value="' . $group_id . '">';
    $content .= '<input type="hidden" name="post_changes" value="list_of_paths">';
    $content .= html_build_list_table_top(array($GLOBALS['Language']->getText('svn_admin_notification', 'path_header'), $GLOBALS['Language']->getText('svn_admin_notification', 'mailing_list_header'), $GLOBALS['Language']->getText('svn_admin_notification', 'path_delete_ask')), false, false, false);
    $rowBgColor  = 0;
    foreach ($svnNotificationsDetails as $item) {
        $content .= '<tr class="' . html_get_alt_row_color(++$rowBgColor) . '">';
        $content .= '<td>' . $hp->purify($item['path']) . '</td>';
        $content .= '<td>' . $hp->purify($item['svn_events_mailing_list']) . '</td><td>';
        $content .= '<input type="checkbox" value="' . $hp->purify($item['path']) . '" name="paths_to_delete[]" >';
        $content .= '</td></tr>';
    }
    $content .= '<tr align="right"><td colspan="3"><input type="submit" value="' . $Language->getText('global', 'delete') . '" class="btn"></td></tr></tbody>';
}
$content .= '</table>';
echo '
       <br/>
       <form action="" method="post">' . $content . '</form>';

// Add a path & mail addresses
$svnMailingList = $svnNotification->getSvnMailingList($group_id, $path);
echo '
       <form action="" method="post">
           <input type="hidden" name="group_id" value="' . $group_id . '">
           <input type="hidden" name="post_changes" value="path_mailing_list">

           <label>' . $Language->getText('svn_admin_notification', 'notification_path') . '</label>
           <input type="text" name="form_path" value="' . $hp->purify($path) . '" ' . $disabled . ' />

           <label>' . $Language->getText('svn_admin_notification', 'mail_to') . '</label>
           <input type="text" size="50%" name="form_mailing_list" value="' . $hp->purify($svnMailingList) . '" ' . $disabled . ' />

           <br />

           <input type="submit" name="submit" value="' . $Language->getText('global', 'btn_submit') . '" class="btn" ' . $disabled . ' />
       </form>';

svn_footer(array());
