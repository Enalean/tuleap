<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('account.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
    global $Language;

    if (!isset($GLOBALS['Update'])) {
        return 0;
    }
    if (!isset($GLOBALS['user_id'])) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_userid'));
        return 0;
    }
    if (!isset($GLOBALS['form_pw'])) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_nopasswd'));
        return 0;
    }
    if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_passwd'));
        return 0;
    }
    if (!account_pwvalid($GLOBALS['form_pw'], $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        return 0;
    }
	
    // if we got this far, it must be good
    if (!account_set_password($GLOBALS['user_id'],$GLOBALS['form_pw']) ) {
        $GLOBALS['register_error'] = $Language->getText('admin_user_changepw','error_update');
        return 0;
    }
    return 1;
}

// ###### first check for valid login, if so, congratulate
$HTML->includeJavascriptFile('/scripts/prototype/prototype.js');
$HTML->includeJavascriptFile('/scripts/check_pw.js');
if (register_valid()) {
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw','title_changed')));
    $d = getdate(time());
    $h = ($sys_crondelay - 1) - ($d['hours'] % $sys_crondelay);
    $m= 60 - $d['minutes'];
?>
<h3><?php echo $Language->getText('admin_user_changepw','header_changed'); ?></h3>
<p><?php echo $Language->getText('admin_user_changepw','msg_changed',array($h,$m)); ?></h3>

<p><a href="/admin"><?php echo $Language->getText('global','back'); ?></a>.
<?php
} else { // not valid registration, or first time to page
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw','title')));

    require_once('common/event/EventManager.class.php');
    $em =& EventManager::instance();
    $em->processEvent('before_admin_change_pw', array());

?>
<h3><?php echo $Language->getText('admin_user_changepw','header'); ?></h3>
<?php if (isset($register_error)) print '<p><span class="highlight">$register_error</span>'; ?>
<form action="user_changepw.php" method="post">
<table><tr valign='top'><td><?php echo $Language->getText('admin_user_changepw','new_passwd'); ?>:
<br><input type="password" name="form_pw" id="form_pw">
<p><?php echo $Language->getText('admin_user_changepw','new_passwd2'); ?>:
<br><input type="password" name="form_pw2">
</td><td>
<?php
$password_strategy =& new PasswordStrategy();
include($GLOBALS['Language']->getContent('account/password_strategy'));
foreach($password_strategy->validators as $key => $v) {
    echo '<div id="password_validator_msg_'. $key .'">'. $v->description() .'</div>';
}
?></td></tr></table>
<script type="text/javascript">
var password_validators = [<?= implode(', ', array_keys($password_strategy->validators)) ?>];
</script>
<INPUT type=hidden name="user_id" value="<?php print $user_id; ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global','btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
