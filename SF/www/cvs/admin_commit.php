<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$



if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

if (!user_ismember($group_id,'A')) {
    // Must be at least Project Admin
    exit_permission_denied();
}


commits_header(array ('title'=>'CVS Administration',
		      'help' => 'CVSWebInterface.html#CVSAdministration'));

// get project name
$sql = "SELECT unix_group_name, cvs_tracker, cvs_events_mailing_list, cvs_events_mailing_header, cvs_preamble from groups where group_id=$group_id";

$result = db_query($sql);
$projectname = db_result($result, 0, 'unix_group_name');
$cvs_tracked = db_result($result, 0, 'cvs_tracker');
$cvs_mailing_list = db_result($result, 0, 'cvs_events_mailing_list');
$cvs_mailing_header = db_result($result, 0, 'cvs_events_mailing_header');
$cvs_preamble = db_result($result, 0, 'cvs_preamble');

if ($cvs_mailing_list == 'NULL') {
  $cvs_mailing_list = '';
}
$custom_mailing_header = $cvs_mailing_header;

if ($cvs_mailing_header == 'NULL') {
  $custom_mailing_header = "";
}



if (!user_isloggedin()) {
  echo "Impossible to enter admin page without an admin user of the project";

} else {

  echo "<h2>CVS Administration</H2>";
  
  echo '<FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="setAdmin">
	<h3> CVS Tracking</H3><I>When CVS tracking is on, the commits (file change, addition and 
removal) are registered in the '.$GLOBALS['sys_name'].' database so that they can be browsed and searched. Else commits are not logged in the database</I><p><b>CVS Tracking</b>&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="tracked"> '.
 	'<OPTION VALUE="1"'.(($cvs_tracked == '1') ? ' SELECTED':'').'>on</OPTION>'.
 	'<OPTION VALUE="0"'.(($cvs_tracked == '0') ? ' SELECTED':'').'>off</OPTION>'.
	'</SELECT></p>'.
        '<H3>E-Mail notification on commits</H3><p><I>Each commit event can also be notified via email to specific 
recipients or mailing lists (comma separated). A specific subject header for the email message can also be specified.</I></p>'.
        '<P><b>Mail to</b></p><p><INPUT TYPE="TEXT" SIZE="70" NAME="mailing_list" VALUE="'.$cvs_mailing_list.'"></p>'.
        '<p><b>Subject header</b>'.
        '</p><p><INPUT TYPE="TEXT" SIZE="20" NAME="custom_mailing_header" VALUE="'.$custom_mailing_header.'"></p>

<h3>CVS Preamble</h3>
<P>Introductory message displayed in project <a href="/cvs/?func=info&group_id='.$group_id.'">CVS welcome page</a>.<br>
This message should expain how to access the project CVS repository.<br>
<u>Note</u>: <i>specifying a CVS preamble here will replace the default "CVS Access" message. <br>
The default message should be OK for most projects hosted on '.$GLOBALS['sys_name'].'.</i>
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.$cvs_preamble.'</TEXTAREA>';
echo '</p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit"></p></FORM>';
}

commits_footer(array()); 
?>
