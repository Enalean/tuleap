<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_massmail','title'), 'main_classes' => array('tlp-framed')));
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

print '<h1>'.$Language->getText('admin_massmail','header',array($GLOBALS['sys_name'])).'</h1>

<p class="tlp-text-warning">'.$Language->getText('admin_massmail', 'warning').'</p>

 <FORM ID="massmail_form" NAME="massmail_form" ACTION="massmail_execute.php" METHOD="POST">
<label class="radio" name="'.$count_comm.'"><INPUT type="radio" name="destination" value="comm">
    '.$Language->getText('admin_massmail','to_additional').' ('
    .$count_comm
    .' users)</label>
<label class="radio" name="'.$count_sf.'"><INPUT type="radio" name="destination" value="sf">
    '.$Language->getText('admin_massmail','to_update').' ('
    .$count_sf
    .' users)</label>
<label class="radio" name="'.$count_devel.'"><INPUT type="radio" name="destination" value="devel">
    '.$Language->getText('admin_massmail','to_devel').' ('
    .$count_devel
    .' users)</label>
<label class="radio" name="'.$count_admin.'"><INPUT type="radio" name="destination" value="admin">
    '.$Language->getText('admin_massmail','to_proj_admin').' ('
    .$count_admin
    .' users)</label>
<label class="radio" name="'.$count_sfadmin.'"><INPUT type="radio" name="destination" value="sfadmin">
    '.$Language->getText('admin_massmail','to_site_admin').' ('
    .$count_sfadmin
    .' users)</label>
<label class="radio" name="'.$count_all.'"><INPUT type="radio" name="destination" value="all">
'.$Language->getText('admin_massmail','to_all').' ('
.$count_all
.' users)</label>

<TABLE cellpadding=0 cellspacing=0 border=0>
<TR><TD>
<label><strong>'.$Language->getText('admin_massmail','subject').'</strong></label>
<INPUT type="text" id="mail_subject" name="mail_subject" value="'.$GLOBALS['sys_name'].': "size="40">

<label><strong>'.$Language->getText('admin_massmail','text').'</strong></label>
<div id="mail_message_label"></div>
<TEXTAREA id="mail_message" name="mail_message" cols="75" rows="40" wrap="physical">
'.stripcslashes($Language->getText('admin_massmail','footer',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_email_admin']))).'
</TEXTAREA>
</TD></TR><TR><TD>
<P class="form-inline" id="massmail_preview"><NOSCRIPT><INPUT type="radio" name="destination" value="preview" CHECKED></NOSCRIPT>
'.$Language->getText('admin_massmail','to_preview').'
<INPUT type="text" id="preview_destination" name="preview_destination" size="50" >
<SPAN ID="preview_button"></SPAN>
<DIV id="preview_result"></DIV>
</P>
<P><INPUT type="submit" name="Submit" class="tlp-button-primary" value="'.$Language->getText('global','btn_submit').'">
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
            var mail_message_container = $$('#mail_message')[0];
            var options = {
                toggle: true,
                default_in_html: false,
                htmlFormat : false,
                id: ''
            };

            var editor = new tuleap.textarea.RTE(mail_message_container, options);

            // Building input for the submission of preview adresses
            var button = Builder.node('input', {'id'      : 'preview_submit',
                                                'name'    : 'Submit',
                                                'type'    : 'button',
                                                'value'   : 'Preview'});
            button.addClassName('tlp-button-secondary');
            $('preview_button').appendChild(button);

            //launching initialize function on MassMail instance will observe Events on the  input built above
            new MassMail(editor);
        });";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());
