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

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

//no results found
if (db_numrows($res_grp) < 1) {
	exit_error("Invalid Group","That group does not exist.");
}
$row_grp = db_fetch_array($res_grp);

// ########################### form submission, make updates
if ($submit) {
	group_add_history ('Changed Permissions','',$group_id);

	$res_dev = db_query("SELECT user_id FROM user_group WHERE group_id=$group_id");
	while ($row_dev = db_fetch_array($res_dev)) {
		//
		// cannot turn off their own admin flag -- set it back to 'A'
		//
		if (user_getid() == $row_dev['user_id']) {
			$admin_flags="admin_user_$row_dev[user_id]";
			$$admin_flags='A';
		} else {
			$admin_flags="admin_user_$row_dev[user_id]";
		}
		$bug_flags="bugs_user_$row_dev[user_id]";
		$forum_flags="forums_user_$row_dev[user_id]";
		$project_flags="projects_user_$row_dev[user_id]";
		$patch_flags="patch_user_$row_dev[user_id]";
		$support_flags="support_user_$row_dev[user_id]";
		$doc_flags="doc_user_$row_dev[user_id]";

		$res = db_query('UPDATE user_group SET ' 
			."admin_flags='".$$admin_flags."',"
			."bug_flags='".$$bug_flags."',"
			."forum_flags='".$$forum_flags."',"
			."project_flags='".$$project_flags."', "
			."doc_flags='".$$doc_flags."', "
			."patch_flags='".$$patch_flags."', "
			."support_flags='".$$support_flags."' "
			."WHERE user_id='$row_dev[user_id]' AND group_id='$group_id'");
		if (!$res) {
			echo db_error();
			$feedback .= ' Permissions Failed For '.$row_dev['user_id'].' ';
		}
	}

	$feedback .= ' Permissions Updated ';
}

$res_dev = db_query("SELECT user.user_name AS user_name,"
	. "user.user_id AS user_id, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags, "
	. "user_group.forum_flags, "
	. "user_group.project_flags, "
	. "user_group.patch_flags, "
	. "user_group.doc_flags, "
	. "user_group.support_flags "
	. "FROM user,user_group WHERE "
	. "user.user_id=user_group.user_id AND user_group.group_id=$group_id "
	. "ORDER BY user.user_name");

project_admin_header(array('title'=>'User Permissions','group'=>$group_id,
		     'help' => 'UserPermissions.html'));
?>

<h2>User Permissions</h2>
<FORM action="userperms.php" method="post">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<TABLE width="100%" cellspacing=0 cellpadding=0 border=0>
<TR><TD><B>Developer Name</B></TD>
<TD><B>Project<BR>Admin</B></TD>
<TD><B>CVS Write</B></TD>
<TD><B>Bug Tracking</B></TD>
<TD><B>Forums</B></TD>
<TD><B>Task Manager</B></TD>
<TD><B>Patch Manager</B></TD>
<TD><B>Support Manager</B></TD>
<TD><B>Doc. Manager</B></TD>
</TR>

<?php

if (!$res_dev || db_numrows($res_dev) < 1) {
	echo '<H2>No Developers Found</H2>';
} else {

	while ($row_dev = db_fetch_array($res_dev)) {
	$i++;
	print '<TR class="'. util_get_alt_row_color($i) .'"><TD>'.$row_dev['user_name'].'</TD>';
	print '
		<TD>
		<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="A" '.(($row_dev['admin_flags']=='A')?'CHECKED':'').'> Yes<BR>
		<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="" '.(($row_dev['admin_flags']=='')?'CHECKED':'').'> No
		</TD>';
	print '<TD>Yes</TD>';
	// bug selects
	print '<TD><FONT size="-1"><SELECT name="bugs_user_'.$row_dev['user_id'].'">';
	print '<OPTION value="0"'.(($row_dev['bug_flags']==0)?" selected":"").'>None';
	print '<OPTION value="1"'.(($row_dev['bug_flags']==1)?" selected":"").'>Tech Only';
	print '<OPTION value="2"'.(($row_dev['bug_flags']==2)?" selected":"").'>Tech & Admin';
	print '<OPTION value="3"'.(($row_dev['bug_flags']==3)?" selected":"").'>Admin Only';
	print '</SELECT></FONT></TD>
';
	// forums
	print '<TD><FONT size="-1"><SELECT name="forums_user_'.$row_dev['user_id'].'">';
	print '<OPTION value="0"'.(($row_dev['forum_flags']==0)?" selected":"").'>None';
	print '<OPTION value="2"'.(($row_dev['forum_flags']==2)?" selected":"").'>Moderator';
	print '</SELECT></FONT></TD>
';
	// project selects
	print '<TD><FONT size="-1"><SELECT name="projects_user_'.$row_dev['user_id'].'">';
	print '<OPTION value="0"'.(($row_dev['project_flags']==0)?" selected":"").'>None';
	print '<OPTION value="1"'.(($row_dev['project_flags']==1)?" selected":"").'>Tech Only';
	print '<OPTION value="2"'.(($row_dev['project_flags']==2)?" selected":"").'>Tech & Admin';
	print '<OPTION value="3"'.(($row_dev['project_flags']==3)?" selected":"").'>Admin Only';
	print '</SELECT></FONT></TD>
';
        // patch selects
        print '<TD><FONT size="-1"><SELECT name="patch_user_'.$row_dev['user_id'].'">';
        print '<OPTION value="0"'.(($row_dev['patch_flags']==0)?" selected":"").'>None';
        print '<OPTION value="1"'.(($row_dev['patch_flags']==1)?" selected":"").'>Tech Only';
        print '<OPTION value="2"'.(($row_dev['patch_flags']==2)?" selected":"").'>Tech & Admin';
        print '<OPTION value="3"'.(($row_dev['patch_flags']==3)?" selected":"").'>Admin Only';
        print '</SELECT></FONT></TD>
';

	// patch selects
	print '<TD><FONT size="-1"><SELECT name="support_user_'.$row_dev['user_id'].'">';
	print '<OPTION value="0"'.(($row_dev['support_flags']==0)?" selected":"").'>None';
	print '<OPTION value="1"'.(($row_dev['support_flags']==1)?" selected":"").'>Tech Only';
	print '<OPTION value="2"'.(($row_dev['support_flags']==2)?" selected":"").'>Tech & Admin';
	print '<OPTION value="3"'.(($row_dev['support_flags']==3)?" selected":"").'>Admin Only';
	print '</SELECT></FONT></TD>
';

	//documenation states - nothing or editor	
	print '<TD><FONT size="-1"><SELECT name="doc_user_'.$row_dev['user_id'].'">';
	print '<OPTION value="0"'.(($row_dev['doc_flags']==0)?" selected":"").'>None';
	print '<OPTION value="1"'.(($row_dev['doc_flags']==1)?" selected":"").'>Editor';
	print '</SELECT></FONT></TD>
';

	print '</TR>
';
}

}
?>

</TABLE>
<P align="center"><INPUT type="submit" name="submit" value="Update Developer Permissions">
</FORM>

<?php
project_admin_footer(array());
?>
