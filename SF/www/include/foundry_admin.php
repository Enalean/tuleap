<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($func) {
	/*
		Make a change to the database
	*/
	if ($func=='rmproject') {
		/*
			remove a project from this foundry
		*/
		$feedback .= ' Removed a Project ';
		db_query("DELETE FROM foundry_preferred_projects WHERE foundry_id='$group_id' AND group_id='$rm_id'");

		group_add_history ('removed project',$rm_id,$group_id);

	} else if ($func=='rmuser') {
		/*
			remove a user from this foundry
		*/
		$res=db_query("DELETE FROM user_group WHERE group_id='$group_id' AND user_id='$rm_id' AND admin_flags <> 'A'");
		if (!$res || db_affected_rows($res) < 1) {
			$feedback .= ' User Not Removed - You cannot remove admins from a project. 
			You must first turn off their admin flag and/or find another admin for the project ';
		} else {
			$feedback .= ' Removed a User ';
			group_add_history ('removed user',$rm_id,$group_id);
		}

	} else if ($func=='addproject') {
		/*
			Add a project to this foundry
		*/
		$res_newgroup = db_query("SELECT group_id FROM groups WHERE unix_group_name='$form_unix_name'");

		if (db_numrows($res_newgroup) > 0) {
			//user was found
			$form_newuid = db_result($res_newgroup,0,'group_id');

			//if not already a member, add them
			$res_member = db_query("SELECT * FROM foundry_preferred_projects WHERE group_id='$form_newuid' AND foundry_id='$group_id'");
			if (db_numrows($res_member) < 1) {
				//not a member
				group_add_history ('added project',$rm_id,$group_id);
				db_query("INSERT INTO foundry_preferred_projects (group_id,foundry_id,rank) VALUES ('$form_newuid','$group_id','$rank')");
				$feedback .= " Project was added ";
			} else {
				//was a member
				$feedback .= " Project was already a featured member ";
			}
		} else {
			//user doesn't exist
			$feedback .= "That project does not exist on SourceForge";
		}

		//data has changed, so create a new object for reference below
		//there must be a better way to do this.......
		$foundry = new Foundry($group_id);

	} else if ($func=='setfoundrydata') {
		$res=db_query("UPDATE foundry_data SET guide_image_id='$guide_image_id',logo_image_id='$logo_image_id',trove_categories='$trove_categories' WHERE foundry_id='$group_id'");
		if (db_affected_rows($res) < 1) {
			echo db_error();
			$feedback .= " Update failed ";
		} else {
			group_add_history ('data updated','',$group_id);
			$feedback .= " Data Updated ";
		}
	} else if ($func=='adduser') {
		/*
			Add a user to this project
			They don't need unix access
		*/
		include ('account.php');
		account_add_user_to_group ($group_id,$form_unix_name);
	}
}


project_admin_header(array('title'=>"Project Admin: ".group_getname($group_id),'group'=>$group_id));

/*

	Show the list of member projects

*/

echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top("Featured Projects");

$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,foundry_preferred_projects.rank ".
	"FROM groups,foundry_preferred_projects ".
	"WHERE foundry_preferred_projects.group_id=groups.group_id ".
	"AND foundry_preferred_projects.foundry_id='$group_id' ".
	"ORDER BY rank ASC";

$res_grp=db_query($sql);
$rows=db_numrows($res_grp);

if (!$res_grp || $rows < 1) {
	echo 'No Projects';
	echo db_error();
} else {
	print '<TABLE WIDTH="100%" BORDER="0">
';
	for ($i=0; $i<$rows; $i++) {
		print '
		<FORM ACTION="'. $PHP_SELF .'" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmproject">'.
		'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'. db_result($res_grp,$i,'group_id') .'">'.
		'<TR><TD ALIGN="center"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
		'<TD><A href="/projects/'. strtolower(db_result($res_grp,$i,'unix_group_name')) .'/">'. 
		db_result($res_grp,$i,'group_name') .'</A> ( '. db_result($res_grp,$i,'rank') .' )</TD></TR>';
	}
	print '</TABLE>
';
}

$HTML->box1_bottom();

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


/*

	Show the members of this project

*/

$HTML->box1_top("Group Members");

$res_memb = db_query("SELECT user.realname,user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE user.user_id=user_group.user_id ".
		"AND user_group.group_id=$group_id");

	print '<TABLE WIDTH="100%" BORDER="0">
';
	while ($row_memb=db_fetch_array($res_memb)) {
		print '
		<FORM ACTION="/foundry/'.$expl_pathinfo[2].'/admin/" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">'.
		'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'.$row_memb['user_id'].'">'.
		'<TR><TD ALIGN="center"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
		'<TD><A href="/users/'.$row_memb['user_name'].'/">'.$row_memb['realname'].'</A></TD></TR>';
	}
	print '</TABLE>
';

echo '
	<TR><TD colspan="2" align="center">
	&nbsp;<BR>
	<A href="/project/admin/userperms.php?group_id='. $group_id.'">[Edit Member Permissions]</A>';

$HTML->box1_bottom();


echo '</TD></TR>

<TR valign=top><TD width=50%>';

/*

	Tool admin pages

*/

$HTML->box1_top('Tool Admin');

echo '
<A HREF="/news/submit.php?group_id='.$group_id.'">Submit Your News</A><BR>
<A HREF="/foundry/'.$expl_pathinfo[2].'/admin/news/">Foundry-wide News Admin</A><BR>
<A HREF="/forum/admin/?group_id='.$group_id.'">Forum Admin</A><BR>
<A HREF="/foundry/'.$expl_pathinfo[2].'/admin/html/">Edit FreeForm HTML</A><BR>
';

$HTML->box1_bottom();

echo '<P>';

$HTML->box1_top('Tool Admin');

$images_res=db_query("SELECT id,description FROM db_images WHERE group_id='$group_id'");

//echo db_error();

echo '<FORM ACTION="/foundry/'.$expl_pathinfo[2].'/admin/" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="setfoundrydata">
	<TABLE>
	<TR><TD><B>Guide Image:</B></TD><TD>'. html_build_select_box ($images_res, 'guide_image_id', $foundry->getGuideImageID()  ,false) .'</TR>
	<TR><TD><B>Logo Image:</B></TD><TD>'. html_build_select_box ($images_res, 'logo_image_id', $foundry->getLogoImageID()  ,false) .'</TD></TR>
	<TR><TD><B>Trove Categories:</B><BR>(must comma separate)</TD><TD><INPUT TYPE="TEXT" NAME="trove_categories" VALUE="'. $foundry->getTroveCategories() .'" SIZE="6"></TD></TR>
	<TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Update"></TD></TR>
	</TABLE>
	</FORM>
';

$HTML->box1_bottom();

echo '</TD>

<TD>&nbsp;</TD>

<TD width=50%>';

/*
	Add project/users
*/

$HTML->box1_top('Add Projects/Users');

print '
	<FORM ACTION="/foundry/'.$expl_pathinfo[2].'/admin/" METHOD="POST">
	<TABLE>
	<TR><TD><B>Add Project:</B></TD><TD><INPUT TYPE="RADIO" NAME="func" VALUE="addproject" CHECKED></TR>
	<TR><TD><B>Add User:</B></TD><TD><INPUT TYPE="RADIO" NAME="func" VALUE="adduser"></TD></TR>
	<TR><TD><B>Unix Name:</B></TD><TD><INPUT TYPE="TEXT" NAME="form_unix_name" VALUE=""></TD></TR>
	<TR><TD><B>Rank (for projects):</B></TD><TD><INPUT TYPE="TEXT" NAME="rank" VALUE="" SIZE="2" MAXLENGTH="2"></TD></TR>
	<TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add"></TD></TR>
	</TABLE>
	</FORM>
';

$HTML->box1_bottom();

echo '</TD>
</TR>
</TABLE>';

project_admin_footer(array());

?>
