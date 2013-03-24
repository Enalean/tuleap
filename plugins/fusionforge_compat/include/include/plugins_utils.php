<?php

require_once('pre.php');

set_include_path(get_include_path() . PATH_SEPARATOR . Config::get('sys_custompluginsroot') . PATH_SEPARATOR . Config::get('sys_pluginsroot'));

$GLOBALS['mailman_lib_dir'] = '/var/lib/mailman';
$GLOBALS['mailman_bin_dir'] = '/usr/lib/mailman/bin';
$GLOBALS['forumml_arch'] = '/var/lib/mailman/archives';
$GLOBALS['forumml_tmp'] = '/var/run/forumml';
$GLOBALS['forumml_dir'] = '/var/lib/codendi/forumml';
$GLOBALS['sysdebug_lazymode_on'] = false;

function isLogged(){
        return user_isloggedin();
}
// TODO
function session_set() {
	return true;
}
function session_loggedin() {
        return user_isloggedin();
}
function session_get_user() {
        return UserManager::instance()->getCurrentUser();
}

function sysdebug_lazymode($enable) {
	global $sysdebug_lazymode_on;
	$sysdebug_lazymode_on = $enable ? true : false;
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

/**
 * plugin_hook () - run a set of hooks
 *
 * @param hookname - name of the hook
 * @param params - parameters for the hook
 */
function plugin_hook($hookname,$params) {
        $em =& EventManager::instance();
        $em->processEvent($hookname,$params);
}

function getImage($url) {
return util_get_image_theme($url);
}

// array_replace_recursive only appeared in PHP 5.3.0
if (!function_exists('array_replace_recursive')) {
        /**
         * Replaces elements from passed arrays into the first array recursively
         * @param array $a1 The array in which elements are replaced.
         * @param array $a2 The array from which elements will be extracted.
         * @return Returns an array, or NULL if an error occurs.
         */
        function array_replace_recursive ($a1, $a2) {
                $result = $a1 ;

                if (!is_array ($a2)) {
                        return $a2 ;
                }

                foreach ($a2 as $k => $v) {
                        if (!is_array ($v) ||
                            !isset ($result[$k]) || !is_array ($result[$k])) {
                                $result[$k] = $v ;
                        }

                        $result[$k] = array_replace_recursive ($result[$k],
                                                               $v) ;
                }

                return $result ;
        }
}

class PluginSpecificRoleSetting {
        var $role;
        var $name = '';
        var $section = '';
        var $values = array();
        var $default_values = array();
        var $global = false;

        function PluginSpecificRoleSetting(&$role, $name, $global = false) {
                $this->global = $global;
                $this->role =& $role;
                $this->name = $name;
        }

        function SetAllowedValues($values) {
                $this->role->role_values = array_replace_recursive($this->role->role_values,
                                                                   array($this->name => $values));
                if ($this->global) {
                        $this->role->global_settings[] = $this->name;
                }
        }

        function SetDefaultValues($defaults) {
                foreach ($defaults as $rname => $v) {
                        $this->role->defaults[$rname][$this->name] = $v;
                }
        }

        function setValueDescriptions($descs) {
                global $rbac_permission_names ;
                foreach ($descs as $k => $v) {
                        $rbac_permission_names[$this->name.$k] = $v;
                }
        }

        function setDescription($desc) {
                global $rbac_edit_section_names ;
                $rbac_edit_section_names[$this->name] = $desc;
        }
}

/** TODO See ./common/include/Group.class.php FusionForge code */
function &group_get_objects($id_arr) {
        $return = array();

        return $return;
}

?>
