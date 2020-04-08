<?php
/*
 * Copyright (C) 2010 Roland Mas, Olaf Lenz
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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

use Tuleap\DB\DBConfig;
use Tuleap\Mediawiki\MediawikiExtensionDAO;
use Tuleap\Mediawiki\MediawikiMathExtensionEnabler;

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

    require_once __DIR__ . '/../../../src/common/include/Codendi_Request.class.php';
    require_once __DIR__ . '/../../../src/common/include/HTTPRequest.class.php';
    require_once __DIR__ . '/../../../src/www/include/pre.php';

/**
 * HACK
 */
    require_once MEDIAWIKI_BASE_DIR . '/../fusionforge/compat/load_compatibilities_method.php';

    $plugin_manager = PluginManager::instance();
    $mw_plugin = $plugin_manager->getPluginByName('mediawiki');
    \assert($mw_plugin instanceof mediawikiPlugin);
    if (! $mw_plugin || ! $plugin_manager->isPluginAvailable($mw_plugin)) {
        die('Mediawiki plugin not available');
    }

    define('MW_NO_SESSION', true);

    $manager                = $mw_plugin->getMediawikiManager();
    $GLOBALS['mediawiki_dao'] = $manager->getDao();
    $language_manager       = new MediawikiLanguageManager(new MediawikiLanguageDao());
    $project_name_retriever = new MediawikiFusionForgeProjectNameRetriever();
    $project_manager        = ProjectManager::instance();

    $forbidden_permissions = array(
    'editmyusercss',
    'editmyuserjs',
    'viewmyprivateinfo',
    'editmyprivateinfo'
    );

    $read_permissions = array(
    'read',
    'viewmywatchlist',
    'editmywatchlist'
    );

    $write_permissions = array(
    'edit',
    'createpage',
    'move',
    'createtalk',
    'writeapi'
    );

//Trust Mediawiki security
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();

    $wgServer = HTTPRequest::instance()->getServerUrl();

    if (! isset($fusionforgeproject)) {
        $fusionforgeproject = null;
    }

    $fusionforgeproject = $project_name_retriever->getFusionForgeProjectName($fusionforgeproject);

    $group = $project_manager->getProjectByUnixName($fusionforgeproject);

    $IP = '/usr/share/mediawiki-tuleap-123';

    $gconfig_dir = forge_get_config('mwdata_path', 'mediawiki');
    $project_dir = forge_get_config('projects_path', 'mediawiki') . "/"
    . $group->getID();
    if (! is_dir($project_dir)) {
        $project_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $group->getUnixName();
        if (! is_dir($project_dir)) {
            exit_error(sprintf(_('Mediawiki for project %s not created yet, please wait for a few minutes.'), Codendi_HTMLPurifier::instance()->purify($group->getPublicName()) . ' : ' . $project_dir));
        }
    }
    $path = array( $IP, "$IP/includes", "$IP/languages" );
    set_include_path(implode(PATH_SEPARATOR, $path) . PATH_SEPARATOR . get_include_path());

    require_once("$IP/includes/AutoLoader.php");
    require_once("$IP/includes/Defines.php");
    require_once("$IP/includes/DefaultSettings.php");

    if ($wgCommandLineMode) {
        if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
            die("This script must be run from the command line\n");
        }
    }

    $wgSitename         = $group->getPublicName() . " Wiki";
    $wgScriptPath       = "/plugins/mediawiki/wiki/$fusionforgeproject";
    $wgEmergencyContact = forge_get_config('admin_email');
    $wgPasswordSender   = forge_get_config('admin_email');
    $wgDBtype           = "forge";
    $wgDBserver         = forge_get_config('database_host');

    if (forge_get_config('mw_dbtype', 'mediawiki') == 'mysql') {
        // At the time writing schema in mysql is synonym for database
        $wgDBname = $manager->getWgDBname($group);
        if (! $wgDBname) {
            exit_error(sprintf(_('Mediawiki for project %s cannot be found, please contact your system admininistrators.'), $fusionforgeproject . ':' . $project_dir));
        }
        $wgDBprefix = $manager->getWgDBprefix($group);
    } else {
        $wgDBname      = forge_get_config('database_name');
        $wgDBmwschema  = str_replace('-', '_', "plugin_mediawiki_$fusionforgeproject");
        $wgDBts2schema = str_replace('-', '_', "plugin_mediawiki_$fusionforgeproject");
    }

    $wgDBuser           = forge_get_config('database_user');
    $wgDBpassword       = forge_get_config('database_password');
    $wgDBadminuser      = forge_get_config('database_user');
    $wgDBadminpassword  = forge_get_config('database_password');
    $wgDBport           = forge_get_config('database_port');
    if (DBConfig::isSSLEnabled()) {
        $wgDBssl = true;
    }
    $wgMainCacheType    = CACHE_NONE;
    $wgMemCachedServers = array();
    $wgEnableParserCache = false;

    $debug_project_ids = array();
    $debug_project_id = forge_get_config('mw_debug_project_id', 'mediawiki');
    if ($debug_project_id !== false) {
        $debug_project_ids = array_map(
            function ($arg) {
                return (int) trim($arg);
            },
            explode(',', $debug_project_id)
        );

        if (in_array($group->getID(), $debug_project_ids)) {
            ini_set('display_errors', 1);
            $wgShowSQLErrors = true;
            $wgDebugDumpSql = true;
            $wgShowDBErrorBacktrace = true;
            $wgDebugToolbar = true;
            if (forge_get_config('mw_debug_full', 'mediawiki') === true) {
                $wgShowDebug = true;
            }
        }
    }

//$wgEnableUploads = forge_get_config('enable_uploads', 'mediawiki');
    $wgEnableUploads             = true;
    $wgUploadDirectory           = "$project_dir/images";
    $wgUseImageMagick            = true;
    $wgImageMagickConvertCommand = "/usr/bin/convert";
    $wgLocalInterwiki            = $wgSitename;
    $wgShowExceptionDetails      = true;

    $user       = UserManager::instance()->getCurrentUser();
    $mw_service = $group->getService(MediaWikiPlugin::SERVICE_SHORTNAME);

    $used_language = $language_manager->getUsedLanguageForProject($group);
    if ($used_language) {
        $wgLanguageCode  = substr($used_language, 0, 2);
    } elseif ($mw_service && $mw_service->userIsAdmin($user)) {
        header('Location: /plugins/mediawiki/forge_admin.php?group_id=' . $group->getID() . '&pane=language&nolang=1');
        die();
    } else {
        $wgLanguageCode  = substr($user->getLocale(), 0, 2);
    }

    $wgHtml5          = false;
    $wgStyleDirectory = forge_get_config('codendi_dir') . forge_get_config('mw_style_path', 'mediawiki');
    $wgWellFormedXml  = true;
    $wgLogo           = "";

    $GLOBALS['sys_dbhost']         = forge_get_config('database_host');
    $GLOBALS['sys_dbport']         = forge_get_config('database_port');
    $GLOBALS['sys_dbname']         = forge_get_config('database_name');
    $GLOBALS['sys_dbuser']         = forge_get_config('database_user');
    $GLOBALS['sys_dbpasswd']       = forge_get_config('database_password');
    $GLOBALS['sys_plugins_path']   = forge_get_config('plugins_path');
    $GLOBALS['sys_urlprefix']      = forge_get_config('url_prefix');
    $GLOBALS['sys_use_ssl']        = (bool) ForgeConfig::get('sys_https_host');
    $GLOBALS['sys_default_domain'] = forge_get_config('web_host');
    $GLOBALS['sys_custom_path']    = forge_get_config('custom_path');
    $GLOBALS['gfwww']              = $gfwww;
    $GLOBALS['gfplugins']          = $gfplugins;
    $GLOBALS['sys_lang']           = forge_get_config('default_language');
    $GLOBALS['sys_urlroot']        = forge_get_config('url_root');
    $GLOBALS['sys_session_key']    = forge_get_config('session_key');
    $GLOBALS['sys_session_expire'] = forge_get_config('session_expire');
    $GLOBALS['REMOTE_ADDR']        = getStringFromServer('REMOTE_ADDR');
    $GLOBALS['HTTP_USER_AGENT']    = getStringFromServer('HTTP_USER_AGENT');

    require_once 'DatabaseForgeMysql123.php';

    function TuleapMediawikiAuthentication($user, &$result)
    {
        global $fusionforgeproject, $wgGroupPermissions;

        $user_manager = UserManager::instance();
        $tuleap_user  = $user_manager->getCurrentUser();

        if ($tuleap_user->isLoggedIn()) {
            $group          = group_get_object_by_name($fusionforgeproject);
            $madiawiki_name = ucfirst($tuleap_user->getUnixName());
            $mediawiki_user = User::newFromName($madiawiki_name);

            if ($mediawiki_user->getID() == 0) {
                    $mediawiki_user->addToDatabase();
                    $mediawiki_user->setPassword(User::randomPassword());
                    $mediawiki_user->setRealName($tuleap_user->getRealName());
                    $mediawiki_user->setToken();
                    $mediawiki_user->loadFromDatabase();
            }

            $user->mId = $mediawiki_user->getID();
            $user->loadFromId();
            $user = manageMediawikiGroupsForUser($user, $tuleap_user, $group);

            $user->saveSettings();
            wfSetupSession();
        } else {
            $user->logout();
        }

        $result = true;
        return true;
    }

/**
 * On every page load, the user's permissions are recalculated. They are based
 * upon the groups to which the user belongs.
 */
    function manageMediawikiGroupsForUser(User $mediawiki_user, PFUser $tuleap_user, Group $group)
    {
        $groups_mapper    = new MediawikiUserGroupsMapper($GLOBALS['mediawiki_dao'], new User_ForgeUserGroupPermissionsDao());
        $mediawiki_groups = $groups_mapper->defineUserMediawikiGroups($tuleap_user, $group);

        foreach ($mediawiki_groups['removed'] as $group_to_remove) {
            $mediawiki_user->removeGroup($group_to_remove);
        }

        foreach ($mediawiki_groups['added'] as $group_to_add) {
            $mediawiki_user->addGroup($group_to_add);
        }

        return $mediawiki_user;
    }

    function customizeMediawikiGroupsRights(
        array $wgGroupPermissions,
        MediawikiManager $manager,
        $fusionforgeproject,
        array $forbidden_permissions,
        array $read_permissions,
        array $write_permissions
    ) {
        $user_manager = UserManager::instance();
        $tuleap_user  = $user_manager->getCurrentUser();

        $wgGroupPermissions = removeUnwantedRights($wgGroupPermissions, $forbidden_permissions);
        $wgGroupPermissions = removeAllGroupsReadWriteRights($wgGroupPermissions, $read_permissions, $write_permissions);
        $wgGroupPermissions = addReadPermissionForUser(
            $tuleap_user,
            $manager,
            $fusionforgeproject,
            $wgGroupPermissions,
            $read_permissions
        );
        $wgGroupPermissions = addWritePermissionForUser(
            $tuleap_user,
            $manager,
            $fusionforgeproject,
            $wgGroupPermissions,
            $write_permissions
        );

        return $wgGroupPermissions;
    }

    function addReadPermissionForUser(PFUser $tuleap_user, MediawikiManager $manager, $fusionforgeproject, array $wgGroupPermissions, array $read_permissions)
    {
        $group = group_get_object_by_name($fusionforgeproject);

        if (! $manager->userCanRead($tuleap_user, $group)) {
            return $wgGroupPermissions;
        }

        foreach ($read_permissions as $read_permission) {
            $wgGroupPermissions['*'][$read_permission] = true;
        }

        return $wgGroupPermissions;
    }

    function addWritePermissionForUser(PFUser $tuleap_user, MediawikiManager $manager, $fusionforgeproject, array $wgGroupPermissions, array $write_permissions)
    {
        $group = group_get_object_by_name($fusionforgeproject);

        if (! $manager->userCanWrite($tuleap_user, $group)) {
            return $wgGroupPermissions;
        }

        foreach ($write_permissions as $write_permission) {
            $wgGroupPermissions['*'][$write_permission] = true;
        }

        return $wgGroupPermissions;
    }

    function removeAllGroupsReadWriteRights(array $wgGroupPermissions, array $read_permissions, array $write_permissions)
    {
        $permissions = array_merge($read_permissions, $write_permissions);

        foreach ($permissions as $permission) {
            $wgGroupPermissions['*'][$permission]          = false;
            $wgGroupPermissions['user'][$permission]       = false;
            $wgGroupPermissions['bot'][$permission]        = false;
            $wgGroupPermissions['bureaucrat'][$permission] = false;
            $wgGroupPermissions['sysop'][$permission]      = false;
        }

        return $wgGroupPermissions;
    }

    function removeUnwantedRights(array $wgGroupPermissions, array $forbidden_permissions)
    {
        $wgGroupPermissions['bureaucrat']['userrights'] = false;
        $wgGroupPermissions['*']['createaccount']       = false;

        foreach ($forbidden_permissions as $forbidden_permission) {
            $wgGroupPermissions['*'][$forbidden_permission]          = false;
            $wgGroupPermissions['user'][$forbidden_permission]       = false;
            $wgGroupPermissions['bot'][$forbidden_permission]        = false;
            $wgGroupPermissions['bureaucrat'][$forbidden_permission] = false;
            $wgGroupPermissions['sysop'][$forbidden_permission]      = false;
        }

        return $wgGroupPermissions;
    }

    function NoLinkOnMainPage(&$personal_urls)
    {
        unset($personal_urls['anonlogin']);
        unset($personal_urls['anontalk']);
        unset($personal_urls['logout']);
        unset($personal_urls['login']);
        return true;
    }

    $wgHooks['PersonalUrls'][] = 'NoLinkOnMainPage';

    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        class SpecialForgeRedir extends SpecialPage
        {
            public $dstappendself = false;

            public function getTitle($subpage = "")
            {
                  return 'SpecialForgeRedir';
            }

            public function getRedirect($subpage = "")
            {
                  return $this;
            }

            public function getRedirectQuery()
            {
                  return $this;
            }

            public function getFullUrl()
            {
                  $u = $this->dst;
                if ($this->dstappendself) {
                    $u .= urlencode(getStringFromServer('REQUEST_URI'));
                }
                return util_make_url($u);
            }
        }

        class SpecialForgeRedirLogin extends SpecialForgeRedir
        {
            public $dstappendself = true;
            public $dst = '/account/login.php?return_to=';
        }

        class SpecialForgeRedirCreateAccount extends SpecialForgeRedir
        {
            public $dst = '/account/register.php';
        }

        class SpecialForgeRedirResetPass extends SpecialForgeRedir
        {
            public $dst = '/account/lostpw.php';
        }

        function DisableLogInOut(&$mList)
        {
            $mList['Userlogin'] = 'SpecialForgeRedirLogin';
            $mList['CreateAccount'] = 'SpecialForgeRedirCreateAccount';
            $mList['Resetpass'] = 'SpecialForgeRedirResetPass';
               unset($mList['Userlogout']);
            return true;
        }
        $GLOBALS['wgHooks']['SpecialPage_initList'][] = 'DisableLogInOut';
    }

    $GLOBALS['wgHooks']['UserLoadFromSession'][] = 'TuleapMediawikiAuthentication';

    $wgGroupPermissions = customizeMediawikiGroupsRights(
        $wgGroupPermissions,
        $manager,
        $fusionforgeproject,
        $forbidden_permissions,
        $read_permissions,
        $write_permissions
    );

    $wgFavicon     = '/images/icon.png';
    $wgBreakFrames = false;

    if (forge_get_config('unbreak_frames', 'mediawiki')) {
        $wgEditPageFrameOptions = false;
    }

    ini_set('memory_limit', '100M');

// LOAD THE SITE-WIDE AND PROJECT-SPECIFIC EXTRA-SETTINGS
    if (is_file(forge_get_config('config_path') . "/plugins/mediawiki/LocalSettings.php")) {
        include(forge_get_config('config_path') . "/plugins/mediawiki/LocalSettings.php");
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
        include("$gconfig_dir/ForgeSettings.php");
    }
// project specific settings
    if (is_file("$project_dir/ProjectSettings.php")) {
        include("$project_dir/ProjectSettings.php");
    }
}

// Add Tuleap Skin
$wgDefaultSkin    = 'tuleap123';
$wgAutoloadClasses['Tuleap123'] = __DIR__ . "/skins/Tuleap123/Tuleap123.php";
$wgValidSkinNames['tuleap123'] = 'Tuleap123';
require_once $wgAutoloadClasses['Tuleap123'];

// ParserFunctions Extension inclusion
require_once("$IP/extensions/ParserFunctions/ParserFunctions.php");
$wgPFEnableStringFunctions = true;

// SyntaxHighlight_GeSHi Extension inclusion
require_once "$IP/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php";

// PdfBook Extension inclusion
require_once("$IP/extensions/PdfBook/PdfBook.php");
$wgPdfBookTab = true;

// Labeled Section Transclusion
require_once("$IP/extensions/LabeledSectionTransclusion/lst.php");
require_once("$IP/extensions/LabeledSectionTransclusion/lsth.php");
// CategoryTree
$wgUseAjax = true;
require_once("$IP/extensions/CategoryTree/CategoryTree.php");

// Cite
require_once "$IP/extensions/Cite/Cite.php";

// ImageMap
require_once "$IP/extensions/ImageMap/ImageMap.php";

// InputBox
require_once "$IP/extensions/InputBox/InputBox.php";

// UNC_links
$wgUrlProtocols = array(
    'http://',
    'https://',
    'ftp://',
    'ftps://', // If we allow ftp:// we should allow the secure version.
    'ssh://',
    'sftp://', // SFTP > FTP
    'irc://',
    'ircs://', // @bug 28503
    'xmpp:', // Another open communication protocol
    'sip:',
    'sips:',
    'gopher://',
    'telnet://', // Well if we're going to support the above.. -ævar
    'nntp://', // @bug 3808 RFC 1738
    'worldwind://',
    'mailto:',
    'tel:', // If we can make emails linkable, why not phone numbers?
    'sms:', // Likewise this is standardized too
    'news:',
    'svn://',
    'git://',
    'mms://',
    'bitcoin:', // Even registerProtocolHandler whitelists this along with mailto:
    'magnet:', // No reason to reject torrents over magnet: when they're allowed over http://
    'urn:', // Allow URNs to be used in Microdata/RDFa <link ... href="urn:...">s
    'geo:', // urls define geo locations, they're useful in Microdata/RDFa and for coordinates
    '//', // for protocol-relative URLs
);

if ($manager->isCompatibilityViewEnabled($group)) {
    // WikiEditor Extension inclusion
    require_once("$IP/extensions/WikiEditor/WikiEditor.php");

    // Enables use of WikiEditor by default but still allow users to disable it in preferences
    $wgDefaultUserOptions['usebetatoolbar'] = 1;
    $wgDefaultUserOptions['usebetatoolbar-cgd'] = 1;

    // Displays the Preview and Changes tabs
    $wgDefaultUserOptions['wikieditor-preview'] = 1;

    // Displays the Publish and Cancel buttons on the top right side
    $wgDefaultUserOptions['wikieditor-publish'] = 1;
}

// TuleapArtLinks Extension inclusion
require_once dirname(__FILE__) . '/../extensions/TuleapArtLinks/TuleapArtLinks.php';
$wgTuleapArtLinksGroupId = $group->getGroupId();

$mleb_manager_loader = new MediawikiMLEBExtensionManagerLoader();
$mleb_manager        = $mleb_manager_loader->getMediawikiMLEBExtensionManager();
if ($mleb_manager->isMLEBExtensionInstalled()) {
    if ($mleb_manager->isMLEBExtensionAvailableForProject($group) || (isset($IS_RUNNING_UPDATE) && $IS_RUNNING_UPDATE)) {
        $mleb_path = forge_get_config('extension_mleb_path', 'mediawiki');

        // Babelww
        require_once $mleb_path . "/extensions/Babel/Babel.php";

        // CLDR
        require_once $mleb_path . "/extensions/cldr/cldr.php";

        // CleanChanges
        require_once $mleb_path . "/extensions/CleanChanges/CleanChanges.php";
        $wgCCTrailerFilter                = true;
        $wgCCUserFilter                   = false;
        $wgDefaultUserOptions['usenewrc'] = 1;

        // LocalisationUpdate
        require_once $mleb_path . "/extensions/LocalisationUpdate/LocalisationUpdate.php";
        $wgLocalisationUpdateDirectory = $mleb_path . "/cache";

        // Translate
        require_once $mleb_path . "/extensions/Translate/Translate.php";
        $wgGroupPermissions['user']['translate']               = true;
        $wgGroupPermissions['user']['translate-messagereview'] = true;
        $wgGroupPermissions['user']['translate-groupreview']   = true;
        $wgGroupPermissions['user']['translate-import']        = true;
        $wgGroupPermissions['sysop']['pagetranslation']        = true;
        $wgGroupPermissions['sysop']['translate-manage']       = true;
        $wgExtraLanguageNames['qqq']                           = 'Message documentation'; // No linguistic content. Used for documenting messages

        require_once $mleb_path . "/extensions/UniversalLanguageSelector/UniversalLanguageSelector.php";
        $GLOBALS['wgTranslatePageTranslationULS'] = true;
    }
}

$mediawiki_math_extension_enabler = new MediawikiMathExtensionEnabler(
    new MediawikiExtensionDAO(),
    new Mediawiki_Migration_MediawikiMigrator()
);
if ($mediawiki_math_extension_enabler->canPluginBeLoaded($IP, (isset($IS_RUNNING_UPDATE) && $IS_RUNNING_UPDATE), $group)) {
    /*
     * SHELL_MAX_ARG_STRLEN should be defined in $IP/includes/Defines.php but for some reasons
     * it is not the case in our installation. Let's just set it to the default value.
     */
    define('SHELL_MAX_ARG_STRLEN', 100000);
    $wgDefaultUserOptions['math'] = 'png';
    $wgMathValidModes             = ['png', 'source'];
    $wgMathDisableTexFilter       = 'never';
    $wgMathFullRestbaseURL        = false;

    require_once "$IP/extensions/Math/Math.php";
}
