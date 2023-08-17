<?php
$db_config_file = dirname(__FILE__) . "/database.inc";
$GLOBALS['db_config_file'] = $db_config_file;
$sys_default_domain = "%sys_default_domain%";


// Plugins related paths
$sys_pluginsroot       =  "/usr/share/tuleap/tests/integration/_fixtures/";
$sys_custompluginsroot = "/usr/share/tuleap/tests/integration/_fixtures/";
$sys_pluginspath       = "/plugins";
$sys_custompluginspath = "/customplugins";

//This is used for cookie authentication. If you have distributed servers,
//please use same cookie prefix for a "cluster"
$sys_cookie_prefix = "TULEAP";


//
//
// PART II - Paths and system settings
//
//

// In order to run a test server (without backend cron jobs, CVS, SVN, vhosts, FRS, etc.)
// Please update the following variables:
// $codendi_dir = "mydir/tuleap"
// $sys_custom_dir = "mydir/etc/tuleap"
// $sys_data_dir = "mydir/data"


// Tuleap directory (PHP pages, site-content, etc)
$codendi_dir = "/usr/share/tuleap";
$tuleap_dir  = $codendi_dir;

// Site specific settings are stored there:
$sys_custom_dir = "/etc/tuleap";

// Tuleap binary prefix - do *not* use variables in this definition (compile_fileforge.sh)
$codendi_bin_prefix = "/usr/lib/tuleap/bin";

// Tuleap database configuration file
//$db_config_file = $sys_custom_dir . "/conf/database.inc";

// Data Directory
$sys_data_dir = "/var/lib/tuleap";

// Place to put temporary data: deleted user's accounts, etc
$tmp_dir = "/tmp";

// Tuleap utils directory
// WARNING: DO NOT USE VARIABLES in the three parameter definitions below.
// For instance, do *not* use someting like
//    *wrong* $codendi_utils_prefix = $codendi_dir . "tuleap/utils" *wrong*
// this is because those variables will be read by shell scripts.
$codendi_utils_prefix         = "/usr/share/tuleap/src/utils";

// Where wiki attachments are stored
$sys_wiki_attachment_data_dir = $sys_data_dir . "/wiki";

// Where the Tuleap files are placed
$sys_urlroot = $codendi_dir . "/src/www/";

// Site-content paths
$sys_incdir = $codendi_dir . '/site-content';// Directory which hosts the site content
$sys_custom_incdir = $sys_custom_dir . '/site-content';// Directory which hosts the customized site content

// Cache location
// This directory must be world reachable, but writable only by the web-server
$codendi_cache_dir = $tmp_dir;

$homedir_prefix = "/home/users"; // prefix to add to the user's homedir
$grpdir_prefix  = "/home/groups";// prefix to add to the project's homedir

// CVS paths
$cvs_prefix          = "/var/lib/tuleap/cvsroot";  // cvs root
$cvslock_prefix      = "/var/lock/cvs";           // prefix to add to the cvs lock directory
$cvs_root_allow_file = "/etc/cvs_root_allow";// File containing allowed cvs roots (regular CVS *and* CVSnt)
$cvsnt_config_file   = "/etc/cvsnt/PServer"; // File containing CVSnt configuration
$cvs_cmd             = "/usr/bin/cvs";       // CVS binary
$cvs_hook_tmp_dir    = "/var/run/log_accum"; // temporary directory used by CVS commit hooks. Must be similar in commit_prep

// Subversion paths
$svn_prefix    = "/var/lib/tuleap/svnroot";// prefix to add to the subversion root directory
$svnadmin_cmd  = "/usr/bin/svnadmin";     // SVN Admin binary
$svn_root_file = "/etc/httpd/conf.d/tuleap_svnroot.conf"; // File containing SVN repository definitions for Apache
$svn_log_path  = 'logs/svn_log'; // Where apache logs files for svn should go to

// FRS paths - do *not* use variables in the following parameters
// If you change those paths, please run utils/compile_fileforge.sh
$ftp_frs_dir_prefix  = "/var/lib/tuleap/ftp/codendi";   // Where the released files are located
$ftp_incoming_dir    = "/var/lib/tuleap/ftp/incoming";// Where files are placed when uploaded
$ftp_anon_dir_prefix = "/var/lib/tuleap/ftp/pub";     // prefix to add to the anon ftp project homedir

// Path for DB dumps. Do *not* use variables in this parameter
$dump_dir         = "/var/lib/tuleap/dumps";// Where should we stick files we're working with

// Paths for backend scripts
$codendi_log        = "/tmp";   // Tuleap log dir (cvslogs,)
$codendi_shell_skel = "/etc/skel_codendi";  // Tuleap Shell skeleton files directory

// Sendmail alias
$alias_file      = "/etc/aliases.codendi";
// Comma separated paths where Tuleap can find plugins
$sys_extra_plugin_path = '';

// How much to add to the database unix_uid to get the actual unix uid
// NOTE: if you change the default value, you need to update /etc/libnss-mysql.cfg
$unix_uid_add	= "20000";
// How much to add to the database group_id to get the unix gid
// NOTE: if you change the default value, you need to update /etc/libnss-mysql.cfg
$unix_gid_add	= "1000";

// Users
$sys_http_user  = "codendiadm"; // Unix user running the HTTP server

//
//
// Part III - GUI modifications (menu colors, etc.) and other parameters
//
//


// Group id corresponding to Tuleap News group
// (was group 714 in SourceForge - sfnews)
$sys_news_group = 46;

// Make project categorisation mandatory. If a project has not categorized itself,
// it will result in a warning in project summary page.
$sys_trove_cat_mandatory = 1;

// Linefeed characters: \r\n for Windows, \n for Linux/Unix
$sys_lf = "\n";

// Disable sub-domains (like cvs.proj.example.com)
// Should be disabled if no DNS delegation
$sys_disable_subdomains = 0;

//
// Authentication scheme
//
// Should be either 'ldap' or 'codendi'
$sys_auth_type = 'codendi';

// System name to be used in Web pages, documentation,etc.
$sys_name = 'Tuleap';

// Company/Organization running the system (short and long)
$sys_org_name      = '%sys_org_name%';
$sys_long_org_name = '%sys_long_org_name%';

// Email address pointing to the Tuleap site administrators
// for use Web pages, documentation,etc.
$sys_email_admin = 'codendi-admin@%sys_default_domain%';

// Email address pointing to the Tuleap contacts.
// for use Web pages, documentation,etc.
$sys_email_contact = 'codendi-contact@%sys_default_domain%';

// Address from which emails are sent
$sys_noreply = '"Tuleap" <noreply@%sys_default_domain%>';

// Supported languages (separated comma)
// Only en_US and fr_FR are available for now
// Exemple: 'en_US,fr_FR'
$sys_supported_languages = 'en_US,fr_FR';

// Default language for the system
$sys_lang = 'en_US';

// Is license approval mandatory when downloading a file from the FRS?
// (1 is mandatory, 0 is optional)
$sys_frs_license_mandatory = 1;

// Default session duration when user select "Remember Me" option in user
// account maintainance (in seconds)
// Default value is about 6 months: 3600*24*183=15811200
$sys_session_lifetime = 15811200;

// Duration before deleting pending accounts which have not been activated
// (in days)
// Default value is 60 days
$sys_pending_account_lifetime = 60;

// Default password duration. User will be asked to change its password
// after 'sys_password_lifetime' days.
// 0 = no duration
$sys_password_lifetime = 0;

// Suspend user account after a given period of inactivity (in days)
// 0 = no inactivity checking
$sys_suspend_inactive_accounts_delay = 0;

// Suspend user account if he has been no  more member of any project (in days)
// 0 = never desactivate "not project members" account
$sys_suspend_non_project_member_delay = 0;

// Delay between the date when a doc is deleted by a user and the date the
// corresponding file is erased on the filesystem.
// Useful for backup and to restore files that where deleted by mistake
// Default value is 3 days
$sys_file_deletion_delay = 3;

// Max upload size for quick release, documentation.
// in bytes. E.g. 67108864 for 64 MB
// Make sure that php.ini and mysql configuration file (/etc/my.cnf)
// are also configured to allow this size.
$sys_max_size_upload = 67108864;

// Enable trove categorization (project tree/software map)
// (1 to enable, 0 to disable)
$sys_use_trove = 1;

//Enable new project registration
// (1 to enable, 0 to disable)
$sys_use_project_registration = 1;

//User's avatar path
$sys_avatar_path = $sys_data_dir . "/user/avatar/";

//Markdown library path
$markdown_path = "/usr/share/php-markdown";

//Exchange policy URL
$sys_exchange_policy_url = "/plugins/docman/?group_id=1";

// Displays to end users the privacy of the project
// If enabled (1), a 'lock' icon will be displayed plus the mention public|private
// If disabled (0), only the project name is displayed
$sys_display_project_privacy_in_service_bar = 1;

// Hard limit to number of emailed people when the news admin choose to send a news
// by email.
// Only used in news service for now
$sys_max_number_of_emailed_people = 100;

// Allow (or not) users to do a SVN commit without any commit message
// Set to 0 to force commit message to not be empty
$sys_allow_empty_svn_commit_message = 1;

// Default theme variant in forge
$sys_default_theme_variant = 'FlamingParrot_Orange';

// List of available theme variant in forge
// Available variants:
// * FlamingParrot_Orange
// * FlamingParrot_Blue
// * FlamingParrot_Green
// * FlamingParrot_BlueGrey
// * FlamingParrot_Purple
// * FlamingParrot_Red
$sys_available_theme_variants = 'FlamingParrot_Orange,FlamingParrot_Blue,FlamingParrot_Green,FlamingParrot_BlueGrey,FlamingParrot_Purple,FlamingParrot_Red';

// Set the reporting level for logging
//
//  Possible levels:
// - debug
// - info
// - warn
// - error
//
// Note: as of today, not all loggers respects this value
$sys_logger_level = 'warn';


// Define the email domain for email gateway feature
// By default, email domain = default Tuleap domain (see $sys_default_domain)
$sys_default_mail_domain = "";

//Backup path for deleted projects
$sys_project_backup_path = $tmp_dir;

// Whitelist URLs for the Content-Security-Policy directive script-src
// This could be needed if JavaScript code needs to be executed from an external website
// For example, this could be 'https://example.com http://tuleap.net' or 'https://example.com/script.js'
$sys_csp_script_scr_whitelist = '';

// Convert cvs commit log messages from cp1252 to utf8.
$sys_cvs_convert_cp1252_to_utf8 = 0;

// Comma separated list of IP addresses that are trusted reverse proxy.
//
// When you setup a reverse proxy in front of Tuleap (for SSL termination or
// load balancer for instance, you should set there the IP address of the proxy
//
// /!\ SECURITY WARINING /!\
//
// When enabled, Tuleap will trust following HTTP headers:
// - X_FORWARDED_FOR
// - X_FORWARDED_PROTO
// - REMOTE_ADDR
// You have to ensure those headers are properly set by your reverse proxy
// otherwise it might be an injection point for an attacker
$sys_trusted_proxies = '';
