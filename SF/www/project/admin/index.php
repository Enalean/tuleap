<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require ('account.php');

// get current information
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1) {
    exit_error("Invalid Group","That group could not be found.");
}

//if the project isn't active, require you to be a member of the super-admin group
if (!(db_result($res_grp,0,'status') == 'A')) {
    session_require (array('group'=>1));
}

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($func) {
    /*
      updating the database
    */
    if ($func=='adduser') {
	/*
	  add user to this project
	*/
	$res = account_add_user_to_group ($group_id,$form_unix_name);

	if ($res) {
	    group_add_history ('Added User',$form_unix_name,$group_id);
	}

    } else if ($func=='rmuser') {
	/*
	  remove a user from this portal
	*/
	$res=db_query("DELETE FROM user_group WHERE group_id='$group_id' AND user_id='$rm_id' AND admin_flags <> 'A'");
	if (!$res || db_affected_rows($res) < 1) {
	    $feedback .= ' User Not Removed - You cannot remove admins from a project. 
			You must first turn off their admin flag and/or find another admin for the project ';
	} else {
	    $feedback .= ' Removed a User ';
	    group_add_history ('removed user',$rm_id,$group_id);
	}
    }

}

project_admin_header(array('title'=>"Project Admin: ".group_getname($group_id),'group'=>$group_id));

/*
	Show top box listing trove and other info
*/

echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top("Group Edit: " . group_getname($group_id)); 

print '&nbsp;
<BR>
Short Description: '. db_result($res_grp,0,'short_description') .'
<P>
Homepage Link: <B>'. db_result($res_grp,0,'homepage') .'</B>
<P align=center>
<A HREF="http://'.$GLOBALS['sys_cvs_host'].'/cvstarballs/'. db_result($res_grp,0,'unix_group_name') .'-cvsroot.tar.gz">[ Download Your Nightly CVS Tree Tarball ]</A>
<P>
<B>Trove Categorization Info</B> - This group is in the following Trove categories:

<UL>';

// list all trove categories
$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
			 .'trove_cat.trove_cat_id AS trove_cat_id '
			 .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
			 .'trove_group_link.trove_cat_id AND trove_group_link.group_id='.$group_id
			 .' ORDER BY trove_cat.fullpath');
while ($row_trovecat = db_fetch_array($res_trovecat)) {
    print ('<LI>'.$row_trovecat['fullpath'].' '
	   .help_button('trove_cat',$row_trovecat['trove_cat_id'])."\n");
}

print '
</UL>
<P align="center">
<A href="/project/admin/group_trove.php?group_id='.$group_id.'">'
.'<B>[Edit Trove Categorization]</B></A>
';

$HTML->box1_bottom(); 

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


$HTML->box1_top("Group Members");

/*

	Show the members of this project

*/

$res_memb = db_query("SELECT user.realname,user.user_id,user.user_name ".
		     "FROM user,user_group ".
		     "WHERE user.user_id=user_group.user_id ".
		     "AND user_group.group_id=$group_id");

print '<TABLE WIDTH="100%" BORDER="0">';

while ($row_memb=db_fetch_array($res_memb)) {
    print '<FORM ACTION="'. $PHP_SELF .'" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">'.
	'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'.$row_memb['user_id'].'">'.
	'<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">'.
	'<TR><TD ALIGN="MIDDLE"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
	'<TD><A href="/users/'.$row_memb['user_name'].'/">'.$row_memb['realname'].'&nbsp;&nbsp;('.$row_memb['user_name'].') </A></TD></TR>';
}

print '</TABLE> <HR NoShade SIZE="1">';

/*
	Add member form
*/

echo '
          <FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
          <INPUT TYPE="hidden" NAME="func" VALUE="adduser">
          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">
          <TABLE WIDTH="100%" BORDER="0">
          <TR><TD><B>Login Name:</B></TD><TD><INPUT TYPE="TEXT" NAME="form_unix_name" VALUE=""></TD></TR>
          <TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add User"></TD></TR></FORM>
          </TABLE>

         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userperms.php?group_id='. $group_id.'">[Edit Member Permissions]</A>
         </div>
         </TD></TR>';
 
$HTML->box1_bottom();


echo '</TD></TR>

	<TR valign=top><TD width=50%>';

/*
	Tool admin pages
*/

$HTML->box1_top('Tool Admin');

echo '
	<BR>
	<A HREF="/docman/admin/?group_id='.$group_id.'">DocManager Admin</A><BR>
	<A HREF="/bugs/admin/?group_id='.$group_id.'">Bug Admin</A><BR>
	<A HREF="/patch/admin/?group_id='.$group_id.'">Patch Admin</A><BR>
	<A HREF="/mail/admin/?group_id='.$group_id.'">Mail Admin</A><BR>
	<A HREF="/news/admin/?group_id='.$group_id.'">News Admin</A><BR>
	<A HREF="/pm/admin/?group_id='.$group_id.'">Task Manager Admin</A><BR>
	<A HREF="/support/admin/?group_id='.$group_id.'">Support Manager Admin</A><BR>
	<A HREF="/forum/admin/?group_id='.$group_id.'">Forum Admin</A><BR>
	';

$HTML->box1_bottom(); 




echo '</TD>

	<TD>&nbsp;</TD>

	<TD width=50%>';

/*
	Show filerelease info
*/

$HTML->box1_top("File Releases"); ?>
	&nbsp;<BR>
	<CENTER>
	<A href="editpackages.php?group_id=<?php print $group_id; ?>"><B>[Edit/Add File Releases]</B></A><BR> or... <BR>
	<A href="qrs.php?group_id=<?php print $group_id; ?>"><B>[Quick Add File Release]</B></A><BR>if you know what you're doing and have only one file to release.
	</CENTER>

	<HR>
	<B>Packages:</B> <A href="/docman/display_doc.php?docid=46&group_id=1">Documentation</A> (Very Important!)

     <P><?php

	$res_module = db_query("SELECT * FROM frs_package WHERE group_id=$group_id");
while ($row_module = db_fetch_array($res_module)) {
print "$row_module[name]<BR>";
}

echo $HTML->box1_bottom(); ?>
</TD>
</TR>
</TABLE>

<?php
project_admin_footer(array());

?>
