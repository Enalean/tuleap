<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('account.php');
require_once('common/event/EventManager.class.php');
require_once('common/system_event/SystemEventManager.class.php');

$GLOBALS['HTML']->includeCalendarScripts();

session_require(array('group'=>'1','admin_flags'=>'A'));

$request = HTTPRequest::instance();
$um      = UserManager::instance();
$em      = EventManager::instance();

$user_id = null;
$user    = null;

// Validate user
$vUserId = new Valid_UInt('user_id');
$vUserId->required();
if ($request->valid($vUserId)) {
    $user_id = $request->get('user_id');
    $user    = $um->getUserById($user_id);
}
if (!$user_id || !$user) {
    $GLOBALS['Response']->addFeedback('error', 'Invalid user');
}

// Validate action
$vAction = new Valid_Whitelist('action', array('update_user'));
$vAction->required();
if ($request->valid($vAction)) {
    $action = $request->get('action');
} else {
    $action = '';
}

if ($request->isPost()) {
    if ($action == 'update_user') {
        /*
         * Update the user
         */
        $vDate = new Valid('expiry_date');
        $vDate->addRule(new Rule_Date());
        //$vDate->required();
        if (!$request->valid($vDate)) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_usergroup','data_not_parsed'));
        } else {
            if ($request->existAndNonEmpty('expiry_date')) {
                $date_list = split('-', $request->get('expiry_date'), 3);
                $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
                if ($user->getExpiryDate() != $unix_expiry_time) {
                    $user->setExpiryDate($unix_expiry_time);
                }
            } else {
                if ($user->getExpiryDate()) {
                    $user->setExpiryDate('');
                }
            }

            $vShell = new Valid_WhiteList('form_shell', $user->getAllUnixShells());
            $vShell->required();
            if ($request->valid($vShell)) {
                $user->setShell($request->get('form_shell'));
            }

            $vEmail = new Valid_Email('email');
            $vEmail->required();
            if ($request->valid($vEmail)) {
                $user->setEmail($request->get('email'));
            }

            $vRealName = new Valid_String('form_realname');
            $vRealName->required();
            if ($request->valid($vRealName)) {
                $user->setRealName($request->get('form_realname'));
            }

            // form_unixstatus must be BEFORE form_status validation because
            // form_status can constraint form_unixstatus
            $vUnixStatus = new Valid_WhiteList('form_unixstatus', $user->getAllUnixStatus());
            $vUnixStatus->required();
            if ($request->valid($vUnixStatus)) {
                $user->setUnixStatus($request->get('form_unixstatus'));
            }

            // New status must be valid AND user account must already be validated
            // There are specific actions done in approve_pending scripts
            $accountActivationEvent = null;
            $vStatus = new Valid_WhiteList('form_status', $user->getAllWorkingStatus());
            $vStatus->required();
            if ($request->valid($vStatus)
                && in_array($user->getStatus(), $user->getAllWorkingStatus())
                && $user->getStatus() != $request->get('form_status')) {
                switch ($request->get('form_status')) {
                    case User::STATUS_ACTIVE:
                        $user->setStatus($request->get('form_status'));
                        $accountActivationEvent = 'project_admin_activate_user';
                        break;

                    case User::STATUS_RESTRICTED:
                        if (isset($GLOBALS['sys_allow_restricted_users']) && $GLOBALS['sys_allow_restricted_users'] == 1) {
                            $user->setStatus($request->get('form_status'));
                            // If the user had a shell, set it to restricted shell
                            if ($user->getShell()
                                && ($user->getShell() != "/bin/false")
                                && ($user->getShell() != "/sbin/nologin")) {
                                $user->setShell($GLOBALS['codendi_bin_prefix'].'/cvssh-restricted');
                            }
                            $accountActivationEvent = 'project_admin_activate_user';
                        }
                        break;

                    case User::STATUS_DELETED:
                        $user->setStatus($request->get('form_status'));
                        $user->setUnixStatus($user->getStatus());
                        $accountActivationEvent = 'project_admin_delete_user';
                        break;

                    case User::STATUS_SUSPENDED:
                        $user->setStatus($request->get('form_status'));
                        $user->setUnixStatus($user->getStatus());
                        break;
                }
            }

            // Change login name
            if ($user->getUserName() != $request->get('form_loginname')) {
                if (SystemEventManager::instance()->canRenameUser($user)) {
                    $vLoginName = new Valid_UserNameFormat('form_loginname');
                    $vLoginName->required();
                    if ($request->valid($vLoginName)) {
                        switch ($user->getStatus()) {
                            case User::STATUS_PENDING:
                            case User::STATUS_VALIDATED:
                            case User::STATUS_VALIDATED_RESTRICTED:
                                $user->setUserName($request->get('form_loginname'));
                                break;
                            default:
                                $em->processEvent(Event::USER_RENAME, array('user_id'  => $user->getId(),
                                                                    'new_name' => $request->get('form_loginname')));
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_usergroup','rename_user_msg', array($user->getUserName(), $request->get('form_loginname'))));
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_usergroup','rename_user_warn'));
                        }
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_usergroup', 'rename_user_already_queued'), CODENDI_PURIFIER_DISABLED);
                }
            }

            if ($GLOBALS['sys_auth_type'] == 'ldap') {
                $vLdapId = new Valid_String('ldap_id');
                $vLdapId->required();
                if ($request->existAndNonEmpty('ldap_id') && $request->valid($vLdapId)) {
                    $user->setLdapId($request->get('ldap_id'));
                } else {
                    $user->setLdapId("");
                }
            }

            // Run the update
            if ($um->updateDb($user)) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_usergroup','success_upd_u'));
                if ($accountActivationEvent) {
                    $em->processEvent($accountActivationEvent, array('user_id' => $user->getId()));
                }
            }

            if ($user->getUnixStatus() != 'N' && !$user->getUnixUid()) {
                $um->assignNextUnixUid($user);
            }

            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id='.$user->getId());
        }
    }
}

$HTML->header(array('title'=>$Language->getText('admin_usergroup','title')));
$hp = Codendi_HTMLPurifier::instance();
?>

<h2><?php echo $Language->getText('admin_usergroup','header').": ".$user->getUserName()." (ID ".$user->getId().")"; ?></h2>

<h3><?php echo $Language->getText('admin_usergroup','account_info'); ?></h3>

<FORM method="post" name="update_user" action="?">
<INPUT type="hidden" name="action" value="update_user">
<INPUT type="hidden" name="user_id" value="<?php echo $user->getId(); ?>">

<table>

<tr><td colspan="2">
<a href="/users/<?php echo $user->getUserName(); ?>">[<?php echo $Language->getText('admin_usergroup','user_public_profile'); ?>]</a>
</td></tr>

<tr><td colspan="2">
<a href="user_changepw.php?user_id=<?php echo $user->getId(); ?>">[<?php echo $Language->getText('admin_usergroup','change_passwd'); ?>]</a>

<?php $em->processEvent('usergroup_data', array('user_id' => $user->getId())); ?>
<br/></td></tr>

<tr><td>
<?php echo $GLOBALS['Language']->getText('account_options', 'codendi_login'); ?>:
</td><td>
<INPUT TYPE="TEXT" NAME="form_loginname" VALUE="<?php echo  $hp->purify($user->getUserName(), CODENDI_PURIFIER_CONVERT_HTML) ; ?>" SIZE="50">
</td></tr>

<tr><td>
<?php echo $GLOBALS['Language']->getText('account_options', 'realname'); ?>:
</td><td>
<INPUT TYPE="TEXT" NAME="form_realname" VALUE="<?php echo  $hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML) ; ?>" SIZE="50">
</td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup','email'); ?>:
</td><td>
<INPUT TYPE="TEXT" NAME="email" VALUE="<?php echo $hp->purify($user->getEmail(), CODENDI_PURIFIER_CONVERT_HTML); ?>" SIZE="50">
</td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup','status'); ?>:
</td><td>
<?php 
$statusVals = $user->getAllWorkingStatus();
if (in_array($user->getStatus(), $statusVals)) {
    // Assume getAllWorkingStatus() order never change
    $statusTxts = array($Language->getText('admin_usergroup','active'),
    $Language->getText('admin_usergroup','restricted'),
    $Language->getText('admin_usergroup','suspended'),
    $Language->getText('admin_usergroup','deleted'));

    echo html_build_select_box_from_arrays($statusVals, $statusTxts, 'form_status', $user->getStatus(), false);
} else {
    echo '<strong>'.$Language->getText('admin_usergroup','cannot_change_status').'</strong>';
}
?>
</td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup','expiry_date'); 
$exp_date='';
if($user->getExpiryDate() != 0){
   $exp_date = format_date('Y-m-d', $user->getExpiryDate()); 
}

?>:
</td><td>
<?php echo $GLOBALS['HTML']->getDatePicker("expiry_date", "expiry_date", $exp_date); ?>
</td></tr>

<?php 

if ($GLOBALS['sys_auth_type'] == 'ldap') {
    echo '<tr><td>';
    echo $Language->getText('admin_usergroup', 'ldap_id').': ';
    echo '</td><td>';
    echo '<input type="text" name="ldap_id" value="'.$user->getLdapId().'" size="50" />';
    echo '</td></tr>';
}
?>

<tr><td colspan="2"><strong><?php echo $Language->getText('admin_usergroup','unix_details'); ?></strong></td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup','unix_status'); ?>:
</td><td>
<?php 
$unixStatusVals = $user->getAllUnixStatus();
// Assume getAllUnixStatus() order never change
$unixStatusTxts = array($Language->getText('admin_usergroup','no_account'),
                        $Language->getText('admin_usergroup','active'),
                        $Language->getText('admin_usergroup','suspended'),
                        $Language->getText('admin_usergroup','deleted'));

echo html_build_select_box_from_arrays($unixStatusVals, $unixStatusTxts, 'form_unixstatus', $user->getUnixStatus(), false);
?>
</td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup','shell'); ?>:
</td><td>
<SELECT name="form_shell">
<?php account_shellselects($user->getShell()); ?>
</SELECT>
</td></tr>

<tr><td colspan="2"><strong><?php echo $Language->getText('admin_usergroup','account_details'); ?></strong></td></tr>

<tr><td>
<?php 
$userInfo = $um->getUserAccessInfo($user);
echo $Language->getText('admin_usergroup', 'last_access_date');
?>:
</td><td>
<?php echo html_time_ago($userInfo['last_access_date']); ?>
</td></tr>

<tr><td>
<?php echo $Language->getText('admin_usergroup', 'last_pwd_update'); ?>:
</td><td>
<?php echo html_time_ago($user->getLastPwdUpdate());?>
</td></tr>

<tr><td>
<?php echo $Language->getText('account_options', 'auth_attempt_last_success'); ?>
</td><td>
<?php echo html_time_ago($userInfo['last_auth_success']);?>
</td></tr>

<tr><td>
<?php echo $Language->getText('account_options', 'auth_attempt_last_failure'); ?>
</td><td>
<?php echo html_time_ago($userInfo['last_auth_failure']);?>
</td></tr>

<tr><td>
<?php echo $Language->getText('account_options', 'auth_attempt_nb_failure'); ?>
</td><td>
<?php echo html_time_ago($userInfo['nb_auth_failure']); ?>
</td></tr>

<tr><td>
<?php echo $Language->getText('account_options', 'auth_attempt_prev_success'); ?>
</td><td>
<?php echo html_time_ago($userInfo['last_auth_success']);?>
</td></tr>

<tr><td>
<?php echo $Language->getText('include_user_home','member_since'); ?>:
</td><td>
<?php echo html_time_ago($user->getAddDate());?>
</td></tr>


<?php 
if(isset($GLOBALS['sys_enable_user_skills']) && $GLOBALS['sys_enable_user_skills']) {
    echo '<tr><td>';
    echo $Language->getText('include_user_home','user_prof').': ';
    echo '</td><td>';
    echo '<a href="/people/viewprofile.php?user_id='.$user->getId().'">'.$Language->getText('include_user_home','see_skills').'</a>';
    echo '</td></tr>';
}
?>

<?php
// Plugins entries
$entry_label = array();
$entry_value = array();

$eParams = array();
$eParams['user_id']     =  $user->getId();
$eParams['entry_label'] =& $entry_label;
$eParams['entry_value'] =& $entry_value;
$em->processEvent('user_home_pi_entry', $eParams);

foreach($entry_label as $key => $label) {
    $value = $entry_value[$key];
    echo '<tr><td>';
    echo $label;
    echo '</td><td>';
    echo $value;
    echo '</td></tr>';
}
?>

</table>

<INPUT type="submit" name="Update_Unix" class="btn btn-primary" value="<?php echo $Language->getText('global','btn_update'); ?>">

</FORM>


<?php if($GLOBALS['sys_user_approval'] == 1){ ?>
<HR>
<H3><?php echo $Language->getText('admin_approve_pending_users','purpose'); ?>:</H3>
<?php echo  $hp->purify($user->getRegisterPurpose(), CODENDI_PURIFIER_CONVERT_HTML) ; 
}?>

<HR>

<H3><?php echo $Language->getText('admin_usergroup','current_groups'); ?></H3>

<ul>
<?php
/*
 Iterate and show groups this user is in
 */
$res_cat = db_query("SELECT groups.group_name AS group_name, "
. "groups.group_id AS group_id, "
. "user_group.admin_flags AS admin_flags FROM "
. "groups,user_group WHERE user_group.user_id=".db_ei($user->getId())." AND "
. "groups.group_id=user_group.group_id");

$pm = ProjectManager::instance();

while ($row_cat = db_fetch_array($res_cat)) {
    echo '<li>';
    echo '<a href="groupedit.php?group_id='. $row_cat['group_id'] .'"><b>'. $pm->getProject($row_cat['group_id'])->getPublicName() . '</b></a>';
    if ($row_cat['admin_flags'] === 'A') {
        echo '&nbsp;(admin)';
    }
    echo '</li>';

}

?>
</ul>

<script type="text/javascript">
codendi.locales['admin_usergroup'] = {
        'was': '<?php echo $Language->getText('admin_usergroup','was'); ?>'
};
</script>
<script type="text/javascript" src="usergroup.js"></script>

<?php
$HTML->footer(array());
?>
