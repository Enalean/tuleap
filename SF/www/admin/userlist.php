<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/account.php');
require($DOCUMENT_ROOT.'/project/admin/ugroup_utils.php');

$LANG->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$LANG->getText('admin_userlist','title')));

function show_users_list ($result) {
    global $LANG;
	echo '<P>'.$LANG->getText('admin_userlist','legend').'
		<P>
		<TABLE width=100% cellspacing=0 cellpadding=0 BORDER="1">';

	while ($usr = db_fetch_array($result)) {
		print "\n<TR><TD><a href=\"usergroup.php?user_id=$usr[user_id]\">";
		if ($usr[status] == 'A') print "<B>";
		if ($usr[status] == 'R') print "<u>";
		if ($usr[status] == 'D') print "<I>";
		if ($usr[status] == 'P') print "*";
		print "$usr[user_name]</A>";
		if ($usr[status] == 'A') print "</B></TD>";
		if ($usr[status] == 'R') print "</u></TD>";
		if ($usr[status] == 'D') print "</I></TD>";
		if ($usr[status] == 'S') print "</TD>";
		if ($usr[status] == 'P') print "</TD>";
		print "\n<TD><A HREF=\"/users/$usr[user_name]/\">[DevProfile]</A></TD>";
                if ($usr[status] == 'A') {
                    print "\n<TD>".$LANG->getText('admin_userlist','active')."</TD>";
                } else { print "\n<TD><A HREF=\"userlist.php?action=activate&user_id=$usr[user_id]\">[".$LANG->getText('admin_userlist','activate')."]</A></TD>"; }
                if ($GLOBALS['sys_allow_restricted_users']) {
                    if ($usr[status] == 'R') {
                        print "\n<TD>".$LANG->getText('admin_userlist','restricted')."</TD>";
                    } else { print "\n<TD><A HREF=\"userlist.php?action=restrict&user_id=$usr[user_id]\">[".$LANG->getText('admin_userlist','restrict')."]</A></TD>"; }
                }
                if ($usr[status] == 'D') {
                    print "\n<TD>".$LANG->getText('admin_userlist','deleted')."</TD>";
                } else { print "\n<TD><A HREF=\"userlist.php?action=delete&user_id=$usr[user_id]\">[".$LANG->getText('admin_userlist','delete')."]</A></TD>";}
                if ($usr[status] == 'S') {
                    print "\n<TD>".$LANG->getText('admin_userlist','suspended')."</TD>";
                } else { print "\n<TD><A HREF=\"userlist.php?action=suspend&user_id=$usr[user_id]\">[".$LANG->getText('admin_userlist','suspend')."]</A></TD>"; }
		print "</TR>";
	}
	print "</TABLE>";

}

// Administrative functions

/*
	Set this user to delete
*/
if ($action=='delete') {
	db_query("UPDATE user SET status='D',unix_status='D'  WHERE user_id='$user_id'");
        ugroup_delete_user_from_all_ugroups($user_id);
	echo '<H2>'.$LANG->getText('admin_userlist','user_deleted').'</H2>';
}

/*
	Activate their account
*/
if ($action=='activate') {
	db_query("UPDATE user SET status='A' WHERE user_id='$user_id'");
	echo '<H2>'.$LANG->getText('admin_userlist','user_active').'</H2>';
}

/*
	Suspend their account
*/
if ($action=='suspend') {
	db_query("UPDATE user SET status='S' WHERE user_id='$user_id'");
	echo '<H2>'.$LANG->getText('admin_userlist','user_suspended').'</H2>';
}

/*
	Restrict their account
*/
if (($action=='restrict')&&($GLOBALS['sys_allow_restricted_users'])) {
	db_query("UPDATE user SET status='R' WHERE user_id='$user_id'");
	echo '<H2>'.$LANG->getText('admin_userlist','user_restricted').'</H2>';
}

/*
	Add a user to this group
*/
if ($action=='add_to_group') {
    // Get user unix name
    $res_newuser = db_query("SELECT user_name FROM user WHERE user_id=$user_id");
    if (db_numrows($res_newuser) > 0) {
        $user_name = db_result($res_newuser,0,'user_name');
        if (!account_add_user_to_group($group_id,$user_name)) {
            $feedback .= ' '.$LANG->getText('admin_userlist','error_noadd');
        }
    } else {
	$feedback .= ' '.$LANG->getText('admin_userlist','error_uid',array($user_id));
    }
}

/*
	Show list of users
*/
print "<p>".$LANG->getText('admin_userlist','user_list').":  ";
if (!$group_id) {
	print "<b>".$LANG->getText('admin_userlist','all_groups')."</b>";
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
    print "<b>Group ".$LANG->getText('admin_userlist','group',array(group_getname($group_id)))."</b>";
	
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
	<p><?php echo $LANG->getText('admin_userlist','uid_toadd'); ?>:&nbsp;
	<input name="user_id" type="TEXT" value="">
	<br>
	<input type="HIDDEN" name="group_id" VALUE="<?php print $group_id; ?>">
	<p>
	<input type="submit" name="Submit" value="<?php echo $LANG->getText('global','btn_submit'); ?>">
	</form>

	<?php	
}

$HTML->footer(array());

?>
