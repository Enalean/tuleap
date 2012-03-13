<?php

require_once('pre.php');

set_include_path(get_include_path() . PATH_SEPARATOR . Config::get('sys_custompluginsroot') . PATH_SEPARATOR . Config::get('sys_pluginsroot'));

$GLOBALS['mailman_lib_dir'] = '/var/lib/mailman';
$GLOBALS['mailman_bin_dir'] = '/usr/lib/mailman/bin';
$GLOBALS['forumml_arch'] = '/var/lib/mailman/archives';
$GLOBALS['forumml_tmp'] = '/var/run/forumml';
$GLOBALS['forumml_dir'] = '/var/lib/tuleap/forumml';
$GLOBALS['sysdebug_lazymode_on'] = false;

function isLogged(){
        return user_isloggedin();
}

function sysdebug_lazymode($enable) {
	global $sysdebug_lazymode_on;
	$sysdebug_lazymode_on = $enable ? true : false;
}

function forge_get_config($key) {
  $conf_variables_mapping = array(
  'lists_host' => 'sys_lists_host',
  'web_host'  => 'sys_default_domain',
  'config_path' => 'sys_custom_dir',
  'database_host' => 'sys_dbhost',
  'database_user' => 'sys_dbuser',
  'database_name' => 'sys_dbname',
  'database_password' => 'sys_dbpasswd',
  );
  if (isset($conf_variables_mapping[$key])) {
    $key = $conf_variables_mapping[$key];
  }
  return Config::get($key);
}

function htmlRedirect($url) {
        $GLOBALS['HTML']->redirect($url);
}
function htmlIframe($url,$poub) {
        $GLOBALS['HTML']->iframe($url,array('class' => 'iframe_service'));
}


function helpButton($params)
{
        echo ' | ';
        echo help_button($params,false,_('Help'));
}
function getIcon() {
        echo '<IMG SRC="'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT="13" WIDTH="15" BORDER="0">';
}
function util_make_url ($loc) {
        return session_make_url($loc);
}
function plugin_hook($hook,$params) {
        $em =& EventManager::instance();
        $em->processEvent($hook,$params);
}
function getImage($url) {
return util_get_image_theme($url);
}
?>
