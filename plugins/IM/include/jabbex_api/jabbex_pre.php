<?php

define("JABBER_LOG_FILE",$GLOBALS['codendi_log']."/jabbex_log"); 

require_once("local.inc.php");

define("DEBUG_ON",false); // Set this flag true to enable debug mode. ___This flag must be false in production environments___

define("JABBER_SERVER_CONF_FILE",$GLOBALS['sys_custom_dir']."/plugins/IM/etc/jabbex_conf.xml"); // can we use $plugin->getPluginEtcRoot() here?

$jabber_server_conf = new JabberServerConf();
$jabber_server_conf->load_conf(JABBER_SERVER_CONF_FILE);

// set Jabber server hostname, username, and password here
define("JABBER_SERVER", $jabber_server_conf->get_server() );
require_once(dirname(__FILE__)."/lib/group_mng/".JABBER_SERVER.".php"); // Load the group mng to the current server.
define("JABBER_SERVER_DNS", $jabber_server_conf->get_server_dns() );
define("JABBER_SERVER_PORT", $jabber_server_conf->get_server_port() );
define("JABBER_WEBADMIN_SEC_PORT", $jabber_server_conf->get_webadmin_sec_port() );
define("JABBER_WEBADMIN_UNSEC_PORT", $jabber_server_conf->get_webadmin_unsec_port() );
define("JABBER_USERNAME", $jabber_server_conf->get_username() );
define("JABBER_PASSWORD", $jabber_server_conf->get_user_pwd() );
define("JABBER_LOCKMUC_PWD", $jabber_server_conf->get_lockmuc_pwd() );

define("JABBER_HELGA_JID", $jabber_server_conf->get_helga_jid()); // !!!This is exclusive for Openfire!!!
define("CONFERENCE_SERVICE_NAME","conference"); // !!! Load from conf.

define("JABBER_GROUP_MNG_ACTIVE", $jabber_server_conf->get_group_mng_active() ); // Defines if the shared group management is active or not.

define("RUN_TIME",20);	// set a maximum run time of 20 seconds
define("CBK_FREQ",0.5);	// fire a callback event every 1 second

define("JABBER_LOG_DATE_FORMAT","Ymd::G:i:s - ");

?>
