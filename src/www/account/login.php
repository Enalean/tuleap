<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
header("Cache-Control: no-cache");
header("Cache-Control: must-revalidate");

require_once('pre.php');
require_once('account.php');
require_once('common/include/CookieManager.class.php');

$GLOBALS['Language']->loadLanguageMsg('account/account');

$em =& EventManager::instance();

if (!session_issecure() && isset($GLOBALS['sys_https_host']) && ($GLOBALS['sys_https_host'] != "")) {
    //force use of SSL for login
    util_return_to('https://'.$GLOBALS['sys_https_host'].'/account/login.php');
    exit;
}

//
// Validate input
//

// Clean variables
$_cVar = array();
// Raw variables
$_rVar = array();
$request =& HTTPRequest::instance();

$_rVar['form_loginname'] = null;
if($request->exist('form_loginname')) {
    $_rVar['form_loginname'] = $request->get('form_loginname');
}

$_rVar['form_pw'] = null;
if($request->exist('form_pw')) {
    $_rVar['form_pw'] = $request->get('form_pw');
}

// Should test if numeric and in (0,1,2)
$_cVar['pv'] = null;
if($request->exist('pv')) {
    $_cVar['pv'] = (int) $request->get('pv');
}

$_rVar['return_to'] = null;
if($request->exist('return_to')) {
    $_rVar['return_to'] = $request->get('return_to');
}

// Should test if numeric and in (0,1)
$_cVar['stay_in_ssl'] = null;
if($request->exist('stay_in_ssl')) {
    $_cVar['stay_in_ssl'] = (int) $request->get('stay_in_ssl');
}

//
// Application
//

// first check for valid login, if so, redirect
$success = false;
$status = null;
if ($request->isPost()) {
    list($success, $status) = session_login_valid($_rVar['form_loginname'], $_rVar['form_pw']);
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

$purifier =& CodeX_HTMLPurifier::instance();

$userStatusBox = '';
switch($status) {
 case 'P':
     $userStatusBox .= "<p><strong>".$GLOBALS['Language']->getText('account_login', 'pending_title')."</strong>";
     if ($GLOBALS['sys_user_approval'] != 0) {
         $userStatusBox .= "<p>".$GLOBALS['Language']->getText('account_login', 'need_approval');
     } else {
         $userStatusBox .= "<p>".$GLOBALS['Language']->getText('account_login', 'pending_msg');
         $userStatusBox .= "<p><a href=\"pending-resend.php?form_user=".$purifier->purify($_rVar['form_loginname'])." \">[".$GLOBALS['Language']->getText('account_login', 'resend_btn')."]</a></p>";
     }
     break;

 case 'V':
 case 'W':
     $userStatusBox .= "<p>".$GLOBALS['Language']->getText('account_login', 'validation_msg')."</p>";
     $userStatusBox .= "<p><a href=\"pending-resend.php?form_user=".$purifier->purify($_rVar['form_loginname'])." \">[".$GLOBALS['Language']->getText('account_login', 'resend_btn')."]</a></p>";
        break;

 case 'S':
     $userStatusBox .= "<p><strong>".$GLOBALS['Language']->getText('account_suspended', 'title')."</strong>";
     $userStatusBox .= "<p>".$GLOBALS['Language']->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']))."</p>";
     break;
}

// Display mode
$pvMode = false;
if($_cVar['pv'] == 2) {
    $pvMode = true;
}

// Form target
$_useHttps = false;
if (isset($GLOBALS['sys_https_host']) && $GLOBALS['sys_https_host']) {
    $_useHttps = true;
    $form_url = "https://".$GLOBALS['sys_https_host'];
} else {
    $form_url = "http://".$GLOBALS['sys_default_domain'];
}
$form_url .= '/account/login.php';

// Page title
$pageTitle = $GLOBALS['Language']->getText('account_login', 'title');
if($_useHttps) {
    $pageTitle .= ' ('.$GLOBALS['Language']->getText('account_login', 'secure').')';
}

//
// Start output
//

if($pvMode) {
    $GLOBALS['HTML']->pv_header(array('title'=>$pageTitle));
} else {
    $GLOBALS['HTML']->header(array('title'=>$pageTitle));
}

if($userStatusBox != '') {
    echo $userStatusBox;
    echo "<hr />";
}

?>

<h2><?php echo $pageTitle ?></h2>

<p>
<span class="highlight"><strong><?php echo $GLOBALS['Language']->getText('account_login', 'cookies'); ?></strong></span>
</p>

<form action="<?php echo $form_url; ?>" method="post" name="form_login" autocomplete="off">
<input type="hidden" name="return_to" value="<?php echo $purifier->purify($_rVar['return_to']); ?>">
<input type="hidden" name="pv" value="<?php echo $_cVar['pv']; ?>">

<p>
<?php print $GLOBALS['Language']->getText('account_login', 'name'); ?>:
<br>
<input type="text" name="form_loginname" value="<?php echo $purifier->purify($_rVar['form_loginname']); ?>">
</p>

<p>
<?php print $GLOBALS['Language']->getText('account_login', 'password'); ?>:
<br>
<input type="password" name="form_pw" value="">
</p>

<?php
// Only show the stay in SSL mode if the server is SSL enabled
// and it is not forced to operate in SSL mode
// and the stay in SSL check box can be shown
if ($_useHttps && $GLOBALS['sys_force_ssl'] == 0 && $GLOBALS['sys_stay_in_ssl'] == 1 ) {
    $checked = '';
    $ieMsg = '';
    if((browser_is_ie() && browser_get_version() < '5.1') ||
       !session_issecure()) {
        $checked = ' checked="checked"';
        $ieMsg = $GLOBALS['Language']->getText('account_login', 'msie_pb');
    }

    echo '<p>';
    echo '<input type="checkbox" name="stay_in_ssl" value="1"'.$checked.'>';
    echo $GLOBALS['Language']->getText('account_login', 'stay_ssl');
    echo '</p>';

    if($ieMsg) {
        echo '<p>'.$ieMsg.'</p>';
    }
}
?>

<p>
<input type="submit" name="login" value="<?php echo $GLOBALS['Language']->getText('account_login', 'login_btn'); ?>">
</p>

</form>

<?php
$display_lostpw_createaccount = true;
$em->processEvent('display_lostpw_createaccount', array('allow' => &$display_lostpw_createaccount));
if ($display_lostpw_createaccount) {
    echo '<p>';
    echo $GLOBALS['Language']->getText('account_login', 'lost_pw',array($GLOBALS['sys_email_admin'],$GLOBALS['sys_name']));
    echo '</p>';
    echo '<p>';
    echo $GLOBALS['Language']->getText('account_login', 'create_acct',array($GLOBALS['sys_name'],$GLOBALS['sys_org_name']));
    echo '</p>';
}

$em->processEvent('login_after_form', array());

?>

<script type="text/javascript">
<!--
    document.form_login.form_loginname.focus();
//-->
</script>

<?php

if ($pvMode) {
    $GLOBALS['HTML']->pv_footer(array());
} else {
    $GLOBALS['HTML']->footer(array());
}

?>
