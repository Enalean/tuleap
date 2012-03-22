<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
//	Originally written by Laurent Julliard 2004, Codendi Team, Xerox
//

require_once('common/svn/SvnNotification.class.php');
$svnNotification = new SvnNotification();
$pm              = ProjectManager::instance();

// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));

$vPath = new Valid_String('path');
if ($request->exist('path') && $request->valid($vPath)) {
    $path             = $request->get('path');
} else {
    $path = '/';
}

if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $postChanges = $request->get('post_changes');
    switch ($postChanges) {
        case 'subject_header' :
            $vHeader = new Valid_String('form_mailing_header');
            if($request->valid($vHeader)) {
                $mailingHeader = $request->get('form_mailing_header');
                if ($pm->setSVNHeader($group_id, $mailingHeader)) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification','upd_header_success'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_header_fail'));
                }
            }
            break;
        case 'list_of_paths' :
            if ($request->exist('paths_to_delete')) {
                $vPathToDelete = new Valid_Array('paths_to_delete');
                if($request->valid($vPathToDelete)) {
                    $PathsToDelete    = $request->get('paths_to_delete');
                    $svnNotification->removeSVNNotification($PathsToDelete, $group_id);
                }
            }
            break;
        case 'path_mailing_list' :
            $vPath       = new Valid_String('form_path');
            $formPath    = $request->get('form_path');
            $result      = util_cleanup_email_list($request->get('form_mailing_list'));
            $mailingList = join(', ', $result['clean']);
            $badList     = join(', ', $result['bad']);
            if(!empty($mailingList) && !empty($formPath) && $request->valid($vPath)) {
                if ($svnNotification->setSVNMailingList($group_id, $mailingList, $formPath)) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification','upd_email_success'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_email_fail'));
            }
            if (!empty($badList)) {
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('svn_admin_notification','upd_email_bad_adr', $badList));
            }
            break;
        default :
            break;
    }
}

$hp = Codendi_HTMLPurifier::instance();

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_general_settings','gen_settings'),
		                  'help' => 'SubversionAdministrationInterface.html#SubversionEmailNotification'));
$pm->clear($group_id);
$project = $pm->getProject($group_id);
$svn_mailing_header = $project->getSVNMailingHeader();
$svn_mailing_list = $svnNotification->getSVNMailingList($group_id, $path);

// Mail header
echo '
       <h2>'.$Language->getText('svn_admin_notification','email').'</h2>
       '.$Language->getText('svn_admin_notification','mail_comment').'
       <form action="" method="post">
           <input type="hidden" name="group_id" value="'.$group_id.'">
           <input type="hidden" name="post_changes" value="subject_header">
           <table>
               <th align="left">'.$Language->getText('svn_admin_notification','header').'</th>
               <tr>
                   <td><input type="text" name="form_mailing_header" value="'.$hp->purify($svn_mailing_header).'"></td>
                   <td><input type="submit" name="submit" value="'.$Language->getText('global','btn_submit').'"></td>
               </tr>
           </table>
       </form>';

// List of paths & mail addresses (+delete)
// TODO
$svn_notifications_details = $svnNotification->getSVNEventNotificationDetails($group_id);
$content = '<table>';
$content .= '<input type="hidden" name="group_id" value="'.$group_id.'">';
$content .= '<input type="hidden" name="post_changes" value="list_of_paths">';
$content .= html_build_list_table_top(array('SVN notification list', 'SVN monitored path' , 'Delete?'), false, false , false);
$rowBgColor  = 0;

foreach ($svn_notifications_details as $item) {
    $content .= '<tr class="'. html_get_alt_row_color(++$rowBgColor) .'">';
    $content .= '<td>'. $hp->purify($item['svn_events_mailing_list']) .'</td>';
    $content .= '<td>'. $hp->purify($item['path']) .'</td><td>';
    $content .= '<input type="checkbox" value="'. $item['path'] .'" name="paths_to_delete[]" >';
    $content .= '</td></tr>';
}
$content .= '<tr><td colspan="2"><input type="submit" value="Delete"></td></tr>';
$content .= '</tbody></table>';
echo '
       <br/>
       <form action="" method="post">'.$content.'</form>';

// Add a path & mail addresses
echo '
       <br/>
       <form action="" method="post">
           <input type="hidden" name="group_id" value="'.$group_id.'">
           <input type="hidden" name="post_changes" value="path_mailing_list">
           <table width="100%">
               <tr>
                   <td width="10"><b>'.$Language->getText('svn_admin_notification','notification_path').'</b></td>
                   <td><input type="text" size="50%" name="form_path" value="'.$hp->purify($path).'"></td>
               </tr>
               <tr>
                   <td width="10"><b>'.$Language->getText('svn_admin_notification','mail_to').'</b></td>
                   <td><input type="text" size="50%" name="form_mailing_list" value="'.$hp->purify($svn_mailing_list).'"></td>
               </tr>
               <tr>
                   <td><input type="submit" name="submit" value="'.$Language->getText('global','btn_submit').'"></td>
               </tr>
           </table>
       </form>';

svn_footer(array());

?>
