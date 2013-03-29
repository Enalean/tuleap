<?php
/*
 * Copyright (C) 2010 Roland Mas, Olaf Lenz
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/** This contains the local settings for Mediawiki as used in the
 *  Mediawiki plugin of FusionForge.
 */

/* C style inclusion guard. Yes, I know. Don’t comment on it. */
if (!isset($fusionforge_plugin_mediawiki_LocalSettings_included)) {
$fusionforge_plugin_mediawiki_LocalSettings_included = true;

require_once 'pre.php';
require_once 'plugins_utils.php';
require_once 'common/user/UserManager.class.php';

//require_once 'common/include/RBACEngine.class.php';
sysdebug_lazymode(true);

$IP = forge_get_config('src_path', 'mediawiki');

if (!isset ($fusionforgeproject)) {
	$gr=new Group(1);
	$fusionforgeproject=$gr->getUnixName();
}

$exppath = explode ('/', $_SERVER['PHP_SELF']) ;

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

$g = group_get_object_by_name($fusionforgeproject) ;
$wgSitename         = $g->getPublicName() . " Wiki";
$wgScriptPath       = "/plugins/mediawiki/wiki/$fusionforgeproject" ;

$wgEmergencyContact = forge_get_config('admin_email');
$wgPasswordSender = forge_get_config('admin_email');

$wgDBtype           = "forge";
$wgDBserver         = forge_get_config('database_host') ;
if (forge_get_config('mw_dbtype', 'mediawiki')=='mysql'){
	// At the time writing schema in mysql is synonym for database
	$wgDBname           = 'plugin_mediawiki_'.$fusionforgeproject;
	$wgDBprefix         = 'mw';
} else {
	$wgDBname           = forge_get_config('database_name');
}
$wgDBuser           = forge_get_config('database_user') ;
$wgDBpassword       = forge_get_config('database_password') ;
$wgDBadminuser           = forge_get_config('database_user') ;
$wgDBadminpassword       = forge_get_config('database_password') ;
$wgDBport           = forge_get_config('database_port') ;
$wgDBmwschema       = str_replace ('-', '_', "plugin_mediawiki_$fusionforgeproject") ;
$wgDBts2schema      = str_replace ('-', '_', "plugin_mediawiki_$fusionforgeproject") ;
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

//$wgEnableUploads = forge_get_config('enable_uploads', 'mediawiki');
$wgEnableUploads = true;
$wgUploadDirectory = "$project_dir/images";
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";
$wgLocalInterwiki   = $wgSitename;
$wgShowExceptionDetails = true ;

$wgLanguageCode = strtolower(forge_get_config('default_country_code'));

$wgDefaultSkin = 'tuleap';

$wgHtml5 = false;
$wgStyleDirectory = forge_get_config('codendi_dir').forge_get_config('mw_style_path', 'mediawiki');
$wgWellFormedXml = true;
$wgLogo = "";

/* DEBUG
$wgDebugLogFile         = '/tmp/wiki.log';
$wgDebugLogPrefix       = '';
$wgDebugRedirects       = true;
$wgDebugRawPage         = true;
$wgDebugComments        = true;
$wgLogQueries           = true;
$wgDebugDumpSql         = true;
$wgDebugLogGroups       = array();
$wgShowDebug            = true;
$wgSpecialVersionShowHooks =  true;
$wgShowSQLErrors        = true;
$wgColorErrors          = true;
$wgShowExceptionDetails = true;
$wgShowHostnames = true;
*/


$GLOBALS['sys_dbhost'] = forge_get_config('database_host') ;
$GLOBALS['sys_dbport'] = forge_get_config('database_port') ;
$GLOBALS['sys_dbname'] = forge_get_config('database_name') ;
$GLOBALS['sys_dbuser'] = forge_get_config('database_user') ;
$GLOBALS['sys_dbpasswd'] = forge_get_config('database_password') ;
$GLOBALS['sys_plugins_path'] = forge_get_config('plugins_path') ;
$GLOBALS['sys_urlprefix'] = forge_get_config('url_prefix') ;
$GLOBALS['sys_use_ssl'] = forge_get_config('use_ssl') ;
$GLOBALS['sys_default_domain'] = forge_get_config('web_host') ;
$GLOBALS['sys_custom_path'] = forge_get_config('custom_path') ;
$GLOBALS['gfwww'] = $gfwww ;
$GLOBALS['gfplugins'] = $gfplugins ;
$GLOBALS['sys_lang'] = forge_get_config('default_language') ;
$GLOBALS['sys_urlroot'] = forge_get_config('url_root');
$GLOBALS['sys_session_key'] = forge_get_config('session_key');
$GLOBALS['sys_session_expire'] = forge_get_config('session_expire');
$GLOBALS['REMOTE_ADDR'] = getStringFromServer('REMOTE_ADDR') ;
$GLOBALS['HTTP_USER_AGENT'] = getStringFromServer('HTTP_USER_AGENT') ;

require_once("$IP/includes/Exception.php");
require_once("$IP/includes/db/Database.php");
if (forge_get_config('mw_dbtype', 'mediawiki')=='mysql'){
	require_once 'DatabaseForgeMysql.php';
}else{
	require_once 'DatabaseForgePgsql.php';
}

function FusionForgeRoleToMediawikiGroupName ($role, $project) {
	if ($role instanceof RoleAnonymous) {
		return '*';
	} elseif ($role instanceof RoleLoggedIn) {
		return 'user';
	} elseif ($role->getHomeProject() == NULL) {
		return sprintf ('ForgeRole:%s [global]',
				$role->getName ()) ;
	} elseif ($role->getHomeProject()->getID() != $project->getID()) {
		return sprintf ('ForgeRole:%s [project %s]',
				$role->getName (),
				$role->getHomeProject()->getUnixName()) ;
	} else {
		return sprintf ('ForgeRole:%s',
				$role->getName ()) ;
	}
}

function FusionForgeMWAuth( $user, &$result ) {
	global $fusionforgeproject, $wgGroupPermissions ;

	session_set();

        if (session_loggedin()) {
                $u = session_get_user();
		$g = group_get_object_by_name ($fusionforgeproject) ;

                $mwname = ucfirst($u->getUnixName ()) ;
                $mwu = User::newFromName ($mwname);
                if($mwu->getID() == 0) {
                        $mwu->addToDatabase ();
                        $mwu->setPassword (User::randomPassword());
                        $mwu->setRealName ($u->getRealName ()) ;
                        $mwu->setToken ();
                        $mwu->loadFromDatabase ();
                }
                $user->mId=$mwu->getID();
                $user->loadFromId() ;

		$current_groups = $user->getGroups() ;

		$available_roles = RBACEngine::getInstance()->getAvailableRoles() ;
		$rs = array () ;
		foreach ($available_roles as $r) {
			$linked_projects = $r->getLinkedProjects () ;
			
			if ($r->hasGlobalPermission('forge_admin')) {
				$rs[] = $r ;
				continue ;
			}
				
			foreach ($linked_projects as $lp) {
				if ($lp->getID() == $g->getID()) {
					$rs[] = $r ;
					continue ;
				}
			}
		}

		// Sync MW groups for current user with FF roles
		$rnames = array () ;
		foreach ($rs as $r) {
			$rnames[] = FusionForgeRoleToMediawikiGroupName ($r, $g) ;
		}
		$role_groups = preg_grep ("/^ForgeRole:/", $current_groups) ;

		foreach ($rnames as $rname) {
			if (!in_array ($rname, $current_groups)) {
				$user->addGroup ($rname) ;
			}
		}
		foreach ($role_groups as $cg) {
			if (!in_array ($cg, $rnames)) {
				$user->removeGroup ($cg) ;
			}
		}

                $user->setCookies ();
                $user->saveSettings ();
		wfSetupSession ();
	} else {
		$user->logout ();
        }

	$result = true;
	return true ;
}

function SetupPermissionsFromRoles () {
	global $fusionforgeproject, $wgGroupPermissions ;

        $group = group_get_object_by_name ($fusionforgeproject) ;
	// Setup rights for all roles referenced by project
	$role_ids = $group->getRolesID() ;
	$rbac_engine = RBACEngine::getInstance();
        
	$global_roles = $rbac_engine->getGlobalRoles();
	foreach ($global_roles as $role) {
		$role_ids[] = $role->getID();
	}
	$role_ids = array_unique($role_ids);
	$roles = array();
	foreach ($role_ids as $rid) {
		$roles[] = $rbac_engine->getRoleById($rid);
	}

	$wgGroupPermissions['*']['read'] = true;
        $wgGroupPermissions['*']['edit'] = true;
        $wgGroupPermissions['*']['createpage'] = true;

        $user = UserManager::instance()->getCurrentUser();

        if ($user->isAnonymous() || ! $user->isMember($group->getID())) {
            $wgGroupPermissions['*']['edit']                = false;
            $wgGroupPermissions['*']['createpage']          = false;
            $wgGroupPermissions['*']['writeapi']            = false;
            $wgGroupPermissions['*']['move-subpages']       = false;
            $wgGroupPermissions['*']['move-rootuserpages']  = false;
            $wgGroupPermissions['*']['reupload-shared']     = false;
            $wgGroupPermissions['*']['createaccount']       = false;
            $wgGroupPermissions['*']['createtalk']          = false;
            $wgGroupPermissions['*']['minoredit']           = false;
            $wgGroupPermissions['*']['move']                = false;
            $wgGroupPermissions['*']['delete']              = false;
            $wgGroupPermissions['*']['undelete']            = false;
            $wgGroupPermissions['*']['upload']              = false;
            $wgGroupPermissions['*']['reupload-own']        = false;
            $wgGroupPermissions['*']['reupload']            = false;
            $wgGroupPermissions['*']['upload_by_url']       = false;
            $wgGroupPermissions['*']['editinterface']       = false;
            $wgGroupPermissions['*']['import']              = false;
            $wgGroupPermissions['*']['importupload']        = false;
            $wgGroupPermissions['*']['siteadmin']           = false;
            $wgGroupPermissions['*']['interwiki']           = false;
        }
        
	foreach ($roles as $role) {
		$mw_group_name = FusionForgeRoleToMediawikiGroupName ($role, $group) ;
                
		// Read access
		$wgGroupPermissions[$mw_group_name]['read'] = $role->hasPermission ('plugin_mediawiki_read', $group->getID()) ;

		// Day-to-day edit privileges
		$wgGroupPermissions[$mw_group_name]['edit']               = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editexisting') ;
		$wgGroupPermissions[$mw_group_name]['writeapi']           = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editexisting') ;
		$wgGroupPermissions[$mw_group_name]['createpage']         = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editnew') ;
		$wgGroupPermissions[$mw_group_name]['createtalk']         = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editnew') ;
		$wgGroupPermissions[$mw_group_name]['minoredit']          = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editnew') ;
		$wgGroupPermissions[$mw_group_name]['move']               = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editmove') ;
		$wgGroupPermissions[$mw_group_name]['move-subpages']      = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editmove') ;
		$wgGroupPermissions[$mw_group_name]['move-rootuserpages'] = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editmove') ;
		$wgGroupPermissions[$mw_group_name]['delete']             = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editmove') ;
		$wgGroupPermissions[$mw_group_name]['undelete']           = $role->hasPermission ('plugin_mediawiki_edit', $group->getID(), 'editmove') ;

		// File upload privileges
		$wgGroupPermissions[$mw_group_name]['upload']          = $role->hasPermission ('plugin_mediawiki_upload', $group->getID(), 'upload') ;
		$wgGroupPermissions[$mw_group_name]['reupload-own']    = $role->hasPermission ('plugin_mediawiki_upload', $group->getID(), 'upload') ;
		$wgGroupPermissions[$mw_group_name]['reupload']        = $role->hasPermission ('plugin_mediawiki_upload', $group->getID(), 'reupload') ;
		$wgGroupPermissions[$mw_group_name]['reupload-shared'] = $role->hasPermission ('plugin_mediawiki_upload', $group->getID(), 'reupload') ;
		$wgGroupPermissions[$mw_group_name]['upload_by_url']   = $role->hasPermission ('plugin_mediawiki_upload', $group->getID(), 'reupload') ;

		// Administrative tasks
		$wgGroupPermissions[$mw_group_name]['editinterface'] = $role->hasPermission ('plugin_mediawiki_admin', $group->getID()) ;
		$wgGroupPermissions[$mw_group_name]['import']        = $role->hasPermission ('plugin_mediawiki_admin', $group->getID()) ;
		$wgGroupPermissions[$mw_group_name]['importupload']  = $role->hasPermission ('plugin_mediawiki_admin', $group->getID()) ;
		$wgGroupPermissions[$mw_group_name]['siteadmin']     = $role->hasPermission ('plugin_mediawiki_admin', $group->getID()) ;

		// Interwiki management restricted to forge admins
		$wgGroupPermissions[$mw_group_name]['interwiki'] = $role->hasGlobalPermission ('forge_admin') ;
	}
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

$GLOBALS['wgHooks']['UserLoadFromSession'][]='FusionForgeMWAuth';

$zeroperms = array ('read', 'writeapi', 'edit', 'move-subpages', 'move-rootuserpages', 'reupload-shared', 'createaccount');

foreach ($zeroperms as $i) {
	$wgGroupPermissions['user'][$i] = false;
	$wgGroupPermissions['*'][$i] = false;
}

SetupPermissionsFromRoles();

$wgFavicon = '/images/icon.png' ;
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

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

/* !isset($fusionforge_plugin_mediawiki_LocalSettings_included) */
}
