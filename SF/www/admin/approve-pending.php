<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/include/account.php');
require($DOCUMENT_ROOT.'/include/proj_email.php');
require($DOCUMENT_ROOT.'/admin/admin_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

// group public choice
if ($action=='activate') {
	/*
		update the project flag to active
	*/
	db_query("UPDATE groups SET status='A'"
		. " WHERE group_id IN ($list_of_groups)");


	/*
		now activate the admin's unix account if it isn't already
	*/
	//list of user_id's for these admins
	$admin_res=db_query("SELECT DISTINCT user.user_id FROM user,user_group ".
		"WHERE user_group.group_id IN ($list_of_groups) ".
		"AND user_group.admin_flags='A' ".
		"AND user.unix_status='N' ".
		"AND user.user_id=user_group.user_id ".
		"AND user.unix_uid='0'");

	if (db_numrows($admin_res) > 0) {

		$admin_list=result_column_to_array($admin_res,0);
		$count=count($admin_list);

		for ($i=0; $i<$count; $i++) {
			$res_user=db_query("UPDATE user SET unix_uid='" . account_nextuid() . "',unix_status='A' WHERE user_id='$admin_list[$i]'");
			if (!$res_user || db_affected_rows($res_user) < 1) {
				echo db_error();
			}
		}
	} else {
		echo db_error();
	}

	/*
		Now send the project approval emails
	*/
	$groups=explode(',',$list_of_groups);
	$count=count($groups);
	for ($i=0; $i<$count; $i++) {
		group_add_history ('approved','x',$groups[$i]);
		send_new_project_email($groups[$i]);
		usleep(250000);
	}

} else if ($action=='delete') {
	group_add_history ('deleted','x',$group_id);
	db_query("UPDATE groups SET status='D'"
		. " WHERE group_id='$group_id'");
}


// get current information
$res_grp = db_query("SELECT * FROM groups WHERE status='P'");

if (db_numrows($res_grp) < 1) {
	exit_error("None Found","No Pending Projects to Approve");
}

site_admin_header(array('title'=>'Approving Pending Projects'));

while ($row_grp = db_fetch_array($res_grp)) {

	?>
	<H2><?php echo $row_grp['group_name']; ?></H2>

	<p>
	<A href="/admin/groupedit.php?group_id=<?php echo $row_grp['group_id']; ?>"><H3>[Edit Project Details]</H3></A>

	<p>
	<A href="/project/admin/?group_id=<?php echo $row_grp['group_id']; ?>"><H3>[Project Admin]</H3></A>

	<P>
	<A href="userlist.php?group_id=<?php print $row_grp['group_id']; ?>"><H3>[View/Edit Project Members]</H3></A>

	<p>
        <TABLE WIDTH="70%">
        <TR>
        <TD>
	<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
	<INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="<?php print $row_grp['group_id']; ?>">
	<INPUT type="submit" name="submit" value="Approve Project">
	</FORM>
 	</TD>

        <TD> 
	<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="delete">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php print $row_grp['group_id']; ?>">
	<INPUT type="submit" name="submit" value="Delete Project">
	</FORM>
        </TD>
        </TR>
        </TABLE>
	<P>
	<B>License: <?php echo $row_grp['license']; ?></B>

	<BR><B>Home Box: <?php print $row_grp['unix_box']; ?></B>
	<BR><B>HTTP Domain: <?php print $row_grp['http_domain']; ?></B>

	<br>
	&nbsp;
	<?php
	$res_cat = db_query("SELECT category.category_id AS category_id,"
		. "category.category_name AS category_name FROM category,group_category "
		. "WHERE category.category_id=group_category.category_id AND "
		. "group_category.group_id=$row_grp[group_id]");
	while ($row_cat = db_fetch_array($res_cat)) {
		print "<br>$row_cat[category_name] "
		. "<A href=\"groupedit.php?group_id=$row_grp[group_id]&group_idrm=$row_grp[group_id]&form_catrm=$row_cat[category_id]\">"
		. "[Remove from Category]</A>";
	}

	// ########################## OTHER INFO

	print "<P><B>Other Information</B>";
	print "<P>Unix Group Name: $row_grp[unix_group_name]";

	print "<P>Submitted Description:<P> $row_grp[register_purpose]";

	print "<P>License Other: <P> $row_grp[license_other]";
	
	if ( $sys_show_project_type ) {
		print "<P>Project type: ";
		$res_type = db_query("SELECT * FROM project_type WHERE project_type_id = ". $row_grp[project_type]);
		$row_type = db_fetch_array($res_type);
		print $row_type[description];
	}	

	echo "<P><HR><P>";

}

//list of group_id's of pending projects
$arr=result_column_to_array($res_grp,0);
$group_list=implode($arr,',');

echo '
	<CENTER>
	<FORM action="'.$PHP_SELF.'" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
	<INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="'.$group_list.'">
	<INPUT type="submit" name="submit" value="Approve All On This Page">
	</FORM>
	';
	
site_admin_footer(array());

?>
