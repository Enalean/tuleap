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

require('../include/pre.php');

if (!session_issecure() && ($GLOBALS['sys_https_host'] != "")) {
    //force use of SSL for login
    header('Location: https://'.$GLOBALS['sys_https_host'].'/account/login.php');
}

// ###### first check for valid login, if so, redirect

if ($login) {
    $success=session_login_valid($form_loginname,$form_pw);
    if ($success) {
	/*
	  You can now optionally stay in SSL mode
	*/
	$use_ssl = session_issecure()
		&& $GLOBALS['sys_https_host'] != ""
		&& ($GLOBALS['sys_force_ssl']
		    || !$GLOBALS['sys_stay_in_ssl']
		    || $HTTP_POST_VARS['stay_in_ssl']
		    );

	if ($return_to) {
	    // if return_to URL start with a protocol name then take as is
	    // otherwise prepend the proper http protocol
	    if (preg_match("/^\s*\w*:\/\//", $return_to)) {
		header("Location: $return_to");
	    } else {
		if ($use_ssl) {
		    header("Location: https://".$GLOBALS['sys_https_host'].$return_to);
		} else {
		    header("Location: http://".$GLOBALS['sys_default_domain'].$return_to);
		}
	    }
	    exit;
	} else {
	    if ($use_ssl) {
		header("Location: https://".$GLOBALS['sys_https_host']."/my/");
	    } else {
		header("Location: http://".$GLOBALS['sys_default_domain']."/my/");
	    }
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
		
		echo '<h2><span class="feedback">'. $feedback .'</span></H2>';
	} //end else

}

if ($GLOBALS['sys_https_host']) {
    $form_url = "https://".$GLOBALS['sys_https_host'];
} else {
    $form_url = "http://".$GLOBALS['sys_default_domain'];
}
?>

<p>
<h2><?php print $GLOBALS['sys_name']; ?> Site Login
<?php print ($GLOBALS['sys_https_host'] != "" ? ' (Secure)':''); ?>
</h2>
<p>
<span class="highlight"><B>Cookies must be enabled past this point.</B></span>
<P>
<form action="<?php echo $form_url; ?>/account/login.php" method="post" name="form_login">
<INPUT TYPE="HIDDEN" NAME="return_to" VALUE="<?php echo $return_to; ?>">
<p>
Login Name:
<br><input type="text" name="form_loginname" VALUE="<?php echo $form_loginname; ?>">
<p>
Password:
<br><input type="password" name="form_pw">
<P>
<?php
// Only show the stay in SSL mode if the server is SSL enabled
// and it is not forced to operate in SSL mode
// and the stay in SSL check box can be shown
if ( $GLOBALS['sys_https_host'] != '' && $GLOBALS['sys_force_ssl'] == 0 &&
     $GLOBALS['sys_stay_in_ssl'] == 1 ) {
    echo '<INPUT TYPE="CHECKBOX" NAME="stay_in_ssl" VALUE="1" '.
    (((browser_is_ie() && browser_get_version() < '5.1') || !session_issecure()) ?'':'CHECKED').'>'.
    'Stay in secure connection mode after login';
    echo '<br><em>
&nbsp;&nbsp;&nbsp;(You will be connected with a secure Web server and all your web pages will travel encrypted over the network).
</em>
<p>
';

    if  (browser_is_ie() && browser_get_version() < '5.1') {
	echo '<B>Internet Explorer </B> users (prior to 5.1) will have intermittent SSL problems,
so they should leave SSL after login. Visit <A HREF="http://www.microsoft.com/">Microsoft</A>
for more information about this known problem.';
    }
}
?>
<p>
<input type="submit" name="login" value="Login">
</form>
<P>
<b><A href="lostpw.php">[Lost your password?]</A></b><BR> If you have
lost your password please do not create another account but follow us
and we'll help you <a href="lostpw.php">remember your lost
password</a>. If it fails then contact the <a
href="mailto:<?php print $GLOBALS['sys_email_admin']; ?>"><b><?php print $GLOBALS['sys_name']; ?></b>
site administrators</a>.
<P>
<b><A HREF="register.php">[Create a new Account]</A></b><BR> If it's
your first time on the <b><?php print $GLOBALS['sys_name']; ?></b>
site you can become a member right now ! The creation of a <a
href="register.php">new account</a> takes a few seconds and you can
take advantage of the services offered by the <?php print
$GLOBALS['sys_name']; ?> site to all <?php print $GLOBALS['sys_org_name']; ?> developers.

<SCRIPT language="JavaScript"> <!-- 
    document.form_login.form_loginname.focus();
// --></SCRIPT>

<?php

$HTML->footer(array());

?>
