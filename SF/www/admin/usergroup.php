<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>'Admin - User Info'));

// user remove from group
if ($action=='remove_user_from_group') {
	/*
		Remove this user from this group
	*/

	$result = db_query("DELETE FROM user_group WHERE user_id='$user_id' AND group_id='$group_id'");
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' Error Removing User ';
		echo db_error();
	} else {
		$feedback .= ' Successfully removed user ';
	}

} else if ($action=='update_user_group') {
	/*
		Update the user/group entry
	*/

	$result = db_query("UPDATE user_group SET admin_flags='$admin_flags' "
		. "WHERE user_id=$user_id AND "
		. "group_id=$group_id");
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' Error Updating User_group ';
		echo db_error();
	} else {
		$feedback .= ' Successfully updated user_group ';
	}


} else if ($action=='update_user') {
	/*
		Update the user
	*/

	$result=db_query("UPDATE user SET shell='$form_shell', email='$email' WHERE user_id=$user_id");
	if (!$result || db_affected_rows($result) < 1) {
		echo db_error();
	} else {
		$feedback .= ' Successfully updated user ';
	}
	// status changing
	if ($form_unixstatus != 'N') {
		$res_uid = db_query("SELECT unix_uid FROM user WHERE user_id=$user_id");
		$row_uid = db_fetch_array($res_uid);
		if ($row_uid[unix_uid] == 0) {
			// need to create uid
			db_query("UPDATE user SET unix_uid=" . account_nextuid() . " WHERE user_id=$user_id");
		} 
		// now do update
		$result=db_query("UPDATE user SET unix_status='$form_unixstatus' WHERE user_id=$user_id");	
		if (!$result || db_affected_rows($result) < 1) {
			echo db_error();
		} else {
			$feedback .= 'Successfully updated unix ';
		}

	}

} else if ($action=='add_user_to_group') {
    /*
		Add this user to a group
    */
    // Check that user_id is not null, and that the user does not already belong to the group
    if (!$user_id) {
        $feedback .= ' User ID is missing! ';
    } else if (!$group_id) {
        $feedback .= ' Group ID is missing! ';
    } else {
	$query = "SELECT user_id FROM user_group "
		. "WHERE user_id='$user_id' AND group_id='$group_id'";
	$res = db_query($query);
	if (!$res || db_numrows($res) < 1) {
            // User does not already belong to this group
            $result=db_query("INSERT INTO user_group (user_id,group_id) VALUES ($user_id,$group_id)");
            if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' Error adding User to group ';
		echo db_error();
            } else {
		$feedback .= ' Successfully added user to group ';
            }
	} else {
            $feedback .= " User is already a member of group $group_id";
	}
    }
}

// get user info
$res_user = db_query("SELECT * FROM user WHERE user_id=$user_id");
$row_user = db_fetch_array($res_user);

?>
<p>
User Group Edit for user: <b><?php print $user_id . ": " . user_getname($user_id); ?></b>
<p>
Unix Account Info:
<FORM method="post" action="<?php echo $PHP_SELF; ?>">
<INPUT type="hidden" name="action" value="update_user">
<INPUT type="hidden" name="user_id" value="<?php print $user_id; ?>">

<P>
Shell:
<SELECT name="form_shell">
<?php account_shellselects($row_user[shell]); ?>
</SELECT>

<P>
Unix Account Status:
<SELECT name="form_unixstatus">
<OPTION <?php echo ($row_user[unix_status] == 'N') ? 'selected ' : ''; ?>value="N">No Unix Account
<OPTION <?php echo ($row_user[unix_status] == 'A') ? 'selected ' : ''; ?>value="A">Active
<OPTION <?php echo ($row_user[unix_status] == 'S') ? 'selected ' : ''; ?>value="S">Suspended
<OPTION <?php echo ($row_user[unix_status] == 'D') ? 'selected ' : ''; ?>value="D">Deleted
</SELECT>

<P>
<INPUT TYPE="TEXT" NAME="email" VALUE="<?php echo $row_user[email]; ?>" SIZE="25" MAXLENGTH="55">

<P>
<INPUT type="submit" name="Update_Unix" value="Update">
</FORM>

<HR>

<p>
<H2>Current Groups:</H2>
<br>
&nbsp;

<?php
/*
	Iterate and show groups this user is in
*/
$res_cat = db_query("SELECT groups.group_name AS group_name, "
	. "groups.group_id AS group_id, "
	. "user_group.admin_flags AS admin_flags FROM "
	. "groups,user_group WHERE user_group.user_id=$user_id AND "
	. "groups.group_id=user_group.group_id");

	while ($row_cat = db_fetch_array($res_cat)) {
		print ("<br><hr><b>" . group_getname($row_cat[group_id]) . "</b> "
			. "<a href=\"usergroup.php?user_id=$user_id&action=remove_user_from_group&group_id=$row_cat[group_id]\">"
			. "[Remove User from Group]</a>");
		// editing for flags
		?>
		<form action="<?php echo $PHP_SELF; ?>" method="post">
		<INPUT type="hidden" name="action" value="update_user_group">
		<input name="user_id" type="hidden" value="<?php print $user_id; ?>">
		<input name="group_id" type="hidden" value="<?php print $row_cat[group_id]; ?>">
		<br>
		Admin Flags: 
		<BR>
		<input type="text" name="admin_flags" value="<?php print $row_cat[admin_flags]; ?>">
		<BR>
		<input type="submit" name="Update_Group" value="Update">
		</form>
		<?php
	}

/*
	Show a form so a user can be added to a group
*/
?>
<hr>
<P>
<form action="<?php echo $PHP_SELF; ?>" method="post">
<INPUT type="hidden" name="action" value="add_user_to_group">
<input name="user_id" type="hidden" value="<?php print $user_id; ?>">
<p>
Add User to Group (group_id):
<br>
<input type="text" name="group_id" LENGTH="4" MAXLENGTH="5">
<p>
<input type="submit" name="Submit" value="Submit">
</form>

<P><A href="user_changepw.php?user_id=<?php print $user_id; ?>">[Change User PW]</A>

<?php
html_feedback_bottom($feedback);
$HTML->footer(array());

?>
