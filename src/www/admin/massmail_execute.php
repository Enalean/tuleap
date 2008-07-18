<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');

require_once('common/mail/Mail.class.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>1,'admin_flags'=>'A'));

header ('Content-Type: text/plain');

print $Language->getText('admin_massmail_execute','post_recvd')."\n";
flush();


print $Language->getText('admin_massmail_execute','mailing',array(db_numrows($res_mail)))." ($to_name)\n\n";
flush();

$rows=db_numrows($res_mail);

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
$mail =& new Mail();
$mail->setTo($GLOBALS['sys_noreply']);
$mail->setFrom($GLOBALS['sys_noreply']);
$mail->setSubject(stripslashes($mail_subject));
$mail->setBody(stripslashes($mail_message));

$tolist = '';
for ($i=1; $i<=$rows; $i++) {
	$tolist .= db_result($res_mail,$i-1,'email').', ';
    if ($i % 25 == 0) {
		//spawn sendmail for 25 addresses at a time
        $mail->setBcc($tolist);
        if ($mail->send()) {
            print "\n".$Language->getText('admin_massmail_execute','sending').": ".$tolist;
        } else {
            print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist;
        }
		flush();
        usleep(2000000);
		$tolist='';
	}
}

//send the last of the messages.
if (strlen($tolist) > 0) {
    $mail->setBcc($tolist);
    if ($mail->send()) {
        print "\n".$Language->getText('admin_massmail_execute','sending').": ".$tolist;
    } else {
        print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist;
    }
}
print "\n".$Language->getText('admin_massmail_execute','done')."\n";
flush();
?>
