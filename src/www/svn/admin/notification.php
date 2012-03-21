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


// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));

// TODO: validate path
if ($request->exist('path')) {
    $path = $request->get('path');
} else {
    $path = '/';
}

if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vML = new Valid_Email('form_mailing_list', ',');
    $vHeader = new Valid_String('form_mailing_header');
    if($request->valid($vML)) {
        if($request->valid($vHeader)) {
            $form_mailing_list = $request->get('form_mailing_list');
            $form_mailing_header = $request->get('form_mailing_header');
            // TODO: Update this
            //$ret = svn_data_update_notification($group_id,$form_mailing_list,$form_mailing_header);
            if ($ret) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification','upd_success'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_email_fail'));
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
    }
}

$hp = Codendi_HTMLPurifier::instance();

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_general_settings','gen_settings'),
		                  'help' => 'SubversionAdministrationInterface.html#SubversionEmailNotification'));

$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
//to be modified
$svn_mailing_list   = $project->getSVNMailingList();
$svn_mailing_header = $project->getSVNMailingHeader();

// Mail header
echo '
       <h2>'.$Language->getText('svn_admin_notification','email').'</h2>
       '.$Language->getText('svn_admin_notification','mail_comment').'
       <form action="" method="post">
           <input type="hidden" name="group_id" value="'.$group_id.'">
           <input type="hidden" name="post_changes" value="y">
           <table>
               <th>'.$Language->getText('svn_admin_notification','header').'</th>
               <tr>
                   <td><input type="text" name="form_mailing_header" value="'.$hp->purify($svn_mailing_header).'"></td>
                   <td><input type="submit" name="submit" value="'.$Language->getText('global','btn_submit').'"></td>
               </tr>
           </table>
       </form>';

// List of paths & mail addresses (+delete)
// TODO

// Add a path & mail addresses
echo '
       <br/>
       <form action="" method="post">
           <input type="hidden" name="group_id" value="'.$group_id.'">
           <input type="hidden" name="post_changes" value="y">
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
