<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    

if (!$toaddress && !$touser) {
	exit_error('Error','Error - some variables were not provided');
}

if ($touser) {
	/*
		check to see if that user even exists
		Get their name and email if it does
	*/
	$result=db_query("SELECT email,user_name FROM user WHERE user_id='$touser'");
	if (!$result || db_numrows($result) < 1) {
		exit_error('Error','Error - That user does not exist.');
	}
}

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

if ($toaddress && !eregi($host,$toaddress)) {
	exit_error("error","You can only send to addresses @".$host);
}


if ($send_mail) {
	if (!$subject || !$body || !$name || !$email) {
		/*
			force them to enter all vars
		*/
		exit_missing_param();
	}

	$hdrs = 'From: '. $name .' <'. $email .'>'.$GLOBALS['sys_lf'];
	$hdrs .='Content-type: text/plain; charset=iso-8859-1'.$GLOBALS['sys_lf'];

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
	mail($to, stripslashes($subject),stripslashes($body),$hdrs);
	$HTML->header(array('title'=>($GLOBALS['sys_name'].' Contact')));
	echo '<H2>Message sent</H2>';
	$HTML->footer(array());
	exit;

}

$HTML->header(array('title'=>($GLOBALS['sys_name'].' Staff')));

?>

<H2>Send a Message to <?php 

if ($toaddress) {
	echo $toaddress;
} else {
	echo db_result($result,0,'user_name');
}

?></H2>
<P>
In an attempt to reduce spam, we are using this form to send email.
<p>
Fill it out accurately and completely or the receiver may not be able to respond.
<P>
<span class="highlight"><B>IF YOU ARE WRITING FOR HELP:</B> Did you read the site 
documentation? Did you include your <B>user_id</B> and <B>user_name?</B> If you are writing 
about a project, include your <B>project id</B> (<B>group_id</B>) and <B>Project Name</B>.
</span>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="toaddress" VALUE="<?php echo $toaddress; ?>">
<INPUT TYPE="HIDDEN" NAME="touser" VALUE="<?php echo $touser; ?>">

<B>Your Email Address:</B><BR>
<INPUT TYPE="TEXT" NAME="email" SIZE="30" MAXLENGTH="40" VALUE="">
<P>
<B>Your Name:</B><BR>
<INPUT TYPE="TEXT" NAME="name" SIZE="30" MAXLENGTH="40" VALUE="">
<P>
<B>Subject:</B><BR>
<INPUT TYPE="TEXT" NAME="subject" SIZE="30" MAXLENGTH="40" VALUE="<?php echo $subject; ?>">
<P>
<B>Message:</B><BR>
<TEXTAREA NAME="body" ROWS="15" COLS="60" WRAP="HARD"></TEXTAREA>
<P>
<CENTER>
<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="Send Message">
</CENTER>
</FORM>
<?php
$HTML->footer(array());

?>
