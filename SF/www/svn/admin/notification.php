<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//



// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
if ($post_changes) {
    $ret = svn_data_update_notification($group_id,$form_mailing_list,$form_mailing_header);
    if ($ret) {
	$feedback = "Email Notification updated succesfully";
    } else {
	$feedback = "Email Notification update failed: ".db_error();
    }
}

// Display the form
svn_header_admin(array ('title'=>'Subversion Administration - General Settings',
		      'help' => 'SubversionAdministrationInterface.html#SubversionEmailNotification'));

$project=project_get_object($group_id);
$svn_mailing_list = $project->getSVNMailingList();
$svn_mailing_header = $project->getSVNMailingHeader();

echo '
       <H2>Subversion Administration - Email Notification</H2>
       <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="notification">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
       <H3>E-Mail notification on Commits</H3>
       <p><I>Each commit event can also be notified via email to specific 
       recipients or mailing lists (comma separated). A specific subject header
       for the email message can also be specified.</I></p>

       <P><b>Mail to</b></p><p><INPUT TYPE="TEXT" SIZE="70" NAME="form_mailing_list" VALUE="'.$svn_mailing_list.'"></p>

       <p><b>Subject header</b></p>
       <p><INPUT TYPE="TEXT" SIZE="20" NAME="form_mailing_header" VALUE="'.$svn_mailing_header.'"></p>

        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit"></p></FORM>';

svn_footer(array());
?>
