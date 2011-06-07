<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    


session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_massmail','title')));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tiny_mce/tiny_mce.js');

// get numbers of users for each mailing
$res_count = db_query("SELECT * FROM user WHERE ( status='A' or status='R' ) AND mail_va=1 GROUP BY email");
$count_comm = db_numrows($res_count);
$res_count = db_query("SELECT * FROM user WHERE ( status='A' or status='R' ) AND mail_siteupdates=1 GROUP BY email");
$count_sf = db_numrows($res_count);
$res_count = db_query("SELECT * FROM user WHERE ( status='A' or status='R' ) GROUP BY email");
$count_all = db_numrows($res_count);
$res_count = db_query("SELECT * FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A' GROUP BY email");
$count_admin = db_numrows($res_count);
$res_count = db_query("SELECT * FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) GROUP BY email");
$count_devel = db_numrows($res_count);
$res_count = db_query("SELECT * FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND( user.status='A' OR user.status='R' ) AND user_group.group_id=1 GROUP BY email");
$count_sfadmin = db_numrows($res_count);

print '<h2>'.$Language->getText('admin_massmail','header',array($GLOBALS['sys_name'])).'</h2>

<P>'.$Language->getText('admin_massmail','warning').'

<FORM ACTION="massmail_execute.php" METHOD="POST">
<TABLE width=50% cellpadding=0 cellspacing=0 border=0>
<TR><TD>
<INPUT type="radio" name="destination" value="preview" CHECKED> Send preview to <INPUT type="text" name="preview_destination" size="50"><BR>
<INPUT type="radio" name="destination" value="comm">
'.$Language->getText('admin_massmail','to_additional').' ('
.$count_comm
.' users)<BR><INPUT type="radio" name="destination" value="sf">
'.$Language->getText('admin_massmail','to_update').' ('
.$count_sf
.' users)<BR><INPUT type="radio" name="destination" value="devel">
'.$Language->getText('admin_massmail','to_devel').' ('
.$count_devel
.' users)<BR><INPUT type="radio" name="destination" value="admin">
'.$Language->getText('admin_massmail','to_proj_admin').' ('
.$count_admin
.' users)<BR><INPUT type="radio" name="destination" value="sfadmin">
'.$Language->getText('admin_massmail','to_site_admin').' ('
.$count_sfadmin
.' users)<BR><INPUT type="radio" name="destination" value="all">
'.$Language->getText('admin_massmail','to_all').' ('
.$count_all
.' users)
</TD></TR>
<TR><TD>
<P>'.$Language->getText('admin_massmail','subject').'
<BR><INPUT type="text" name="mail_subject" value="'.$GLOBALS['sys_name'].': "size="40">

<P>'.$Language->getText('admin_massmail','text').'
<PRE>
<BR>
<div id="mail_message_label"></div>
<TEXTAREA id="mail_message" name="mail_message" cols="75" rows="40" wrap="physical">
'.stripcslashes($Language->getText('admin_massmail','footer',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_email_admin']))).'
</TEXTAREA>
</PRE>
<P><INPUT type="submit" name="Submit" value="'.$Language->getText('global','btn_submit').'">
</TD></TR></TABLE>
</FORM>
';

$rte = "
var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';
document.observe('dom:loaded', function() {
            new Codendi_RTE_Send_HTML_MAIL('mail_message');
        });";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());

?>
