<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\CookieManager;
use Tuleap\Event\Events\HitEvent;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Plugin\PluginLoader;
use Tuleap\Request\CurrentPage;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\TimezoneRetriever;

if (PHP_VERSION_ID < 70300) {
    die('Tuleap must be run on a PHP 7.3 (or greater) engine.');
}

require_once __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set(TimezoneRetriever::getServerTimezone());

// Defines all of the settings first (hosts, databases, etc.)
$locar_inc_finder = new Config_LocalIncFinder();
$local_inc = $locar_inc_finder->getLocalIncPath();
require($local_inc);
require($GLOBALS['db_config_file']);
ForgeConfig::loadFromFile($GLOBALS['codendi_dir'] . '/src/etc/local.inc.dist'); //load the default settings
ForgeConfig::loadFromFile($local_inc);
ForgeConfig::loadFromFile($GLOBALS['db_config_file']);
if (isset($GLOBALS['DEBUG_MODE'])) {
    ForgeConfig::loadFromFile($GLOBALS['codendi_dir'] . '/src/etc/development.inc.dist');
    ForgeConfig::loadFromFile(dirname($local_inc) . '/development.inc');
}
ForgeConfig::loadFromDatabase();
ForgeConfig::loadFromFile(ForgeConfig::get('redis_config_file'));

bindtextdomain('tuleap-core', ForgeConfig::get('sys_incdir'));
textdomain('tuleap-core');

$xml_security = new XML_Security();
$xml_security->disableExternalLoadOfEntities();

// Detect whether this file is called by a script running in cli mode, or in normal web mode
if (!defined('IS_SCRIPT')) {
    if (php_sapi_name() == "cli") {
        // Backend scripts should never ends because of lack of time or memory
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        define('IS_SCRIPT', true);
    } else {
        define('IS_SCRIPT', false);
    }
}

//{{{ Sanitize $_REQUEST : remove cookies
while (count($_REQUEST)) {
    array_pop($_REQUEST);
}

if (!ini_get('variables_order')) {
        $_REQUEST = array_merge($_GET, $_POST);
} else {
    $g_pos = strpos(strtolower(ini_get('variables_order')), 'g');
    $p_pos = strpos(strtolower(ini_get('variables_order')), 'p');
    if ($g_pos === false) {
        if ($p_pos !== false) {
            $_REQUEST = $_POST;
        }
    } else {
        if ($p_pos === false) {
            $_REQUEST = $_GET;
        } else {
            if ($g_pos < $p_pos) {
                $_REQUEST = array_merge($_GET, $_POST);
            } else {
                $_REQUEST = array_merge($_POST, $_GET);
            }
        }
    }
}

//Cast group_id as int.
foreach (
    array(
        'group_id',
        'atid',
        'pv',
    ) as $variable
) {
    if (isset($_REQUEST[$variable])) {
        $$variable = $_REQUEST[$variable] = $_GET[$variable] = $_POST[$variable] = (int) $_REQUEST[$variable];
    }
}
//}}}

//{{{ define undefined variables
if (!isset($GLOBALS['feedback'])) {
    $GLOBALS['feedback'] = "";  //By default the feedbak is empty
}

// Create cache directory if needed
if (! file_exists(ForgeConfig::get('codendi_cache_dir'))) {
    $site_cache = new SiteCache();
    $site_cache->restoreRootCacheDirectory();
}

// Instantiate System Event listener
$system_event_manager = SystemEventManager::instance();

//Load plugins
$event_manager  = EventManager::instance();
$plugin_manager = PluginManager::instance();
$plugin_loader  = new PluginLoader(
    $event_manager,
    PluginFactory::instance(),
    BackendLogger::getDefaultLogger()
);
$cookie_manager = new CookieManager();

$loader_scheduler = new LoaderScheduler($cookie_manager, $plugin_loader);
$loader_scheduler->loadPluginsThenStartSession(IS_SCRIPT);

if (!IS_SCRIPT) {
    header('X-UA-Compatible: IE=Edge');
    header('Referrer-Policy: no-referrer-when-downgrade, strict-origin, same-origin');

    // Protection against clickjacking
    header('X-Frame-Options: DENY');
    $csp_rules = "frame-ancestors 'self'; ";

    // XSS prevention
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    $whitelist_scripts = array();
    $event_manager->processEvent(
        Event::CONTENT_SECURITY_POLICY_SCRIPT_WHITELIST,
        array(
            'whitelist_scripts' => &$whitelist_scripts
        )
    );
    $csp_whitelist_script_scr  = implode(' ', $whitelist_scripts);
    $csp_whitelist_script_scr .= ' ' . ForgeConfig::get('sys_csp_script_scr_whitelist');
    $csp_rules                .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' $csp_whitelist_script_scr; ";

    header('Content-Security-Policy: ' . $csp_rules);
}

$feedback = ''; // Initialize global var

$request = HTTPRequest::instance();
$request->setTrustedProxies(array_map('trim', explode(',', ForgeConfig::get('sys_trusted_proxies'))));

//Language
if (!$GLOBALS['sys_lang']) {
    $GLOBALS['sys_lang'] = "en_US";
}
$Language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);

$user_manager = UserManager::instance();
$current_user = $user_manager->getCurrentUser();

(static function () use ($current_user) {
    (new \Tuleap\Language\LocaleSwitcher())->setLocale($current_user->getLocale());
})();

$hit_event = new HitEvent($request, IS_SCRIPT);
$event_manager->processEvent($hit_event);

/*

    Timezone must come after we have warn plugins of the hit to prevent messups


*/
date_default_timezone_set(TimezoneRetriever::getUserTimezone($current_user));

if (! defined('FRONT_ROUTER')) {
    $theme_manager = new ThemeManager(
        new BurningParrotCompatiblePageDetector(
            new CurrentPage(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            )
        )
    );
    $HTML = $theme_manager->getTheme($current_user);
}

// Check if anonymous user is allowed to browse the site
// Bypass the test for:
// a) all scripts where you are not logged in by definition
// b) if it is a local access from localhost

// Check URL for valid hostname and valid protocol

if (!IS_SCRIPT) {
    if (! defined('FRONT_ROUTER')) {
        $urlVerifFactory = new URLVerificationFactory($event_manager);
        $global_server   = $_SERVER ?? [];
        $urlVerif = $urlVerifFactory->getURLVerification($global_server);
        $urlVerif->assertValidUrl($global_server, $request);

        (new RequestInstrumentation(Prometheus::instance()))->incrementLegacy();
    }

    if (! $current_user->isAnonymous()) {
        header('X-Tuleap-Username: ' . $current_user->getUserName());
    }
}

//Check post max size
if ($request->exist('postExpected') && !$request->exist('postReceived')) {
    $e = 'You tried to upload a file that is larger than the Codendi post_max_size setting.';
    exit_error('Error', $e);
}
if (ForgeConfig::get('DEBUG_MODE')) {
    $GLOBALS['DEBUG_TIME_IN_PRE'] = microtime(1) - $GLOBALS['debug_time_start'];
}

if ($request->isAjax()) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
}
