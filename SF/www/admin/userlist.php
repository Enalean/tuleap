<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
session_require(array('group'=>'1','admin_flags'=>'A'));
$HTML->header(array('title'=>'Admin - User List'));

function show_users_list ($result) {
	echo '<P>Key:
		<B>Active</B>
		<I>Deleted</I>
		Suspended
		(*)Pending
		<P>
		<TABLE width=100% cellspacing=0 cellpadding=0 BORDER="1">';

	while ($usr = db_fetch_array($result)) {
		print "\n<TR><TD><a href=\"usergroup.php?user_id=$usr[user_id]\">";
		if ($usr[status] == 'A') print "<B>";
		if ($usr[status] == 'D') print "<I>";
		if ($usr[status] == 'P') print "*";
		print "$usr[user_name]</A>";
		if ($usr[status] == 'A') print "</B></TD>";
		if ($usr[status] == 'D') print "</I></TD>";
		if ($usr[status] == 'S') print "</TD>";
		if ($usr[status] == 'P') print "</TD>";
		print "\n<TD><A HREF=\"/developer/?form_dev=$usr[user_id]\">[DevProfile]</A></TD>";
		print "\n<TD><A HREF=\"userlist.php?action=activate&user_id=$usr[user_id]\">[Activate]</A></TD>";
		print "\n<TD><A HREF=\"userlist.php?action=delete&user_id=$usr[user_id]\">[Delete]</A></TD>";
		print "\n<TD><A HREF=\"userlist.php?action=suspend&user_id=$usr[user_id]\">[Suspend]</A></TD>";
		print "</TR>";
	}
	print "</TABLE>";

}

// Administrative functions

/*
	Set this user to delete
*/
if ($action=='delete') {
	db_query("UPDATE user SET status='D' WHERE user_id='$user_id'");
	echo '<H2>User Updated to DELETE Status</H2>';
}

/*
	Activate their account
*/
if ($action=='activate') {
	db_query("UPDATE user SET status='A' WHERE user_id='$user_id'");
	echo '<H2>User Updated to ACTIVE status</H2>';
}

/*
	Suspend their account
*/
if ($action=='suspend') {
	db_query("UPDATE user SET status='S' WHERE user_id='$user_id'");
	echo '<H2>User Updated to SUSPEND Status</H2>';
}

/*
	Add a user to this group
*/
if ($action=='add_to_group') {
	db_query("INSERT INTO user_group (user_id,group_id) VALUES ($user_id,$group_id)");
}

/*
	Show list of users
*/
print "<p>User List for:  ";
if (!$group_id) {
	print "<b>All Groups</b>";
	print "\n<p>";
	
	if ($user_name_search) {
		$result = db_query("SELECT user_name,user_id,status FROM user WHERE user_name LIKE '$user_name_search%' ORDER BY user_name");
	} else {
		$result = db_query("SELECT user_name,user_id,status FROM user ORDER BY user_name");
	}
	show_users_list ($result);
} else {
	/*
		Show list for one group
	*/
	print "<b>Group " . group_getname($group_id) . "</b>";
	
	print "\n<p>";

	$result = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name,user.status AS status "
		. "FROM user,user_group "
		. "WHERE user.user_id=user_group.user_id AND "
		. "user_group.group_id=$group_id ORDER BY user.user_name");
	show_users_list ($result);

	/*
        	Show a form so a user can be added to this group
	*/
	?>
	<hr>
	<P>
	<form action="<?php echo $PHP_SELF; ?>" method="post">
	<input type="HIDDEN" name="action" VALUE="add_to_group">
	<input name="user_id" type="TEXT" value="">
	<p>
	Add User to Group (<?php print group_getname($group_id); ?>):
	<br>
	<input type="HIDDEN" name="group_id" VALUE="<?php print $group_id; ?>">
	<p>
	<input type="submit" name="Submit" value="Submit">
	</form>

	<?php	
}

$HTML->footer(array());

?>
