<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('common/mail/Mail.class');

$Language->loadLanguageMsg('homepage/homepage');

if (!$toaddress && !$touser) {
	exit_error($Language->getText('include_exit', 'error'),$Language->getText('sendmessage','err_noparam'));
}

if ($touser) {
	/*
		check to see if that user even exists
		Get their name and email if it does
	*/
	$result=db_query("SELECT email,user_name FROM user WHERE user_id='$touser'");
	if (!$result || db_numrows($result) < 1) {
	    exit_error($Language->getText('include_exit', 'error'),
		       $Language->getText('sendmessage','err_nouser'));
	}
}

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

if ($toaddress && !eregi($host,$toaddress)) {
	exit_error($Language->getText('include_exit', 'error'),
		   $Language->getText('sendmessage','err_host',array($host)));
}


if ($send_mail) {
	if (!$subject || !$body || !$name || !$email) {
		/*
			force them to enter all vars
		*/
		exit_missing_param();
	}

	if ($toaddress) {
		/*
			send it to the toaddress
		*/
		$to=eregi_replace('_maillink_','@',$toaddress);
	} else if ($touser) {
		/*
			figure out the user's email and send it there
		*/
		$to=db_result($result,0,'email');
	}
    
	$mail =& new Mail();
    $mail->setTo($to);
    $dest = $to;
    if (isset($_REQUEST['cc']) && count($_REQUEST['cc']) > 0) {
        $cc = util_normalize_emails($_REQUEST['cc']);
        $mail->setCc($cc);
        $dest .= ','.$cc;
    }
    $mail->setSubject(stripslashes($subject));
    $mail->setBody(stripslashes($body));
    $mail->setFrom($email);
    $mail_is_send = $mail->send();

    if (!$mail_is_send) {
        exit_error($GLOBALS['Language']->getText('global', 'error'), 
                    $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
    }
	site_header(array('title'=>$Language->getText('sendmessage', 'title_sent',array($to))));
    echo '<H2>'.$Language->getText('sendmessage', 'title_sent',str_replace(',', ', ',$dest)).'</H2>';
	$HTML->footer(array());
	exit;

}

if ($toaddress) {
	$to_msg = $toaddress;
} else {
	$to_msg = db_result($result,0,'user_name');
}

$HTML->header(array('title'=>$Language->getText('sendmessage', 'title',array($to_msg))));

?>

<H2><?php echo $Language->getText('sendmessage', 'title',array($to_msg)); ?></H2>
<P>
<?php echo $Language->getText('sendmessage', 'message'); ?>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="toaddress" VALUE="<?php echo $toaddress; ?>">
<INPUT TYPE="HIDDEN" NAME="touser" VALUE="<?php echo $touser; ?>">

<B><?php echo $Language->getText('sendmessage', 'email'); ?>:</B><BR>
<INPUT TYPE="TEXT" NAME="email" SIZE="30" MAXLENGTH="40" VALUE="">
<P>
<B><?php echo $Language->getText('sendmessage', 'name'); ?>:</B><BR>
<INPUT TYPE="TEXT" NAME="name" SIZE="30" MAXLENGTH="40" VALUE="">
<P>
<B><?php echo $Language->getText('sendmessage', 'subject'); ?>:</B><BR>
<INPUT TYPE="TEXT" NAME="subject" SIZE="30" MAXLENGTH="40" VALUE="<?php echo $subject; ?>">
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
