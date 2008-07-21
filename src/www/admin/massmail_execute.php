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

$request =& HTTPRequest::instance();

//define white lists for parameters
$destinationWhiteList = array('comm', 'sf', 'all', 'admin', 'sfadmin', 'devel');
$submitWhiteList = array('Submit', 'Cancel');


//valid parameters

//valid destination
$validDestination = new Valid('destination');
$validDestination->addRule(new Rule_WhiteList($destinationWhiteList));

$destination = '';
if($request->valid($validDestination)) {
    $destination = $request->get('destination');
 }
 else {
     $Response->addFeedback('error', 'A destination is required');
 }

//valid mail subject
$validMailSubject = new Valid_String('mail_subject');
$validMailSubject->required();
$mailSubject = '';
if($request->valid($validMailSubject)) {
    $mailSubject = $request->get('mail_subject');
    $mailSubject = stripslashes($mailSubject);
 }
 else {
     $Response->addFeedback('error', 'A subject is required');
 }

//valid mail message
$validMailMessage = new Valid('mail_message');
$validMailMessage->required();
$mailMessage = '';
if($request->valid($validMailMessage)) {
    $mailMessage = $request->get('mail_message');
    $mailMessage = stripslashes($mailMessage);
 }
 else {
     $Response->addFeedback('error', 'A message is required');
 }

//valid res_mail
$validResMail = new Valid('res_mail');
$res_mail = null;
if($request->valid($validResMail)) {
    $res_mail = $request->get('res_mail');
 }
else {
     $Response->addFeedback('error', 'An error occured (si on ne recupere pas res_mail dans massmail_execute.php)');
 }

//valid to_name
$validResMail = new Valid_String('to_name');
$to_name = '';
if($request->valid($validResMail)) {
    $to_name = $request->get('to_name');
 }
 else {
     $Response->addFeedback('error', 'An error occured (si on ne recupere pas to_name dans massmail_execute.php)');
 }

//valid submit
$validSubmit = new Valid('Submit');
$validSubmit->addRule(new Rule_WhiteList($submitWhiteList));

if($request->valid($validSubmit)) {
    $submit = $request->get('Submit');
 }
 else {
     $Response->addFeedback('error','Your data are not valid');
 }




// if user choose to send emails
if ($submit == $Language->getText('global','btn_submit')) {

    //if all the parameters are set
    if($destination != '' && $mailSubject != '' && $mailMessage != '' && $res_mail != null && $to_name != '') {
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
        //        $Response->addFeedback('info', '$Language->getText(\'admin_massmail_execute\',\'done\')');
        //flush();
        print "\n".$Language->getText('admin_massmail_execute','done')."\n";
        flush();
        
    }
    else {
        $Response->addFeedback('error', 'A error occured : all the parameters are not set');
        $Response->redirect('/admin/massmail.php');
    }
 }
 else { //user cancel the mail delivery
     $Response->addFeedback('info', 'Mail delivery has been canceled ');
     $Response->redirect('/admin/massmail.php');
 }

?>
