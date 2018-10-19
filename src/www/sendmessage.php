<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
require_once('common/mail/MailManager.class.php');
require_once('common/include/HTTPRequest.class.php');

define('FORMAT_TEXT', 0);
define('FORMAT_HTML', 1);

$request = HTTPRequest::instance();
$func = $request->getValidated('func', new Valid_WhiteList('restricted_user_request', 'private_project_request'), '');

if ($request->isPost() && $request->exist('Submit') &&  $request->existAndNonEmpty('func')) {
    $defaultMsg = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
    $pm = ProjectManager::instance();
    $dar = $pm->getMessageToRequesterForAccessProject($request->get('groupId'));
    if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
        $row = $dar->current();
        if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
            $defaultMsg = $row['msg_to_requester'];
        }
    }

    switch ($func) {
        case 'restricted_user_request':
            $sendMail = new Error_PermissionDenied_RestrictedUser();
            $vMessage = new Valid_Text('msg_restricted_user');
            $vMessage->required();
            if ($request->valid($vMessage) && (trim($request->get('msg_restricted_user'))!= $defaultMsg )) {
                $messageToAdmin = $request->get('msg_restricted_user');
            } else {
                exit_error($Language->getText('include_exit', 'error'),$Language->getText('sendmessage','invalid_msg'));
            }
            break;

        default:
            break;
    }

    $sendMail->processMail($messageToAdmin);
    exit;
}

$um = UserManager::instance();
$user = $um->getCurrentUser();
if (!$user->isLoggedIn()) {
    exit_error($Language->getText('include_exit', 'error'),$Language->getText('include_exit', 'not_logged_in'));
}

$email = $user->getEmail();

$valid = new Valid_Email('toaddress');
$valid->required();
if ($request->valid($valid)) {
    $toaddress = $request->get('toaddress');
}

$valid = new Valid_UInt('touser');
$valid->required();
if ($request->valid($valid)) {
    $touser = $request->get('touser');
}

if (!isset($toaddress) && !isset($touser)) {
	exit_error($Language->getText('include_exit', 'error'),$Language->getText('sendmessage','err_noparam'));
}

if (strpos(':', $GLOBALS['sys_default_domain']) === false) {
    $host = $GLOBALS['sys_default_domain'];
} else {
    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
}

if (isset($toaddress) && ! preg_match("/$host/",$toaddress)) {
	exit_error($Language->getText('include_exit', 'error'),
		   $Language->getText('sendmessage','err_host',array($host)));
}

$valid = new Valid_Text('subject');
$valid->required();
if ($request->valid($valid)) {
    $subject = $request->get('subject');
}

$valid = new Valid_Text('body');
$valid->required();
if ($request->valid($valid)) {
    $body = $request->get('body');
}

$csrf_token = new CSRFSynchronizerToken('sendmessage.php');
$purifier = Codendi_HTMLPurifier::instance();

if ($request->exist('send_mail')) {
    if (!$subject || !$body || !$email) {
        /*
         force them to enter all vars
         */
        exit_missing_param();
    }
    $csrf_token->check();


$valid = new Valid_Text('cc');
$valid->required();
if ($request->valid($valid)) {
    $cc = $request->get('cc');
}

$mail = new Codendi_Mail();
$mail->setFrom(ForgeConfig::get('sys_noreply'));
if (isset($touser)) {
    //Return the user given its user_id
    $to = $um->getUserById($touser);
    if (!$to) {
        exit_error($Language->getText('include_exit', 'error'),
        $Language->getText('sendmessage','err_nouser'));
    }
    $mail->setToUser(array($to));
    $dest = $to->getRealName();
} else if (isset($toaddress)) {
    $to = preg_replace('/_maillink_/i','@',$toaddress);
    $mail->setTo($to);
    $dest = $to;
}

if (isset($cc) && strlen($cc) > 0) {
    $mail->setCc($cc);
    $dest .= ','. $cc;
}

$mail->setSubject($subject);

$vFormat    = new Valid_WhiteList('body_format', array(FORMAT_HTML, FORMAT_TEXT));
$bodyFormat = $request->getValidated('body_format', $vFormat, FORMAT_HTML);

// Deal with CKEDITOR
if ($bodyFormat === false) {
    $ckeditor_format = $request->getValidated('comment_format', $vFormat, FORMAT_HTML);
    if ($ckeditor_format === 'html') {
        $bodyFormat = FORMAT_HTML;
    } else {
        $bodyFormat = FORMAT_TEXT;
    }
}

if ($bodyFormat == FORMAT_HTML) {
    $mail->getLookAndFeelTemplate()->set('title', $purifier->purify($subject, CODENDI_PURIFIER_CONVERT_HTML));
    $mail->setBodyHtml($body);
} else {
    $mail->setBodyText($body);
}
$mail->clearFrom();
$mail->setFrom(ForgeConfig::get('sys_noreply'));
$mail->addAdditionalHeader('Reply-To', $email);

if ($mail->send()) {
    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('sendmessage', 'title_sent', str_replace(',', ', ',$dest)));
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
    
}
$GLOBALS['Response']->redirect('/users/'.urlencode($to->getUserName()));
exit;
}

if (isset($toaddress) && $toaddress !== '') {
	$to_msg = $toaddress;
} else {
	$to_msg = $to->getUserName();
}
$to_msg = $purifier->purify($to_msg);

$HTML->header(array('title'=>$Language->getText('sendmessage', 'title',array($to_msg))));

?>

<H2><?php echo $Language->getText('sendmessage', 'title',array($to_msg)); ?></H2>
<P>
<?php echo $Language->getText('sendmessage', 'message'); ?>
<P>
<FORM ACTION="?" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="toaddress" VALUE="<?php echo $purifier->purify($toaddress); ?>">
<INPUT TYPE="HIDDEN" NAME="touser" VALUE="<?php echo $purifier->purify($touser); ?>">
<?php echo $csrf_token->fetchHTMLInput(); ?>

<B><?php echo $Language->getText('sendmessage', 'email'); ?>:</B> <?php echo $purifier->purify($email); ?>
<P>
<B><?php echo $Language->getText('sendmessage', 'subject'); ?>:</B><BR>
<INPUT TYPE="TEXT" NAME="subject" SIZE="30" MAXLENGTH="40" VALUE="<?php echo $purifier->purify($subject); ?>">
<P>
<B><?php echo $Language->getText('sendmessage', 'message_body'); ?>:</B><BR>
<TEXTAREA NAME="body" ROWS="15" COLS="60" WRAP="HARD"></TEXTAREA>
<P>
<CENTER>
<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="<?php echo $Language->getText('sendmessage', 'send_btn'); ?>">
</CENTER>
</FORM>
<?php
$HTML->footer(array());

?>
