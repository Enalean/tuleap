<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

project_admin_header(array(title=>"Edit Aliases",group=>$group_id));
?>

<P><B>Alias List</B>
<BR><A href="editaliases-new.php?group_id=<?php print $group_id; ?>">[Add New Alias]</A>

<P>New aliases or alias changes take effect at the next 6 hour cron job.

<P><TABLE width=100% cellpadding=1 cellspacing=0 border=1>
<TR>
<TD><B>Username</B></TD>
<TD><B>Domain</B></TD>
<TD><B>Forward Address</B></TD>
<TD>&nbsp;</TD>
</TR>
<?php
	$res_mail = db_query("SELECT * FROM mailaliases WHERE group_id=$group_id");
	while ($row_mail = db_fetch_array($res_mail)) {
		print "<TR>";
		print "<TD>$row_mail[user_name]</TD>";
		print "<TD>$row_mail[domain]</TD>";
		print "<TD>$row_mail[email_forward]</TD>";
		print "<TD><A href=\"editaliases-edit.php?group_id=$group_id&form_mailid=$row_mail[mailaliases_id]\">"
			. "[Edit]</A>"
			. " <A href=\"editaliases-delete.php?group_id=$group_id&form_mailid=$row_mail[mailaliases_id]\">"
			. "[Delete]</A></TD>";
		print "</TR>";	
	}
?>
</TABLE>
<?php
project_admin_footer(array());
?>
