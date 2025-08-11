<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

/**
 * @psalm-immutable
 */
final readonly class ConfigurationVariablesLocalIncDist
{
    #[ConfigKey('Configuration directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/tuleap')]
    public const CONFIGURATION_DIR = 'sys_custom_dir';

    #[ConfigKey('Binaries directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/usr/lib/tuleap/bin')]
    public const BIN_DIR = 'codendi_bin_prefix';

    #[ConfigKey('Database configuration file')]
    #[ConfigKeyString('/etc/tuleap/conf/database.inc')]
    #[ConfigCannotBeModifiedYet]
    public const DB_CONFIG_FILE = 'db_config_file';

    #[ConfigKey('Redis configuration file')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/tuleap/conf/redis.inc')]
    public const REDIS_CONFIG_FILE = 'redis_config_file';

    #[ConfigKey('Data Directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap')]
    public const SYS_DATA_DIR = 'sys_data_dir';

    #[ConfigKey('Place to put temporary data: deleted user\'s accounts, etc')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/tmp')]
    public const TMP_DIR = 'tmp_dir';

    #[ConfigKey('Where Legacy PhpWiki attachments are stored')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/wiki')]
    public const PHPWIKI_ATTACHMENT_DATA_DIR = 'sys_wiki_attachment_data_dir';

    #[ConfigKey('Path to application entry point (index.php)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/usr/share/tuleap/src/www/')]
    public const TULEAP_SOURCES_INDEX_DIR = 'sys_urlroot';

    #[ConfigKey('Directory which hosts the site content)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/usr/share/tuleap/site-content')]
    public const DEFAULT_SITE_CONTENT = 'sys_incdir';

    #[ConfigKey('Directory which hosts the customized site content')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/tuleap/content')]
    public const CUSTOM_SITE_CONTENT = 'sys_custom_incdir';

    #[ConfigKey('Cache directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('This directory must be world reachable, but writable only by the web-server')]
    #[ConfigKeyString('/var/tmp/tuleap_cache')]
    public const CACHE_DIR = 'codendi_cache_dir';

    #[ConfigKey('Legacy Subversion: data path')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/svnroot')]
    public const LEGACY_SVN_DIR = 'svn_prefix';

    #[ConfigKey('Legacy Subversion: SVN Admin binary')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/usr/bin/svnadmin')]
    public const LEGACY_SVNADMIN_CMD = 'svnadmin_cmd';

    #[ConfigKey('File containing SVN repository definitions for Apache')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/httpd/conf.d/tuleap-svnroot.conf')]
    public const SVN_ROOT_FILE = 'svn_root_file';

    #[ConfigKey('Sub directory where apache logs files for SVN should go to')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('logs/svn_log')]
    public const SVN_LOG_SUBPATH = 'svn_log_path';

    #[ConfigKey('FRS: where the released files are located')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/ftp/tuleap')]
    public const FRS_FTP_DIR = 'ftp_frs_dir_prefix';

    #[ConfigKey('FRS: where files are placed when uploaded')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/ftp/incoming')]
    public const FRS_FTP_INCOMING_DIR = 'ftp_incoming_dir';

    #[ConfigKey('Prefix to add to the anon ftp project homedir')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/ftp/pub')]
    public const FTP_ANON_DIR_PREFIX = 'ftp_anon_dir_prefix';

    #[ConfigKey('Log dir')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/log/tuleap')]
    public const LOG_DIR = 'codendi_log';

    #[ConfigKey('Mail aliases file')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/aliases.codendi')]
    public const MAIL_ALIASES_FILE = 'alias_file';

    #[ConfigKey('Plugins directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/usr/share/tuleap/plugins/')]
    public const PLUGINS_DIR = 'sys_pluginsroot';

    #[ConfigKey('Plugins configuration directory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/etc/tuleap/plugins/')]
    public const PLUGINS_CONFIGURATION_DIR = 'sys_custompluginsroot';

    #[ConfigKey('Plugins path')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/plugins')]
    public const PLUGINS_PATH = 'sys_pluginspath';

    #[ConfigKey('Custom plugins path')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/customplugins')]
    public const CUSTOM_PLUGINS_PATH = 'sys_custompluginspath';

    #[ConfigKey('Comma separated paths where Tuleap can find plugins')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString]
    public const EXTRA_PLUGIN_PATH = 'sys_extra_plugin_path';

    #[ConfigKey('Unix user running the HTTP server')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('codendiadm')]
    public const HTTP_USER = 'sys_http_user';

    #[ConfigKey('Project id corresponding to Tuleap News group')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyInt(46)]
    public const NEWS_GROUP = 'sys_news_group';

    #[ConfigKey('Make project categorisation mandatory')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('If a project has not categorized itself, it will result in a warning in project dashboard page.')]
    #[ConfigKeyLegacyBool(true)]
    public const PROJECT_CATEGORIES_MANDATORY = 'sys_trove_cat_mandatory';

    #[ConfigKey('Linefeed characters: \n for Linux/Unix')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString("\n")]
    public const LINE_FEED_CHAR = 'sys_lf';

    #[ConfigKey('Authentication scheme')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('Should be either \'ldap\' or \'codendi\'')]
    #[ConfigKeyString('codendi')]
    public const AUTH_TYPE = 'sys_auth_type';

    #[ConfigKey('Supported languages (comma separated)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('Following languages are supported: en_US, fr_FR, pt_BR (experimental), ko_KR (experimental)')]
    #[ConfigKeyString('en_US,fr_FR,pt_BR,ko_KR')]
    public const SUPPORTED_LANGUAGES = 'sys_supported_languages';

    #[ConfigKey('Default language')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('en_US')]
    public const DEFAULT_LANG = 'sys_lang';

    #[ConfigKey('Is license approval mandatory when downloading a file from the FRS ?')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyLegacyBool(false)]
    public const FRS_LICENSE_MANDATORY = 'sys_frs_license_mandatory';

    #[ConfigKey('If sys_frs_license_mandatory is 1, then you must give the Exchange policy URL which will be used to generate links on the download confirmation popup.')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('')]
    public const EXCHANGE_POLICY_URL = 'sys_exchange_policy_url';

    #[ConfigKey('Maximum duration of a session (in seconds)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('Default value is about 2 weeks: 3600*24*14')]
    #[ConfigKeyInt(3600 * 24 * 14)]
    public const SESSION_LIFETIME = 'sys_session_lifetime';

    #[ConfigKey('Delay (in seconds) between 2 updates of "last access" date for a user')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Default is 1 hour, it's good enough if you want to know the day a user was seen.
    A number too low will degrade performances.
    EOT)]
    #[ConfigKeyInt(3600)]
    public const LAST_ACCESS_RESOLUTION = 'last_access_resolution';

    #[ConfigKey('Duration before deleting pending accounts which have not been activated (in days)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyInt(60)]
    public const PENDING_ACCOUNT_LIFETIME = 'sys_pending_account_lifetime';

    #[ConfigKey('Default password duration')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    User will be asked to change its password after sys_password_lifetime' days
    0 = no duration
    EOT)]
    #[ConfigKeyInt(0)]
    public const PASSWORD_LIFETIME = 'password_lifetime';

    #[ConfigKey('Suspend user account after a given period of inactivity (in days)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp('0 = no inactivity checking')]
    #[ConfigKeyInt(0)]
    public const SUSPEND_INACTIVE_ACCOUNTS_DELAY = 'sys_suspend_inactive_accounts_delay';

    #[ConfigKey('Suspend user account if they have to been member of any project (in days)')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    0 = never deactivate "not project members" account
    EOT)]
    #[ConfigKeyInt(0)]
    public const SUSPEND_NON_PROJECT_MEMBER_DELAY = 'sys_suspend_non_project_member_delay';

    #[ConfigKey('Delay between the date when a doc is deleted by a user and the date the corresponding file is erased on the filesystem')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Useful for backup and to restore files that where deleted by mistake.
    EOT)]
    #[ConfigKeyInt(3)]
    public const FILE_DELETION_DELAY = 'sys_file_deletion_delay';

    #[ConfigKey('Max upload size for uploaded files in bytes')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Make sure that PHP settings (in /etc/opt/remi/phpXX/php-fpm.d/tuleap.conf where XX corresponds to PHP version, for example “82”):
    * post_max_size
    * upload_max_filesize
    and nginx configuration file (client_max_body_size in /etc/nginx/conf.d/tuleap.conf) are also set to allow this size.
    EOT)]
    #[ConfigKeyInt(64 * 1024 * 1024)]
    public const MAX_SIZE_UPLOAD = 'sys_max_size_upload';

    #[ConfigKey('Enable project categorization')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyLegacyBool(true)]
    public const USE_TROVE = 'sys_use_trove';

    #[ConfigKey('User\'ss avatar path')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/lib/tuleap/user/avatar/')]
    public const AVATAR_PATH = 'sys_avatar_path';

    #[ConfigKey('Displays to end users the privacy of the project')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    If enabled (1), a 'lock' icon will be displayed plus the mention public|private.
    If disabled (0), only the project name is displayed
    EOT)]
    #[ConfigKeyLegacyBool(true)]
    public const DISPLAY_PROJECT_PRIVACY_IN_SERVICE_BAR = 'sys_display_project_privacy_in_service_bar';

    #[ConfigKey('Hard limit to number of emailed people when the news admin choose to send a news by email')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Only used in news service for now
    EOT)]
    #[ConfigKeyInt(100)]
    public const MAX_NUMBER_OF_EMAILED_PEOPLE = 'sys_max_number_of_emailed_people';

    #[ConfigKey('Allow (or not) users to do a SVN commit without any commit message')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Set to 0 to force commit message to not be empty
    EOT)]
    #[ConfigKeyLegacyBool(true)]
    public const ALLOW_EMPTY_SVN_COMMIT_MESSAGE = 'sys_allow_empty_svn_commit_message';

    #[ConfigKey('Set the reporting level for logging')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    Possible levels:
    - debug
    - info
    - warning
    - error
    EOT)]
    #[ConfigKeyString('warning')]
    public const LOGGER_LEVEL = 'sys_logger_level';

    #[ConfigKey('Define the email domain for email gateway feature')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    By default, email domain = default Tuleap domain (see sys_default_domain)
    EOT)]
    #[ConfigKeyString('')]
    public const DEFAULT_MAIL_DOMAIN = 'sys_default_mail_domain';

    #[ConfigKey('Backup path for deleted projects')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('/var/tmp')]
    public const PROJECT_BACKUP_PATH = 'sys_project_backup_path';

    #[ConfigKey('Whitelist URLs for the Content-Security-Policy directive script-src')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
    This could be needed if JavaScript code needs to be executed from an external website
    For example, this could be 'https://example.com http://tuleap.net' or 'https://example.com/script.js'
    EOT)]
    #[ConfigKeyString('')]
    public const CSP_SCRIPT_SCR_WHITELIST = 'sys_csp_script_scr_whitelist';

    #[ConfigKey('Comma separated list of IP addresses that are trusted reverse proxy')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyHelp(<<<EOT
     When you setup a reverse proxy in front of Tuleap (for SSL termination or
     load balancer for instance, you should set there the IP address of the proxy

     /!\ SECURITY WARNING /!\

     When enabled, Tuleap will trust following HTTP headers:
     - X_FORWARDED_FOR
     - X_FORWARDED_PROTO
     - REMOTE_ADDR
     You have to ensure those headers are properly set by your reverse proxy
     otherwise it might be an injection point for an attacker
    EOT)]
    #[ConfigKeyString('')]
    public const TRUSTED_PROXIES = 'sys_trusted_proxies';
}
