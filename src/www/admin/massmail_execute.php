<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');

require_once('common/mail/Mail.class.php');
require_once('common/mail/Codendi_Mail.class.php');


session_require(array('group'=>1,'admin_flags'=>'A'));

$request = HTTPRequest::instance();
if ($request->isPost() && $request->exist('Submit') &&  $request->existAndNonEmpty('destination')) {

    $validDestination = new Valid_WhiteList('destination' ,array('preview', 'comm', 'sf', 'all', 'admin', 'sfadmin', 'devel'));
    $destination = $request->getValidated('destination', $validDestination);

    // LJ The to_name variable has been added here to be used
    // LJ in the mail command later in this script
    switch ($destination) {
        case 'preview':
            break;
        case 'comm':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_va=1 GROUP BY lcase(email)");
            $to_name = 'Additional Community Mailings Subcribers';
            break;
        case 'sf':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_siteupdates=1 GROUP BY lcase(email)");
            $to_name = 'Site Updates Subcribers';
            break;
        case 'all':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) GROUP BY lcase(email)");
            $to_name = 'All Users';
            break;
        case 'admin':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A' "
            ."GROUP by lcase(email)");
            $to_name = 'Project Administrators';
            break;
        case 'sfadmin':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.group_id=1 "
            ."GROUP by lcase(email)");
            $to_name = $GLOBALS['sys_name'].' Administrators';
            break;
        case 'devel':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) GROUP BY lcase(email)");
            $to_name = 'Project Developers';
            break;
        default:
            exit_error('Unrecognized Post','cannot execute');
    }
    header ('Content-Type: text/plain');

    print $Language->getText('admin_massmail_execute','post_recvd')."\n";
    flush();

    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
    $validFormat = new Valid_WhiteList('body_format' ,array(0, 1));
    $bodyFormat = $request->getValidated('body_format', $validFormat, 0);
    if ($bodyFormat) {
        $mail = new Codendi_Mail();
    } else {
        $mail = new Mail();
    }
    $mail->setTo($GLOBALS['sys_noreply']);
    $mail->setFrom($GLOBALS['sys_noreply']);

    $validSubject = new Valid_String('mail_subject');
    if($request->valid($validSubject)) {
        $mailSubject = $request->get('mail_subject');
    }
    $mail->setSubject(stripslashes($mailSubject));

    $validMessage = new Valid_Text('mail_message');
    if($request->valid($validMessage)) {
        $mailMessage = $request->get('mail_message');
    }
    $mail->setBody(stripslashes($mailMessage));

    if (isset($res_mail)) {
        print $Language->getText('admin_massmail_execute','mailing',array(db_numrows($res_mail)))." ($to_name)\n\n";
        flush();

        $rows=db_numrows($res_mail);

        $tolist = '';

        for ($i=1; $i<=$rows; $i++) {
            $tolist .= db_result($res_mail,$i-1,'email').', ';
            if ($i % 25 == 0) {
                //spawn sendmail for 25 addresses at a time
                $mail->setBcc($tolist);
                $mail->setTo($GLOBALS['sys_noreply']);
                if ($mail->send()) {
                    print "\n".$Language->getText('admin_massmail_execute','sending').": ".$tolist;
                } else {
                    print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist;
                }
                flush();
                usleep(2000000);
                $tolist='';
                if ($bodyFormat) {
                    $m = $mail->getMail();
                    $m->clearRecipients();
                }
            }
        }

        //send the last of the messages.
        if (strlen($tolist) > 0) {
            $mail->setBcc($tolist);
            $mail->setTo($GLOBALS['sys_noreply']);
            if ($mail->send()) {
                print "\n".$Language->getText('admin_massmail_execute','sending').": ".$tolist;
            } else {
                print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist;
            }
        }
    } else {
        $validMails = array();
        $addresses  = array_map('trim', preg_split('/[,;]/', $request->get('preview_destination')));
        $rule       = new Rule_Email();
        foreach ($addresses as $address) {
            if ($rule->isValid($address)) {
                $validMails[] = $address;
            }
        }
        $previewDestination = implode(', ', $validMails);
        $mail->setBcc($previewDestination, true);
        if ($mail->send()) {
            print "\n".$Language->getText('admin_massmail_execute','sending').": ".$previewDestination;
        } else {
            print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$previewDestination;
        }
    }
    print "\n".$Language->getText('admin_massmail_execute','done')."\n";
    flush();
}
?>