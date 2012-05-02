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
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/codendi/MassMail.js');

// get numbers of users for each mailing
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' or status='R' ) AND mail_va=1");
$count_comm = db_result($res_count, 0, null);
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' or status='R' ) AND mail_siteupdates=1");
$count_sf = db_result($res_count, 0, null);
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' or status='R' )");
$count_all = db_result($res_count, 0, null);
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A'");
$count_admin = db_result($res_count, 0, null);
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' )");
$count_devel = db_result($res_count, 0, null);
$res_count = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
	."user.user_id=user_group.user_id AND( user.status='A' OR user.status='R' ) AND user_group.group_id=1");
$count_sfadmin = db_result($res_count, 0, null);

print '<h2>'.$Language->getText('admin_massmail','header',array($GLOBALS['sys_name'])).'</h2>

<P>'.$Language->getText('admin_massmail','warning').'

 <FORM ID="massmail_form" NAME="massmail_form" ACTION="massmail_execute.php" METHOD="POST">
<TABLE width=50% cellpadding=0 cellspacing=0 border=0>
<TR><TD>
<SPAN name="'.$count_comm.'"><INPUT type="radio" name="destination" value="comm"></SPAN>
'.$Language->getText('admin_massmail','to_additional').' ('
.$count_comm
.' users)<BR><SPAN name="'.$count_sf.'"><INPUT type="radio" name="destination" value="sf"></SPAN>
'.$Language->getText('admin_massmail','to_update').' ('
.$count_sf
.' users)<BR><SPAN name="'.$count_devel.'"><INPUT type="radio" name="destination" value="devel"></SPAN>
'.$Language->getText('admin_massmail','to_devel').' ('
.$count_devel
.' users)<BR><SPAN name="'.$count_admin.'"><INPUT type="radio" name="destination" value="admin"></SPAN>
'.$Language->getText('admin_massmail','to_proj_admin').' ('
.$count_admin
.' users)<BR><SPAN name="'.$count_sfadmin.'"><INPUT type="radio" name="destination" value="sfadmin"></SPAN>
'.$Language->getText('admin_massmail','to_site_admin').' ('
.$count_sfadmin
.' users)<BR><SPAN name="'.$count_all.'"><INPUT type="radio" name="destination" value="all"></SPAN>
'.$Language->getText('admin_massmail','to_all').' ('
.$count_all
.' users)
</TD></TR>
<TR><TD>
<P>'.$Language->getText('admin_massmail','subject').'
<BR><INPUT type="text" id="mail_subject" name="mail_subject" value="'.$GLOBALS['sys_name'].': "size="40">

<P>'.$Language->getText('admin_massmail','text').'
<PRE>
<div id="mail_message_label"></div>
<TEXTAREA id="mail_message" name="mail_message" cols="75" rows="40" wrap="physical">
'.stripcslashes($Language->getText('admin_massmail','footer',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_email_admin']))).'
</TEXTAREA>
</PRE>
</TD></TR><TR><TD>
<P><NOSCRIPT><INPUT type="radio" name="destination" value="preview" CHECKED></NOSCRIPT>
'.$Language->getText('admin_massmail','to_preview').'
<INPUT type="text" id="preview_destination" name="preview_destination" size="50" >
<SPAN ID="preview_button"></SPAN>
<DIV id="preview_result"></DIV>
</P>
<P><INPUT type="submit" name="Submit" value="'.$Language->getText('global','btn_submit').'">
</TD></TR></TABLE>
</FORM>
';

$js = "new UserAutoCompleter('preview_destination',
                             '".util_get_dir_image_theme()."',
                             true);";
$GLOBALS['HTML']->includeFooterJavascriptSnippet($js);

$rte = "
var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';
document.observe('dom:loaded', function() {
            new Codendi_RTE_Send_HTML_MAIL('mail_message');

            // Building input for the submission of preview adresses 
            var button = Builder.node('input', {'id'      : 'preview_submit',
                                                'name'    : 'Submit',
                                                'type'    : 'button',
                                                'value'   : 'Preview'});
            $('preview_button').appendChild(button);

            //launching initialize function on MassMail instance will observe Events on the  input built above
            new MassMail();
        });";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());

?>
