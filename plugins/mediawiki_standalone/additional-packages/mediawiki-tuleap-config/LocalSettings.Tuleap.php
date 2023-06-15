<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

// Prevent password and realname change
$GLOBALS['wgGroupPermissions']['user']['editmyprivateinfo'] = false;
$GLOBALS['wgPasswordResetRoutes']                           = ['username' => false, 'email' => false];
// Block the createaccount page, users are expected to use their Tuleap accounts
// Reference: https://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_account_creation
$GLOBALS['wgGroupPermissions']['*']['createaccount']     = false;
$GLOBALS['wgGroupPermissions']['sysop']['createaccount'] = false;
// Disable patrolling
$GLOBALS['wgGroupPermissions']['sysop']['autopatrol'] = false;
$GLOBALS['wgGroupPermissions']['sysop']['patrol']     = false;
// Make sure people can always access this page, so that OAuth2 flow can kick-off
$GLOBALS['wgWhitelistRead'][] = 'Special:TuleapLogin';
// As far as this plugin is concerned, there is no difference between an "*" group user (=anon)
// and logged in "user" without any additional groups. Whether user will be anon or logged in
// depends on private/public setting in Tuleap project. In both cases, "read" access is controlled
// by the "is_reader" attribute send from Tuleap, which is handled in a hook, within
// "Extension:TuleapIntegration".
// However, in normal MediaWiki, users with no additional groups behave same as "editors" in Tuleap
// Therefore, we assign all default "user" permissions to our new "editor" group, and assign anon
// permissions to "user".
$GLOBALS['wgGroupPermissions']['*']['edit'] = false;
$GLOBALS['wgGroupPermissions']['editor']    = $GLOBALS['wgGroupPermissions']['user'];
$GLOBALS['wgGroupPermissions']['user']      = $GLOBALS['wgGroupPermissions']['*'];

// Disable email features
$GLOBALS['wgEnableEmail']         = false;
$GLOBALS['wgEnableUserEmail']     = false;
$GLOBALS['wgEmailAuthentication'] = false;

// Third Party Extensions - START ###
// ERM27085 - Extensions that were enabled in MediaWiki 1.23
wfLoadExtensions([
    'CategoryTree',
    'Cite',
    'ImageMap',
    'InputBox',
    'LabeledSectionTransclusion',
    'ParserFunctions',
    'SyntaxHighlight_GeSHi',
    'WikiEditor',
    'Mpdf',
]);
$GLOBALS['wgPFEnableStringFunctions'] = true;
$GLOBALS['wgMpdfSimpleOutput']        = true;
$GLOBALS['wgMpdfTab']                 = true;

$GLOBALS['wgDefaultUserOptions']['usebetatoolbar']     = 1;
$GLOBALS['wgDefaultUserOptions']['usebetatoolbar-cgd'] = 1;
$GLOBALS['wgDefaultUserOptions']['wikieditor-preview'] = 1;
$GLOBALS['wgDefaultUserOptions']['wikieditor-publish'] = 1;

// Additional extensions
wfLoadExtensions([
    'CodeEditor',
    'Gadgets',
    'MultimediaViewer',
    'PageImages',
    'PdfHandler',
    'ReplaceText',
    'Scribunto',
    'TemplateData',
    'TextExtracts',
    'VisualEditor',
]);
// Third Party Extensions - END ###

wfLoadExtension('Math');
$GLOBALS['wgMathValidModes']             = ['mathml'];
$GLOBALS['wgDefaultUserOptions']['math'] = 'mathml';
$GLOBALS['wgMaxShellMemory']             = 1228800;
$GLOBALS['wgHiddenPrefs'][]              = 'math';
$GLOBALS['wgMathoidCli']                 = [
    '/usr/lib/tuleap/mathoid/bin/mathoid-cli',
    '--config',
    '/usr/share/tuleap-mathoid/config.yaml',
];

// MediaWiki Core default settings - START ###
$GLOBALS['wgUrlProtocols'][] = 'file://';
$GLOBALS['wgUrlProtocols'][] = 'redis://'; // From old MediaWiki 1.23

$GLOBALS['wgEnableUploads']  = true;
$GLOBALS['wgFileExtensions'] = ['png', 'gif', 'jpg', 'jpeg'];

$GLOBALS['wgCookieSameSite'] = 'Lax';
$GLOBALS['wgCookiePrefix']   = '__Host-tuleap-' . $GLOBALS['wgDBname'];

$GLOBALS['wgCSPHeader'] = true;

// MediaWiki Core default settings - END ###

// Tuleap Specific - START ###
$GLOBALS['wgTuleapEnableLocalLogin'] = false;
wfLoadExtension('TuleapIntegration');
$GLOBALS['wgTuleapOAuth2Config']['redirectUri']
    // "_oauth" is a virtual instance served by `Extension:TuleapWikifarm`
    = $GLOBALS['wgServer'] . '/mediawiki/_oauth/Special:TuleapLogin/callback';

wfLoadSkin('TuleapSkin');

$GLOBALS['wgSkipSkins'] = ['timeless', 'monobook', 'vector'];

$GLOBALS['wgHiddenPrefs'][] = 'variant';
$GLOBALS['wgHiddenPrefs'][] = 'noconvertlink';

// This is needed to prevent MW from doing "can user read" checked.
// We allow all to read, and then integration will block access if needed.
// Follow-up preset removal https://tuleap.net/plugins/tracker/?aid=27186
$GLOBALS['wgGroupPermissions']['*']['read'] = true;

$GLOBALS['wgDebugLogGroups'] = [
    'exception' => '/var/log/tuleap/mediawiki_log',
    'error' => '/var/log/tuleap/mediawiki_log',
    'fatal' => '/var/log/tuleap/mediawiki_log',
    'TuleapFarm' => '/var/log/tuleap/mediawiki_log',
];

// Disable parser cache
$GLOBALS['wgParserCacheType'] = CACHE_NONE;

// Make sure sessions are stored in DB
$GLOBALS['wgSessionCacheType'] = CACHE_DB;

// Tuleap Specific - END ###
