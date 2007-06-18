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

require_once('pre.php');
require_once('account.php');
require_once('common/include/CookieManager.class.php');

$Language->loadLanguageMsg('account/account');

if (!session_issecure() && isset($GLOBALS['sys_https_host']) && ($GLOBALS['sys_https_host'] != "")) {
    //force use of SSL for login
    util_return_to('https://'.$GLOBALS['sys_https_host'].'/account/login.php');
    exit;
}

// ###### first check for valid login, if so, redirect

if (isset($login) && $login) {
    list($success, $status) = session_login_valid($form_loginname,$form_pw);
    if ($success) {
        account_redirect_after_login();
    }
}
$cookie_manager =& new CookieManager();
if ($cookie_manager->isCookie('session_hash') && $cookie_manager->getCookie('session_hash')) {
	//nuke their old session
    $cookie_manager->removeCookie('session_hash');
	session_delete($cookie_manager->getCookie('session_hash'));
}
$HTML->header(array('title'=>$Language->getText('account_login', 'title')));

if (isset($login) && $login && !$success) {

    if ($status == 'P') {
	echo "<P><B>".$Language->getText('account_login', 'pending_title')."</B>";
        if ($GLOBALS['sys_user_approval'] != 0) {
            echo "<P>".$Language->getText('account_login', 'need_approval');
        } else {
            echo "<P>".$Language->getText('account_login', 'pending_msg');
            echo "<P><A href=\"pending-resend.php?form_user=". htmlentities($form_loginname, ENT_QUOTES) ." \">[".$Language->getText('account_login', 'resend_btn')."]</A><br><hr><p>";
        }
    } else if ($status == 'V' || $status == 'W'){
        echo "<P>".$Language->getText('account_login', 'validation_msg');
        echo "<P><A href=\"pending-resend.php?form_user=". htmlentities($form_loginname, ENT_QUOTES) ." \">[".$Language->getText('account_login', 'resend_btn')."]</A><br><hr><p>";
    }else if ($status == 'S') {
	echo "<P><B>".$Language->getText('account_suspended', 'title')."</B>";
	echo "<P>".$Language->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']));
	echo "<br><hr><p>";
    }
}

if (isset($GLOBALS['sys_https_host']) && $GLOBALS['sys_https_host']) {
    $form_url = "https://".$GLOBALS['sys_https_host'];
} else {
    $form_url = "http://".$GLOBALS['sys_default_domain'];
}
?>

<p>
<h2><?php 
if(!isset($GLOBALS['sys_Name'])) {
    $GLOBALS['sys_Name'] = "";
 }
print $Language->getText('account_login', 'title', array($GLOBALS['sys_Name'])); ?>
<?php print ((isset($GLOBALS['sys_https_host']) && $GLOBALS['sys_https_host'] != "") ? ' ('.$Language->getText('account_login', 'secure').')':''); ?>
</h2>
<p>
<span class="highlight"><B><?php print $Language->getText('account_login', 'cookies'); ?>.</B></span>
<P>
<form action="<?php echo $form_url; ?>/account/login.php" method="post" name="form_login">
<INPUT TYPE="HIDDEN" NAME="return_to" VALUE="<?php if (isset($return_to)) { echo htmlentities(strip_tags($return_to), ENT_QUOTES); } ?>">
<p>
<?php print $Language->getText('account_login', 'name'); ?>:
<br><input type="text" name="form_loginname" VALUE="<?php if (isset($form_loginname)) { echo htmlentities($form_loginname, ENT_QUOTES); } ?>">
<p>
<?php print $Language->getText('account_login', 'password'); ?>:
<br><input type="password" name="form_pw">
<P>
<?php
// Only show the stay in SSL mode if the server is SSL enabled
// and it is not forced to operate in SSL mode
// and the stay in SSL check box can be shown
if ( isset($GLOBALS['sys_https_host']) && $GLOBALS['sys_https_host'] != '' && $GLOBALS['sys_force_ssl'] == 0 &&
     $GLOBALS['sys_stay_in_ssl'] == 1 ) {
    echo '<INPUT TYPE="CHECKBOX" NAME="stay_in_ssl" VALUE="1" '.
    (((browser_is_ie() && browser_get_version() < '5.1') || !session_issecure()) ?'':'CHECKED').'>'.
    $Language->getText('account_login', 'stay_ssl').'
<p>
';

    if  (browser_is_ie() && browser_get_version() < '5.1') {
	echo $Language->getText('account_login', 'msie_pb');
    }
}
?>
<p>
<input type="submit" name="login" value="<?php echo $Language->getText('account_login', 'login_btn'); ?>">
</form>
<P>
<?php 
$em =& EventManager::instance();
$display_lostpw_createaccount = true;
$em->processEvent('display_lostpw_createaccount', array('allow' => &$display_lostpw_createaccount));
if ($display_lostpw_createaccount) {
    echo $Language->getText('account_login', 'lost_pw',array($GLOBALS['sys_email_admin'],$GLOBALS['sys_name'])); 
    echo '<P>';
    echo $Language->getText('account_login', 'create_acct',array($GLOBALS['sys_name'],$GLOBALS['sys_org_name'])); 
}

$em =& EventManager::instance();
$em->processEvent('login_after_form', array());

?>

<SCRIPT language="JavaScript"> <!-- 
    document.form_login.form_loginname.focus();
// --></SCRIPT>

<?php

$HTML->footer(array());

?>
