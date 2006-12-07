<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

// This screen is a part of the "hidden" registeration process under CodeX when
// LDAP is enabled.
// In this case, there is no more "Create new account" for new users but the
// login process automaticaly create new account when:
// * given user exist in LDAP
// * this user is authenticated against the LDAP
// * corresponding codex account do not exist:
//   * imply codex username not exist
//   * imply ldap_name not already in DB

require_once('pre.php');
require_once('timezones.php');
require_once('account.php');
require_once('../include/UserLdap.class.php');

function welcome_exit_error($title,$text) {
    global $HTML,$Language;
    $GLOBALS['feedback'] .= $title;
    site_header(array('title'=>$Language->getText('include_exit','exit_error'),
                      'registeration_process' => true));
    echo '<p>',$text,'</p>';
	$HTML->footer(array('showfeedback' => false));
	exit;
}

function update_account($user_id, $tz='None', $mail_site=0, $mail_va=0) {
    global $TZs;

    $_user_id = (int) $user_id;
    if(!in_array($tz, $TZs)) {
        $_tz = 'None';
    }
    else {
        $_tz = $tz;
    }
    
    $_mail_site = (int) $mail_site;
    $_mail_va = (int) $mail_va;

    $sql = 'UPDATE user SET '
        . ' mail_siteupdates='.$_mail_site
        . ', mail_va='.$_mail_va
        . ', timezone="'.$_tz.'"'
        . ' WHERE user_id='.$_user_id;

    $res = db_query($sql);
    if (!$res) {
        return false;
    }
    return true;    
}

$Language->loadLanguageMsg('ldap', 'ldap');

$user_id = user_getid();

if($_POST['action'] == 'update_reg') {   
    if ($_POST['timezone'] == 'None') {
        welcome_exit_error($Language->getText('plugin_ldap', 'welcome_error_up'),
                           $Language->getText('plugin_ldap', 'welcome_err_notz'));        
    }

    // Update DB
    if(!update_account($user_id,
                       $_POST['timezone'],
                       $_POST['form_mail_site'],
                       $_POST['form_mail_va'])) {
        welcome_exit_error($Language->getText('plugin_ldap', 'welcome_error_up'),
                           $Language->getText('plugin_ldap', 'welcome_error_up_expl', array(db_error())));
    }
            
    account_redirect_after_login();
}
else {

    $timezone = (isset($HTTP_POST_VARS['timezone'])?stripslashes($timezone):'None');
    $title_type = $GLOBALS['sys_auth_type'];

    $lr =& UserLdap::getLdapResultSetFromUserId(user_getid());
    $ldap_name = $lr->getLogin();

    $star = '<span class="highlight"><big>*</big></span>';

    $HTML->header(array('title'=>$Language->getText('plugin_ldap', 'welcome_title', array($lr->getCommonName()))
                        ,'registeration_process' => true));

    print '<h2>';
    print $Language->getText('plugin_ldap', 'welcome_title', array($lr->getCommonName()));
    print '</h2>';

    print '<h3>';
    print $Language->getText('plugin_ldap', 'welcome_first_login', array($GLOBALS['sys_name']));
    print '</h3>';

    print '<p>'.$Language->getText('plugin_ldap', 'welcome_fill_form', array($GLOBALS['sys_name'])).'</p>';

    print '<fieldset>';
    
    print '<legend>'.$Language->getText('plugin_ldap', 'welcome_preferences').'</legend>';

    $return_to = '';
    if(array_key_exists('return_to', $_REQUEST) && $_REQUEST['return_to'] != '') {
        $return_to = $_REQUEST['return_to'];
    }
   
    print '
<form name="welcome" action="/plugins/ldap/welcome.php" method="post">
<input type="hidden" name="return_to" value="'.$return_to.'">
<input type="hidden" name="action" value="update_reg">

<p>'.$star.' '.$Language->getText('plugin_ldap', 'welcome_tz').':';

    echo html_get_timezone_popup ('timezone',$timezone);

    print '</p>
<p><input type="checkbox" name="form_mail_site" value="1" checked />'.$Language->getText('plugin_ldap', 'welcome_siteupdate');

    print '</p>
<p><input type="checkbox" name="form_mail_va" value="1" />'.$Language->getText('plugin_ldap', 'welcome_communitymail').'</p>';
    
    print '<p>'.$Language->getText('plugin_ldap', 'welcome_mandatory', array($star)).'</p>';

    print '<p><input type="submit" name="update_reg" value="'.$Language->getText('plugin_ldap', 'welcome_btn_update').'"></p>';
    print '</fieldset>';

    print '<fieldset>';
    print '<legend>'.$Language->getText('plugin_ldap', 'welcome_your_data', array($GLOBALS['sys_org_name'])).'</legend>';

    print '<table>
<tr>
<td>'.$Language->getText('plugin_ldap', 'welcome_ldap_login').'</td>
<td><strong>'.$ldap_name.'</strong></td>
</tr>
<tr>
<td>'.$Language->getText('plugin_ldap', 'welcome_email').'</td>
<td><strong>'.user_getemail(user_getid()).'</strong></td>
</tr>
<tr>
<td>'.$Language->getText('plugin_ldap', 'welcome_codex_login', array($GLOBALS['sys_name'])).'</td>
<td>'.user_getname(user_getid()).'<br>
'.$Language->getText('plugin_ldap', 'welcome_codex_login_j', array($GLOBALS['sys_name'])).'
</td>
</tr>
</table>';

    print '</fieldset>';    

    $HTML->footer(array());
}
?>