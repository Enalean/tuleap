<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    

$LANG->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$LANG->getText('admin_massmail','title')));

// get numbers of users for each mailing
$res_count = db_query("SELECT count(*) AS count FROM user WHERE ( status='A' or status='R' ) AND mail_va=1");
$row_count = db_fetch_array($res_count);
$count_comm = $row_count[count];
$res_count = db_query("SELECT count(*) AS count FROM user WHERE ( status='A' or status='R' ) AND mail_siteupdates=1");
$row_count = db_fetch_array($res_count);
$count_sf = $row_count[count];
$res_count = db_query("SELECT count(*) AS count FROM user WHERE ( status='A' or status='R' )");
$row_count = db_fetch_array($res_count);
$count_all = $row_count[count];
$res_count = db_query("SELECT count(*) AS count FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A'");
$row_count = db_fetch_array($res_count);
$count_admin = $row_count[count];
$res_count = db_query("SELECT count(*) AS count FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' )");
$row_count = db_fetch_array($res_count);
$count_devel = $row_count[count];
$res_count = db_query("SELECT count(*) AS count FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND( user.status='A' OR user.status='R' ) AND user_group.group_id=1");
$row_count = db_fetch_array($res_count);
$count_sfadmin = $row_count[count];

print '<h2>'.$LANG->getText('admin_massmail','header',array($GLOBALS['sys_name'])).'</h2>

<P>'.$LANG->getText('admin_massmail','warning').'

<FORM action="massmail_execute.php">
<INPUT type="radio" name="destination" value="comm">
'.$LANG->getText('admin_massmail','to_additional').' ('
.$count_comm
.' users)<BR><INPUT type="radio" name="destination" value="sf">
'.$LANG->getText('admin_massmail','to_update').' ('
.$count_sf
.' users)<BR><INPUT type="radio" name="destination" value="devel">
'.$LANG->getText('admin_massmail','to_devel').' ('
.$count_devel
.' users)<BR><INPUT type="radio" name="destination" value="admin">
'.$LANG->getText('admin_massmail','to_proj_admin').' ('
.$count_admin
.' users)<BR><INPUT type="radio" name="destination" value="sfadmin">
'.$LANG->getText('admin_massmail','to_site_admin').' ('
.$count_sfadmin
.' users)<BR><INPUT type="radio" name="destination" value="all">
'.$LANG->getText('admin_massmail','to_all').' ('
.$count_all
.' users)

<P>'.$LANG->getText('admin_massmail','subject').'
<BR><INPUT type="text" name="mail_subject" value="'.$GLOBALS['sys_name'].': "size="40">

<P>'.$LANG->getText('admin_massmail','text').'
<PRE>
<BR><TEXTAREA name="mail_message" cols="70" rows="40" wrap="physical">
'.stripcslashes($LANG->getText('admin_massmail','footer',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_email_admin']))).'
</TEXTAREA>
</PRE>
<P><INPUT type="submit" name="Submit" value="'.$LANG->getText('global','btn_submit').'">

</FORM>
';

$HTML->footer(array());

?>
