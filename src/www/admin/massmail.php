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

<FORM NAME="massmail_form" ACTION="massmail_execute.php" METHOD="POST">
<TABLE width=50% cellpadding=0 cellspacing=0 border=0>
<TR><TD>
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
<BR><INPUT type="text" id="mail_subject" name="mail_subject" value="'.$GLOBALS['sys_name'].': "size="40">

<P>'.$Language->getText('admin_massmail','text').'
<PRE>
<BR>
<div id="mail_message_label"></div>
<TEXTAREA id="mail_message" name="mail_message" cols="75" rows="40" wrap="physical">
'.stripcslashes($Language->getText('admin_massmail','footer',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_email_admin']))).'
</TEXTAREA>
</PRE>
</TD></TR><TR><TD>
<div id="main1">
<div id="iframe">
<iframe src="massmail_upload_attachments.php" frameborder="0"></iframe>
</div>
<div id="images1"></div>
</div>
<div id="myDivLink"></div>
<br><P>'.$Language->getText('admin_massmail','to_preview').'
<INPUT type="text" id="preview_destination" name="preview_destination" size="50">
<INPUT type="button" name="Submit" onClick="sendPreview()" value="'.$Language->getText('global','btn_submit').'">
<DIV id="preview_result"></DIV>
</P>
<P><INPUT type="submit" name="Submit" value="'.$Language->getText('global','btn_submit').'">
</TD></TR></TABLE>
</FORM>
';

$rte = "
var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';
document.observe('dom:loaded', function() {
            new Codendi_RTE_Send_HTML_MAIL('mail_message');
        });

function ajaxRequest(){
 var activexmodes=['Msxml2.XMLHTTP', 'Microsoft.XMLHTTP']; //activeX versions to check for in IE
 if (window.ActiveXObject){ //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
  for (var i=0; i<activexmodes.length; i++){
   try{
    return new ActiveXObject(activexmodes[i]);
   }
   catch(e){
    //suppress error
   }
  }
 }
 else if (window.XMLHttpRequest) // if Mozilla, Safari etc
  return new XMLHttpRequest();
 else
  return false;
}

function sendPreview() {
    var mypostrequest=new ajaxRequest();
    mypostrequest.onreadystatechange=function() {
        if (mypostrequest.readyState==4){
            document.getElementById('preview_result').innerHTML = '<img src=\"/themes/common/images/ic/spinner.gif\" border=\"0\" />';
            if (mypostrequest.status==200 || window.location.href.indexOf('http')==-1) {
                document.getElementById('preview_result').innerHTML='<span style=\"color:red\"><small><b>'+mypostrequest.responseText+'</b></small></span>';
            } else {
                alert('An error has occured making the request');
            }
        }
    }
    var mailSubject=encodeURIComponent(document.getElementById('mail_subject').value);
    var mailMessage=encodeURIComponent(document.getElementById('mail_message').value);
    var previewDestination=encodeURIComponent(document.getElementById('preview_destination').value);
    for (var i=0; i < document.massmail_form.body_format.length; i++) {
        if (document.massmail_form.body_format[i].checked) {
            var bodyFormat = document.massmail_form.body_format[i].value;
        }
    }
    var parameters='destination=preview&mail_subject='+mailSubject+'&body_format='+bodyFormat+'&mail_message='+mailMessage+'&preview_destination='+previewDestination+'&Submit=Submit';
    mypostrequest.open('POST', '/admin/massmail_execute.php', true);
    mypostrequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    mypostrequest.send(parameters);
}";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());

?>
