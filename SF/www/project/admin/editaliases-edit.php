<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($Submit) {
	// check security for update
	$res_alias = db_query("SELECT * FROM mailaliases WHERE mailaliases_id=$form_mailid AND group_id=$group_id");
	if (db_numrows($res_alias) < 1) {
		exit_error('Query Error','Either that alias does not exist or you are trying to edit another group
			alias. This attempt has been logged.');
	}

	if (account_namevalid($form_username)) {
		db_query("UPDATE mailaliases SET user_name='$form_username',"
			. "email_forward='$form_email' WHERE mailaliases_id=$form_mailid");	
		session_redirect("/project/admin/editaliases.php?group_id=$group_id");
	}
}

// Get current alias and check security
$res_alias = db_query("SELECT * FROM mailaliases WHERE mailaliases_id=$form_mailid AND group_id=$group_id");
if (db_numrows($res_alias) < 1) {
	exit_error('Query Error','Either that alias does not exist or you are trying to edit another group
		alias. This attempt has been logged.');
}
$row_alias = db_fetch_array($res_alias); 

project_admin_header(array('title'=>'Add Mail Alias','group'=>$group_id));
?>
<P>Editing email alias/forward for project: <B><?php html_a_group($group_id); ?></B>

<P><FORM action="editaliases-edit.php" method="post">
New username:
<BR><INPUT type="text" name="form_username" value="<?php print $row_alias['user_name']; ?>">
<P>New email forward address:
<BR><INPUT type="text" name="form_email" value="<?php print $row_alias['email_forward']; ?>">
<INPUT type="hidden" name="form_mailid" value="<?php print $form_mailid; ?>">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><INPUT type="submit" name="Submit" value="Submit">
</FORM>

<?php
project_admin_footer(array());
?>
