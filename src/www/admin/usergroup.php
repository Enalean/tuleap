<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('account.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_usergroup','title')));

if (!isset($action)) $action='';

// user remove from group
if ($action=='remove_user_from_group') {
	/*
		Remove this user from this group
	*/

	$result = db_query("DELETE FROM user_group WHERE user_id='$user_id' AND group_id='$group_id'");
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' '.$Language->getText('admin_usergroup','error_del_u');
		echo db_error();
	} else {
		$feedback .= ' '.$Language->getText('admin_usergroup','success_del_u');
	}

} else if ($action=='update_user_group') {
	/*
		Update the user/group entry
	*/

	$result = db_query("UPDATE user_group SET admin_flags='$admin_flags' "
		. "WHERE user_id=$user_id AND "
		. "group_id=$group_id");
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' '.$Language->getText('admin_usergroup','error_upd_ug');
		echo db_error();
	} else {
		$feedback .= ' '.$Language->getText('admin_usergroup','success_upd_ug');
	}


} else if ($action=='update_user') {
	/*
		Update the user
	*/
        $result=db_query("UPDATE user SET shell='$form_shell', email='$email' WHERE user_id=$user_id");
	if (!$result) {
		$feedback .= ' '.$Language->getText('admin_usergroup','error_upd_u');
                echo db_error();
	} else {
		$feedback .= ' '.$Language->getText('admin_usergroup','success_upd_u');
	}
        // Update in plugin
        require_once('common/event/EventManager.class.php');
        $em =& EventManager::instance();
        $em->processEvent('usergroup_update', array('HTTP_POST_VARS' =>  $HTTP_POST_VARS,
                                                    'user_id' => $user_id ));        

	// status changing
	if ($form_unixstatus != 'N') {
		$res_uid = db_query("SELECT unix_uid FROM user WHERE user_id=$user_id");
		$row_uid = db_fetch_array($res_uid);
		if ($row_uid['unix_uid'] == 0) {
			// need to create uid
			db_query("UPDATE user SET unix_uid=" . account_nextuid() . " WHERE user_id=$user_id");
		} 
		// now do update
		$result=db_query("UPDATE user SET unix_status='$form_unixstatus' WHERE user_id=$user_id");	
		if (!$result) {
		    $feedback .= ' - '.$Language->getText('admin_usergroup','error_upd_ux');
		    echo db_error();
		} else {
		    $feedback .= ' - '.$Language->getText('admin_usergroup','success_upd_ux');
		}

	}

} else if ($action=='add_user_to_group') {
    /*
		Add this user to a group
    */
    // Check that user_id is not null, and that the user does not already belong to the group
    if (!$user_id) {
        $feedback .= ' '.$Language->getText('admin_usergroup','error_nouid');
    } else if (!$group_id) {
        $feedback .= ' '.$Language->getText('admin_usergroup','error_nogid');
    } else {
	$query = "SELECT user_id FROM user_group "
		. "WHERE user_id='$user_id' AND group_id='$group_id'";
	$res = db_query($query);
	if (!$res || db_numrows($res) < 1) {
            // User does not already belong to this group
            $result=db_query("INSERT INTO user_group (user_id,group_id) VALUES ($user_id,$group_id)");
            if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' '.$Language->getText('admin_usergroup','error_add_ug');
		echo db_error();
            } else {
		$feedback .= ' '.$Language->getText('admin_usergroup','success_add_ug');
            }
	} else {
            $feedback .= ' '.$Language->getText('admin_usergroup','error_member',array($group_id));
	}
    }
}

// get user info
$res_user = db_query("SELECT * FROM user WHERE user_id=$user_id");
$row_user = db_fetch_array($res_user);

?>
<h2>
<?php echo $Language->getText('admin_usergroup','header').": ".user_getname($user_id)." (ID ".$user_id.")"; ?></h2>
<h3>
<?php echo $Language->getText('admin_usergroup','account_info'); ?></h3>
<FORM method="post" action="<?php echo $PHP_SELF; ?>">
<INPUT type="hidden" name="action" value="update_user">
<INPUT type="hidden" name="user_id" value="<?php print $user_id; ?>">

<P>
<?php echo $Language->getText('admin_usergroup','shell'); ?>:
<SELECT name="form_shell">
<?php account_shellselects($row_user['shell']); ?>
</SELECT>

<P>
<?php echo $Language->getText('admin_usergroup','unix_status'); ?>:
<SELECT name="form_unixstatus">
<OPTION <?php echo ($row_user['unix_status'] == 'N') ? 'selected ' : ''; ?>value="N">
<?php echo $Language->getText('admin_usergroup','no_account'); ?>
<OPTION <?php echo ($row_user['unix_status'] == 'A') ? 'selected ' : ''; ?>value="A">
<?php echo $Language->getText('admin_usergroup','active'); ?>
<OPTION <?php echo ($row_user['unix_status'] == 'S') ? 'selected ' : ''; ?>value="S">
<?php echo $Language->getText('admin_usergroup','suspended'); ?>
<OPTION <?php echo ($row_user['unix_status'] == 'D') ? 'selected ' : ''; ?>value="D">
<?php echo $Language->getText('admin_usergroup','deleted'); ?>
</SELECT>

<P>
<?php echo $Language->getText('admin_usergroup','email'); ?>:
<INPUT TYPE="TEXT" NAME="email" VALUE="<?php echo $row_user['email']; ?>" SIZE="35" MAXLENGTH="55">

<P>
<?php 
require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('usergroup_update_form', array());

?>

<INPUT type="submit" name="Update_Unix" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<?php if($GLOBALS['sys_user_approval'] == 1){ ?>
<HR>
<H3><?php echo $Language->getText('admin_approve_pending_users','purpose'); ?>:</H3>
<?php echo $row_user['register_purpose']; 
}?>
<HR>

<p>
<H3><?php echo $Language->getText('admin_usergroup','current_groups'); ?></H3>
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
		print ('<br><a href="groupedit.php?group_id='. $row_cat['group_id'] .'"><b>'. group_getname($row_cat['group_id']) . '</b></a>'
			. "&nbsp;&nbsp;&nbsp;<a href=\"usergroup.php?user_id=$user_id&action=remove_user_from_group&group_id=$row_cat[group_id]\">"
			. "[".$Language->getText('admin_usergroup','remove_ug')."]</a>");
		// editing for flags
		?>
		<form action="<?php echo $PHP_SELF; ?>" method="post">
		<INPUT type="hidden" name="action" value="update_user_group">
		<input name="user_id" type="hidden" value="<?php print $user_id; ?>">
		<input name="group_id" type="hidden" value="<?php print $row_cat['group_id']; ?>">
		<br>
		<?php echo $Language->getText('admin_usergroup','admin_flags'); ?>: 
		<BR>
		<input type="text" name="admin_flags" value="<?php print $row_cat['admin_flags']; ?>">
		<BR>
		<input type="submit" name="Update_Group" value="<?php echo $Language->getText('global','btn_update'); ?>">
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
<?php echo $Language->getText('admin_usergroup','add_ug'); ?>:
<br>
<input type="text" name="group_id" LENGTH="4" MAXLENGTH="5">
<p>
<input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</form>

<P><A href="user_changepw.php?user_id=<?php print $user_id; ?>">[<?php echo $Language->getText('admin_usergroup','change_passwd'); ?>]</A>

<?php
html_feedback_bottom($feedback);
$HTML->footer(array());

?>
