<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require 'pre.php';    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
require 'vars.php';
require('../forum/forum_utils.php');

if ($show_confirm) {

	$HTML->header(array('title'=>'Registration Complete'));

	$sql="SELECT * FROM groups WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);

	echo '
	<H2>Final Confirmation</H2>
	<P>
	<B><FONT COLOR="RED">Do NOT backarrow!</FONT></B>
	<P>
	<FORM action="'.$PHP_SELF.'" method="post">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="rand_hash" VALUE="'.$rand_hash.'">
	<B>Description:</B><BR>
	<TEXTAREA name="form_purpose" wrap="virtual" cols="70" rows="12">'.db_result($result,0,'register_purpose').'</TEXTAREA>
	<P>
	<B>Intellectual Property:</B><BR>
	<TEXTAREA name="form_patents" wrap="virtual" cols="70" rows="6">'.db_result($result,0,'patents_ips').'</TEXTAREA>
	<P>
	<B>Other Software Required:</B><BR>
	<TEXTAREA name="form_required_sw" wrap="virtual" cols="70" rows="6">'.db_result($result,0,'required_software').'</TEXTAREA>
	<P>
	<B>Other Comments:</B><BR>
	<TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">'.db_result($result,0,'other_comments').'</TEXTAREA>

	<P>	<B>Full Name:</B><BR>
	<INPUT size="40" maxlength="40" type="text" name="form_full_name" VALUE="'.db_result($result,0,'group_name').'">
	<P>
	<B>Unix Name:</B><BR>
	'.db_result($result,0,'unix_group_name').'
	<P>
	<B>License:</B><BR>
	<SELECT NAME="form_license">
	';

	while (list($k,$v) = each($LICENSE)) {
		print "<OPTION value=\"$k\"";
		if ($k==db_result($result,0,'license')) {
			echo ' SELECTED';
		}
		print ">$v\n";
	}
	echo '</SELECT>';
	echo '
	<P>
	<B>If Other License:</B><BR>
	<TEXTAREA name="form_license_other" wrap=virtual cols=60 rows=10>'.db_result($result,0,'license_other').'</TEXTAREA>
	<P>
	If you agree, your project will be created. If you disagree, it will be deleted from the system.
	<P>
	<INPUT type=submit name="i_agree" value="I AGREE"> <INPUT type=submit name="i_disagree" value="I DISAGREE">
	</FORM>';

	$HTML->footer(array());

} else if ($i_agree && $group_id && $rand_hash) {
	/*

		Finalize the db entries

	*/

	$result=db_query("UPDATE groups SET status='P', ".
		"register_purpose='".htmlspecialchars($form_purpose)."', ".
		"required_software='".htmlspecialchars($form_required_sw)."', ".
		"patents_ips='".htmlspecialchars($form_patents)."', ".
		"other_comments='".htmlspecialchars($form_comments)."', ".
		"group_name='$form_full_name', license='$form_license', ".
		"license_other='".htmlspecialchars($form_license_other)."' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	if (db_affected_rows($result) < 1) {
		exit_error('Error','UDPATING TO ACTIVE FAILED. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' '.db_error());
	}

	// define a module
	$result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".group_getunixname($group_id)."')");
	if (!$result) {
		exit_error('Error','INSERTING FILEMODULE FAILED. <B>PLEASE</B> report to admin@'.$GLOBALS['sys_default_domain'].' '.db_error());
	}

	// make the current user an admin
	$result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags) VALUES ("
		. user_getid() . ","
		. $group_id . ","
		. "'A'," // admin flags
		. "2," // bug flags
		. "2)"); // forum_flags	
	if (!$result) {
		exit_error('Error','SETTING YOU AS OWNER FAILED. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' '.db_error());
	}

	//Add a couple of forums for this group
	forum_create_forum($group_id,'Open Discussion',1,'General Discussion');
	forum_create_forum($group_id,'Help',1,'Get Help');
	forum_create_forum($group_id,'Developers',0,'Project Developer Discussion');

	//Set up some mailing lists
	//will be done at some point. needs to communicate with geocrawler

	//
	$HTML->header(array('title'=>'Registration Complete'));
	
	?>

	<H1>Registration Complete!</H1>
	<P>Your project has been submitted to the <?php print $GLOBALS['sys_name']; ?> Administrators. 
	Within 24 hours, you will receive decision notification and further 
	instructions.
	<P>
	Thank you for using <?php print $GLOBALS['sys_name']; ?>.
	<P>

	<?php
	$HTML->footer(array());

} else if ($i_disagree && $group_id && $rand_hash) {

	$HTML->header(array('title'=>'Registration Deleted'));
	$result=db_query("DELETE FROM groups ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	echo '
		<H2>Project Deleted</H2>
		<P>
		<B>Please try again in the future.</B>';
	$HTML->footer(array());

} else {
	exit_error('Error','This is an invalid state. Some form variables were missing.
		If you are certain you entered everything, <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' and
		include info on your browser and platform configuration');

}

?>

