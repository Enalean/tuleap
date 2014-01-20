<?php
/*
 * Copyright (C) 2010 Roland Mas, Olaf Lenz
 * Copyright (c) Enalean, 2014. All rights reserved
 *
 * This file is part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/** This contains the local settings for Mediawiki as used in the
 *  Mediawiki plugin of Tuleap.
 */

/* C style inclusion guard. Yes, I know. Don’t comment on it. */
if (!isset($fusionforge_plugin_mediawiki_LocalSettings_included)) {
$fusionforge_plugin_mediawiki_LocalSettings_included = true;

// Force include of HTTPRequest here instead of relying on autoload for this
// very specific class. Problems come from mediawiki inclusion: mediawiki also
// have an HttpRequest class (but no longer used, in a .old file) and in MW,
// But this class is referenced in MW autoloader (loaded before Tuleap one)
// so when tuleap stuff in pre.php instanciate HTTPRequest (like logger) it instanciate
// mediawiki HttpRequest instead of the Tuleap one.
// This is a short term hack, in a longer term we should namespace tuleap HTTPRequest
// But wait for PHP5.3 min compat.

require_once('common/include/Codendi_Request.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once 'pre.php';
require_once 'plugins_utils.php';
require_once 'common/user/UserManager.class.php';
require_once dirname(__FILE__) .'/../include/MediawikiDao.class.php';

sysdebug_lazymode(true);

$IP = forge_get_config('src_path', 'mediawiki');

if (!isset ($fusionforgeproject)) {
    $gr=new Group(1);
    $fusionforgeproject=$gr->getUnixName();
}

$exppath = explode('/', $_SERVER['PHP_SELF']) ;

# determine $fusionforgeproject from the URL
while (count ($exppath) >= 4) {
    if (($exppath[0] == 'plugins') &&
        ($exppath[1] == 'mediawiki') &&
        ($exppath[2] == 'wiki') &&
        in_array($exppath[4], array(
            'api.php',
            'index.php',
            'load.php',
        ))) {
            $fusionforgeproject = $exppath[3] ;
            break ;
    } else {
            array_shift ($exppath) ;
    }
}

$gconfig_dir = forge_get_config('mwdata_path', 'mediawiki');
$project_dir = forge_get_config('projects_path', 'mediawiki') . "/"
	. $fusionforgeproject ;

if (!is_dir($project_dir)) {
    exit_error (sprintf(_('Mediawiki for project %s not created yet, please wait for a few minutes.'), $fusionforgeproject.':'.$project_dir)) ;
}

$path = array( $IP, "$IP/includes", "$IP/languages" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );

require_once( "$IP/includes/AutoLoader.php" );
require_once( "$IP/includes/Defines.php" );
require_once( "$IP/includes/DefaultSettings.php" );

if ( $wgCommandLineMode ) {
    if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
            die( "This script must be run from the command line\n" );
    }
}

$group = group_get_object_by_name($fusionforgeproject) ;

$wgSitename         = $group->getPublicName() . " Wiki";
$wgScriptPath       = "/plugins/mediawiki/wiki/$fusionforgeproject" ;
$wgEmergencyContact = forge_get_config('admin_email');
$wgPasswordSender   = forge_get_config('admin_email');
$wgDBtype           = "forge";
$wgDBserver         = forge_get_config('database_host') ;

if (forge_get_config('mw_dbtype', 'mediawiki') == 'mysql') {
    // At the time writing schema in mysql is synonym for database
    $wgDBname    = MediawikiDao::getMediawikiDatabaseName($group);
    $wgDBprefix = 'mw';
} else {
    $wgDBname = forge_get_config('database_name');
}

$wgDBuser           = forge_get_config('database_user') ;
$wgDBpassword       = forge_get_config('database_password') ;
$wgDBadminuser      = forge_get_config('database_user') ;
$wgDBadminpassword  = forge_get_config('database_password') ;
$wgDBport           = forge_get_config('database_port') ;
$wgDBmwschema       = str_replace ('-', '_', "plugin_mediawiki_$fusionforgeproject") ;
$wgDBts2schema      = str_replace ('-', '_', "plugin_mediawiki_$fusionforgeproject") ;
$wgMainCacheType    = CACHE_NONE;
$wgMemCachedServers = array();

//$wgEnableUploads = forge_get_config('enable_uploads', 'mediawiki');
$wgEnableUploads             = true;
$wgUploadDirectory           = "$project_dir/images";
$wgUseImageMagick            = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";
$wgLocalInterwiki            = $wgSitename;
$wgShowExceptionDetails      = true ;

// disable language selection
$wgHiddenPrefs[] = 'language';
$user            = UserManager::instance()->getCurrentUser();
$wgLanguageCode  = substr($user->getLocale(), 0, 2);

$wgDefaultSkin    = 'tuleap';
$wgHtml5          = false;
$wgStyleDirectory = forge_get_config('codendi_dir').forge_get_config('mw_style_path', 'mediawiki');
$wgWellFormedXml  = true;
$wgLogo           = "";

$GLOBALS['sys_dbhost']         = forge_get_config('database_host') ;
$GLOBALS['sys_dbport']         = forge_get_config('database_port') ;
$GLOBALS['sys_dbname']         = forge_get_config('database_name') ;
$GLOBALS['sys_dbuser']         = forge_get_config('database_user') ;
$GLOBALS['sys_dbpasswd']       = forge_get_config('database_password') ;
$GLOBALS['sys_plugins_path']   = forge_get_config('plugins_path') ;
$GLOBALS['sys_urlprefix']      = forge_get_config('url_prefix') ;
$GLOBALS['sys_use_ssl']        = forge_get_config('use_ssl') ;
$GLOBALS['sys_default_domain'] = forge_get_config('web_host') ;
$GLOBALS['sys_custom_path']    = forge_get_config('custom_path') ;
$GLOBALS['gfwww']              = $gfwww ;
$GLOBALS['gfplugins']          = $gfplugins ;
$GLOBALS['sys_lang']           = forge_get_config('default_language') ;
$GLOBALS['sys_urlroot']        = forge_get_config('url_root');
$GLOBALS['sys_session_key']    = forge_get_config('session_key');
$GLOBALS['sys_session_expire'] = forge_get_config('session_expire');
$GLOBALS['REMOTE_ADDR']        = getStringFromServer('REMOTE_ADDR') ;
$GLOBALS['HTTP_USER_AGENT']    = getStringFromServer('HTTP_USER_AGENT') ;

require_once("$IP/includes/Exception.php");
require_once("$IP/includes/db/Database.php");

if (forge_get_config('mw_dbtype', 'mediawiki') == 'mysql') {
    require_once 'DatabaseForgeMysql.php';
} else {
    require_once 'DatabaseForgePgsql.php';
}

function TuleapMediawikiAuthentication($user, &$result) {
    global $fusionforgeproject, $wgGroupPermissions ;

    session_set();

    if (session_loggedin()) {
            $forge_user     = session_get_user();
            $group          = group_get_object_by_name($fusionforgeproject);
            $madiawiki_name = ucfirst($forge_user->getUnixName()) ;
            $mediawiki_user = User::newFromName($madiawiki_name);

            if ($mediawiki_user->getID() == 0) {
                    $mediawiki_user->addToDatabase();
                    $mediawiki_user->setPassword(User::randomPassword());
                    $mediawiki_user->setRealName($forge_user->getRealName());
                    $mediawiki_user->setToken();
                    $mediawiki_user->loadFromDatabase();
            }

            $user->mId = $mediawiki_user->getID();
            $user->loadFromId() ;
            $user = defineUserMediawikiGroups($user, $group);

            $user->setCookies();
            $user->saveSettings();
            wfSetupSession();
    } else {
            $user->logout ();
    }

    $result = true;
    return true ;
}

function defineUserMediawikiGroups(User $mediawiki_user, Group $group) {
    $user = UserManager::instance()->getCurrentUser();

    $mediawiki_user->removeGroup('ForgeRole');

    if ($user->isMember($group->getID(), 'A')) {
        $mediawiki_user->addGroup('bureaucrat');
        $mediawiki_user->addGroup('sysop');

    } else if (($group->isPublic() && ! $user->isAnonymous()) || $user->isMember($group->getID())) {
        $mediawiki_user->addGroup('user');
        $mediawiki_user->addGroup('autoconfirmed');
        $mediawiki_user->addGroup('emailconfirmed');

    } else {
        $mediawiki_user->addGroup('*');
    }

    $mediawiki_user = removeUnconsistantMediawikiGroups($mediawiki_user);

    return $mediawiki_user;
}

function removeUnconsistantMediawikiGroups(User $mediawiki_user) {
    $mediawiki_explicit_groups = $mediawiki_user->getGroups();

    foreach ($mediawiki_explicit_groups as $current_mediawiki_group) {
        if (preg_match('/^ForgeRole*/', $current_mediawiki_group)) {
            $mediawiki_user->removeGroup($current_mediawiki_group);
        }
    }

    return $mediawiki_user;
}

function customizeMediawikiGroupsRights(array $wgGroupPermissions) {
    $wgGroupPermissions['bureaucrat']['userrights'] = false;

    return $wgGroupPermissions;
}

function NoLinkOnMainPage(&$personal_urls){
    unset($personal_urls['anonlogin']);
    unset($personal_urls['anontalk']);
    unset($personal_urls['logout']);
    unset($personal_urls['login']);
    return true;
}

$wgHooks['PersonalUrls'][]='NoLinkOnMainPage';

if (isset($_SERVER['SERVER_SOFTWARE'])) {
	class SpecialForgeRedir extends SpecialPage {
		var $dstappendself = false;

		function getTitle($subpage="") {
			return 'SpecialForgeRedir';
		}

		function getRedirect($subpage="") {
			return $this;
		}

		function getRedirectQuery() {
			return $this;
		}

		function getFullUrl() {
			$u = $this->dst;
			if ($this->dstappendself) {
				$u .= urlencode(getStringFromServer('REQUEST_URI'));
			}
			return util_make_url($u);
		}
	}

	class SpecialForgeRedirLogin extends SpecialForgeRedir {
		var $dstappendself = true;
		var $dst = '/account/login.php?return_to=';
	}

	class SpecialForgeRedirCreateAccount extends SpecialForgeRedir {
		var $dst = '/account/register.php';
	}

	class SpecialForgeRedirResetPass extends SpecialForgeRedir {
		var $dst = '/account/lostpw.php';
	}

	class SpecialForgeRedirLogout extends SpecialForgeRedir {
		var $dstappendself = true;
		var $dst = '/account/logout.php?return_to=';
	}

	function DisableLogInOut(&$mList) {
		$mList['Userlogin'] = 'SpecialForgeRedirLogin';
		$mList['CreateAccount'] = 'SpecialForgeRedirCreateAccount';
		$mList['Resetpass'] = 'SpecialForgeRedirResetPass';
		$mList['Userlogout'] = 'SpecialForgeRedirLogout';
		return true;
	}
	$GLOBALS['wgHooks']['SpecialPage_initList'][] = 'DisableLogInOut';
}

$GLOBALS['wgHooks']['UserLoadFromSession'][] = 'TuleapMediawikiAuthentication';

$wgGroupPermissions = customizeMediawikiGroupsRights($wgGroupPermissions);

$wgFavicon     = '/images/icon.png' ;
$wgBreakFrames = false ;

if (forge_get_config('unbreak_frames', 'mediawiki')) {
	$wgEditPageFrameOptions = false;
}

ini_set ('memory_limit', '100M') ;

// LOAD THE SITE-WIDE AND PROJECT-SPECIFIC EXTRA-SETTINGS
if (is_file(forge_get_config('config_path')."/plugins/mediawiki/LocalSettings.php")) {
    include(forge_get_config('config_path')."/plugins/mediawiki/LocalSettings.php");
}

// debian style system-wide mediawiki extensions
if (is_file("/etc/mediawiki-extensions/extensions.php")) {
    include '/etc/mediawiki-extensions/extensions.php';
}

if (file_exists("$wgUploadDirectory/.wgLogo.png")) {
    $wgLogo = "$wgScriptPath/images/.wgLogo.png";
}

// forge global settings
if (is_file("$gconfig_dir/ForgeSettings.php")) {
    include ("$gconfig_dir/ForgeSettings.php") ;
}
// project specific settings
if (is_file("$project_dir/ProjectSettings.php")) {
    include ("$project_dir/ProjectSettings.php") ;
}

}