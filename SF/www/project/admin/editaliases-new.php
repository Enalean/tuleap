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

if ($GLOBALS[Submit]) {
	/*
		PROBLEM - no feedbacks or checks for success/failure
	*/
	if (account_namevalid($form_username)) {
		$res_domain = db_query("SELECT http_domain FROM groups WHERE group_id=$group_id");
		$row_domain = db_fetch_array($res_domain);

		$res = db_query("INSERT INTO mailaliases (group_id,domain,user_name,email_forward) VALUES "
			. "($group_id,'$row_domain[http_domain]','$form_username','$form_email')");	
		if (!$res) exit_error('Error in Query','This database query had an unknown failure. Please email
'.$GLOBALS['sys_email_admin'].' with details of the problem.');
		session_redirect("/project/admin/editaliases.php?group_id=$group_id");
	}
}

project_admin_header(array('title'=>'Add Mail Alias','group'=>$group_id));
?>
<P>Add email alias/forward for project: <B><?php html_a_group($group_id); ?></B>

<P><FORM action="editaliases-new.php" method="post">
New username:
<BR><INPUT type="text" name="form_username">
<P>New email forward address:
<BR><INPUT type="text" name="form_email">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><INPUT type="submit" name="Submit" value="Submit">
</FORM>

<?php
project_admin_footer(array());
?>
