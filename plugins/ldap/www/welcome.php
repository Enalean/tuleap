<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

// This screen is a part of the "hidden" registeration process under Codendi when
// LDAP is enabled.
// In this case, there is no more "Create new account" for new users but the
// login process automaticaly create new account when:
// * given user exist in LDAP
// * this user is authenticated against the LDAP
// * corresponding codendi account do not exist:
//   * imply codendi username not exist
//   * imply ldap_name not already in DB

require_once('pre.php');
require_once('timezones.php');
require_once('account.php');
require_once('../include/LDAP_UserManager.class.php');
require_once('common/valid/ValidFactory.class.php');

$hp = Codendi_HTMLPurifier::instance();

function welcome_exit_error($title,$text) {
    global $HTML,$Language,$pv;
    $GLOBALS['feedback'] .= $title;
    if (isset($pv) && $pv == 2)  
        $HTML->pv_header(array());
    else site_header(array('title'=>$Language->getText('include_exit','exit_error'),
                      'registeration_process' => true));		
    echo '<p>',$text,'</p>';
	(isset($pv) && $pv == 2) ? $HTML->pv_footer(array()) : $HTML->footer(array('showfeedback' => false));
	exit;
}

function userValuesHaveNotBeenModified(PFUser $current_user, $timezone, $mailVa, $mailSite)
{
    return $current_user->getTimezone() == $timezone &&
           $current_user->getMailVA() == $mailVa &&
           $current_user->getMailSiteUpdates() == $mailSite;
}

// LDAP plugin enabled
$pluginManager = PluginManager::instance();
$ldapPlugin = $pluginManager->getPluginByName('ldap');
if (!$ldapPlugin || !$pluginManager->isPluginAvailable($ldapPlugin)) {
    $GLOBALS['Response']->redirect('/my');
}


$um = UserManager::instance();
$currentUser = $um->getCurrentUser();
$user_id = $currentUser->getId();
$timezone = $request->get('timezone');

if($request->isPost() && $request->existAndNonEmpty('action')) {
    if (! is_valid_timezone($timezone)) {
        welcome_exit_error($Language->getText('plugin_ldap', 'welcome_error_up'),
                           $Language->getText('plugin_ldap', 'welcome_err_notz'));
    }

    $mailSite = 0;
    $vMailSite = new Valid_WhiteList('form_mail_site', array('1'));
    $vMailSite->required();
    if($request->valid($vMailSite)) {
        $mailSite = 1;
    }
    
    $mailVa = 0;
    $vMailVa = new Valid_WhiteList('form_mail_va', array('1'));
    $vMailVa->required();
    if($request->valid($vMailVa)) {
        $mailVa = 1;
    }
    
    if ($currentUser) {
        $currentUser->setTimezone($timezone);
        $currentUser->setMailVA($mailVa);
        $currentUser->setMailSiteUpdates($mailSite);
        $currentUser->setUnixStatus('A');
        if (userValuesHaveNotBeenModified($currentUser, $timezone, $mailVa, $mailSite) || $um->updateDb($currentUser)) {
            $ldapUserDao = new LDAP_UserDao(CodendiDataAccess::instance());
            $ldapUserDao->setLoginDate($user_id, $_SERVER['REQUEST_TIME']);
        } else {
            welcome_exit_error($Language->getText('plugin_ldap', 'welcome_error_up'),
                               $Language->getText('plugin_ldap', 'welcome_error_up_expl', array('')));
        }
    }
    account_redirect_after_login($request->get('return_to'));
}
else {
    $pv = 0;
    $vPv = new Valid_Pv();
    if($request->valid($vPv)) {
        $pv = $request->get('pv');
    }

    $ldapUm = $ldapPlugin->getLdapUserManager();
    $lr = $ldapUm->getLdapFromUserId($user_id);
    $ldap_name = $lr->getLogin();

    $star = '<span class="highlight"><big>*</big></span>';

    if ($pv == 2)  {
        $HTML->pv_header(array());
    } else {
        $HTML->header(array('title'=>$Language->getText('plugin_ldap', 'welcome_title', array($lr->getCommonName())),
                            'registeration_process' => true));
    }
    
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
    $vReturnTo = new Valid_String('return_to');
    $vReturnTo->required();
    if($request->valid($vReturnTo)) {
        $return_to = trim($request->get('return_to'));
    }
   
    print '
<form name="welcome" action="'.$ldapPlugin->getPluginPath().'/welcome.php" method="post">
<input type="hidden" name="return_to" value="'. $hp->purify($return_to, CODENDI_PURIFIER_CONVERT_HTML) .'">
<input type="hidden" name="action" value="update_reg">
<input type="hidden" name="pv" value="'.$pv.'">

<p>'.$star.' '.$Language->getText('plugin_ldap', 'welcome_tz').':';

    echo html_get_timezone_popup($timezone);

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
<td><strong>'.$currentUser->getEmail().'</strong></td>
</tr>
<tr>
<td>'.$Language->getText('plugin_ldap', 'welcome_codendi_login', array($GLOBALS['sys_name'])).'</td>
<td>'.$currentUser->getUserName().'<br>
'.$Language->getText('plugin_ldap', 'welcome_codendi_login_j', array($GLOBALS['sys_name'])).'
</td>
</tr>
</table>';

    print '</fieldset>';    

    ($pv == 2) ? $HTML->pv_footer(array()) : $HTML->footer(array());
}
?>