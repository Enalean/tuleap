<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

Header( "Expires: Wed, 11 Nov 1998 11:11:11 GMT"); 
Header( "Cache-Control: no-cache"); 
Header( "Cache-Control: must-revalidate"); 

require ('pre.php');

/*

if (!session_issecure()) {
	//force use of SSL for login
	header('Location: https://'.$HTTP_HOST.'/account/login.php');
}

*/

// ###### first check for valid login, if so, redirect

if ($login) {
	$success=session_login_valid($form_loginname,$form_pw);
	if ($success) {
		/*
			You can now optionally stay in SSL mode
		*/
		if ($stay_in_ssl) {
			$ssl_='s';
		} else {
			$ssl_='';
		}
		if ($return_to) {
			header ("Location: http".$ssl_."://". $sys_default_domain . $return_to);
			exit;
		} else {
			header ("Location: http".$ssl_."://". $sys_default_domain ."/my/");
			exit;
		}
	}
}
if ($session_hash) {
	//nuke their old session
	session_cookie('session_hash','');
	db_query("DELETE FROM session WHERE session_hash='$session_hash'");
}
$HTML->header(array('title'=>'Login'));

if ($login && !$success) {
		
	if ($feedback == "Account Pending") {

		?>
		<P><B>Pending Account</B>

		<P>Your account is currently pending your email confirmation.
		Visiting the link sent to you in this email will activate your account.

		<P>If you need this email resent, please click below and a confirmation
		email will be sent to the email address you provided in registration.

		<P><A href="pending-resend.php?form_user=<?php print $form_loginname; ?>">[Resend Confirmation Email]</A>

		<br><hr>
		<p>


		<?php
	} else {
		
		echo '<h2><FONT COLOR="RED">'. $feedback .'</FONT></H2>';
	} //end else

}

if (browser_is_windows() && browser_is_ie() && browser_get_version() < '5.1') {
	echo '<H2><FONT COLOR="RED">Internet Explorer users need to
	upgrade to IE 5.01 or higher, preferably with 128-bit SSL or use Netscape 4.7 or higher</FONT></H2>';
}

if (browser_is_ie() && browser_is_mac()) {
	echo '<H2><FONT COLOR="RED">Internet Explorer on the Macintosh 
	is not supported currently. Use Netscape 4.7 or higher</FONT></H2>';
}


?>
	
<p>
<b>SourceForge Site Login</b>
<p>
<font color="red"><B>Cookies must be enabled past this point.</B></font>
<P>
<form action="https://<?php echo $sys_default_domain; ?>/account/login.php" method="post">
<INPUT TYPE="HIDDEN" NAME="return_to" VALUE="<?php echo $return_to; ?>">
<p>
Login Name:
<br><input type="text" name="form_loginname" VALUE="<?php echo $form_loginname; ?>">
<p>
Password:
<br><input type="password" name="form_pw">
<P>
<INPUT TYPE="CHECKBOX" NAME="stay_in_ssl" VALUE="1" <?php echo ((browser_is_ie() && browser_get_version() < '5.5')?'':'CHECKED') ?>> Stay in SSL mode after login
<p>
You will be connected with an SSL server and your password will not be visible to other users. 
<P>
<B>Internet Explorer</B> users will have intermittent SSL problems, so they should leave SSL 
after login. Netscape users should stay in SSL mode permanently for maximum security.
Visit <A HREF="http://www.microsoft.com/">Microsoft</A> for more information about this known problem.
<P>
<input type="submit" name="login" value="Login">
</form>
<P>
<A href="lostpw.php">[Lost your password?]</A>
<P>
<A HREF="register.php">[New Account]</A>

<?php

$HTML->footer(array());

?>
