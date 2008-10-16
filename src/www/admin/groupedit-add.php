<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('account.php');
require_once('www/admin/admin_utils.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

// ########################################################

if ($Submit) {
	// check for valid group name
	if (!account_groupnamevalid($form_unixgrp)) {
		exit_error("Invalid Group Name",$register_error);
	}	

	if ($group_idname) {
	$newid = db_insertid(db_query("INSERT INTO groups (group_name,is_public,unix_group_name,http_domain,status) VALUES "
		. "('$group_idname',$form_public,'$form_unixgrp'"
		. ",'$form_unixgrp.$GLOBALS[sys_default_domain]','$form_status')")); 
	} 
	session_redirect("/admin/groupedit.php?group_id=$newid");
} 

site_admin_header(array('title'=>$Language->getText('admin_groupedit_add','title')));
?>

<form action="groupedit-add.php" method="post">
    <p><?php echo $Language->getText('admin_groupedit_add','desc_name'); ?>:
<br><input type="text" name="group_idname">
<p><?php echo $Language->getText('admin_groupedit_add','unix_name'); ?>:
<br><input type="text" name="form_unixgrp">
<br><?php echo $Language->getText('global','status'); ?>
<SELECT name="form_status">
<OPTION value="A"><?php echo $Language->getText('admin_groupedit','active'); ?>
<OPTION value="P"><?php echo $Language->getText('admin_groupedit','pending'); ?>
<OPTION value="H"><?php echo $Language->getText('admin_groupedit','holding'); ?>
<OPTION value="D"><?php echo $Language->getText('admin_groupedit','deleted'); ?>
</SELECT>
<br><?php echo $Language->getText('admin_groupedit','public'); ?>
<SELECT name="form_public">
<OPTION value="1"><?php echo $Language->getText('global','yes'); ?>
<OPTION value="0"><?php echo $Language->getText('global','no'); ?>
</SELECT>
<br><input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</form>

<?php
site_admin_footer(array());

?>
