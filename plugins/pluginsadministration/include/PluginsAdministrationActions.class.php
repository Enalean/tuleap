<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/plugin/PluginManager.class.php');
require_once('common/plugin/PluginHookPriorityManager.class.php');

class PluginsAdministrationActions extends Actions {
    
    function PluginsAdministrationActions(&$controler, $view=null) {
        $this->Actions($controler);
        $GLOBALS['Language']->loadLanguageMsg('pluginsAdministration', 'pluginsadministration');
    }
    
    // {{{ Actions
    function available() {
        $plugin = $this->_getPluginFromRequest();
        if ($plugin) {
            $plugin_manager =& PluginManager::instance();
            if (!$plugin_manager->isPluginAvailable($plugin['plugin'])) {
                $plugin_manager->availablePlugin($plugin['plugin']);
                $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'feedback_available', array($plugin['name'])).'</div>';
            }
        }
    }
    
    function install() {
        $request =& HTTPRequest::instance();
        $name = $request->get('name');
        if ($name) {
            $plugin_manager =& PluginManager::instance();
            $plugin_manager->installPlugin($name);
        }
    }
    
    function unavailable() {
        $plugin = $this->_getPluginFromRequest();
        if ($plugin) {
            $plugin_manager =& PluginManager::instance();
            if ($plugin_manager->isPluginAvailable($plugin['plugin'])) {
                $plugin_manager->unavailablePlugin($plugin['plugin']);
                $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'feedback_unavailable', array($plugin['name'])).'</div>';
            }
        }
    }
    
    function uninstall() {
        $plugin = $this->_getPluginFromRequest();
        if ($plugin) {
            $plugin_manager =& PluginManager::instance();
            $uninstalled = $plugin_manager->uninstallPlugin($plugin['plugin']);
            if (!$uninstalled) {
                 $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_not_uninstalled', array($plugin['name'])).'</div>';
            } else {
                 $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_uninstalled', array($plugin['name'])).'</div>';
            }
        }
    }
    
    function updatePriorities() {
        $request        =& HTTPRequest::instance();
        if ($request->exist('priorities')) {
            $plugin_manager               =& PluginManager::instance();
            $plugin_hook_priority_manager =& new PluginHookPriorityManager();
            $updated = false;
            foreach($request->get('priorities') as $hook => $plugins) {
                if (is_array($plugins)) {
                    foreach($plugins as $id => $priority) {
                        $plugin =& $plugin_manager->getPluginById((int)$id);
                        $updated = $updated || $plugin_hook_priority_manager->setPriorityForPluginHook($plugin, $hook, (int)$priority);
                    }
                }
            }
            if ($updated) {
                $GLOBALS['feedback'] .= 'Priorities updated';
            }
        }
    }

    // Secure args: force each value to be an integer.    
    function _validateProjectList($usList) {
        $sPrjList = null;
        $usList = trim(rtrim($usList));
        if($usList) {
            $usPrjList = explode(',', $usList);
            $sPrjList = array_map('intval', $usPrjList);
        }
        return $sPrjList;
    }

    function _addAllowedProjects($prjList) {
        $plugin = $this->_getPluginFromRequest();
        $plugin_manager =& PluginManager::instance();
        $plugin_manager->addProjectForPlugin($plugin['plugin'], $prjList);
    }

    function _delAllowedProjects($prjList) {
        $plugin = $this->_getPluginFromRequest();
        $plugin_manager =& PluginManager::instance();
        $plugin_manager->delProjectForPlugin($plugin['plugin'], $prjList);
    }

    function _changePluginGenericProperties($properties) {
        if(isset($properties['allowed_project'])) {
            $sPrjList = $this->_validateProjectList($properties['allowed_project']);
            if($sPrjList !== null) {
                $this->_addAllowedProjects($sPrjList);
            }
        }
        if(isset($properties['disallowed_project'])) {
            $sPrjList = $this->_validateProjectList($properties['disallowed_project']);
            if($sPrjList !== null) {
                $this->_delAllowedProjects($sPrjList);
            }
        }
        if(isset($properties['prj_restricted'])) {
            $plugin = $this->_getPluginFromRequest();
            $plugin_manager =& PluginManager::instance();
            $resricted = ($properties['prj_restricted'] == 1 ? true : false);
            $plugin_manager->updateProjectPluginRestriction($plugin['plugin'], $resricted);
        }
    }

    function changePluginProperties() {
        $request =& HTTPRequest::instance();
        if($request->exist('gen_prop')) {
            $this->_changePluginGenericProperties($request->get('gen_prop'));
        }
        $user_properties = $request->get('properties');
        if ($user_properties) {
            $plugin = $this->_getPluginFromRequest();
            $plug_info =& $plugin['plugin']->getPluginInfo();
            $descs =& $plug_info->getPropertyDescriptors();
            $keys  =& $descs->getKeys();
            $iter  =& $keys->iterator();
            $props = '';
            while($iter->valid()) {
                $key   =& $iter->current();
                $desc  =& $descs->get($key);
                $prop_name = $desc->getName();
                if (isset($user_properties[$prop_name->getInternalString()])) {
                    $val = $user_properties[$prop_name->getInternalString()];
                    if (is_bool($desc->getValue())) {
                        $val = $val ? true : false;
                    }
                    $desc->setValue($val);
                }
                $iter->next();
            }
            $plug_info->saveProperties();
        }
    }
    // }}}
    
    
    function _getPluginFromRequest() {
        $return = false;
        $request =& HTTPRequest::instance();
        if ($request->exist('plugin_id') && is_numeric($request->get('plugin_id'))) {
            $plugin_manager =& PluginManager::instance();
            $plugin =& $plugin_manager->getPluginById($request->get('plugin_id'));
            if ($plugin) {
                $plug_info  =& $plugin->getPluginInfo();
                $descriptor =& $plug_info->getPluginDescriptor();
                $name = $descriptor->getFullName();
                if (strlen(trim($name)) === 0) {
                    $name = get_class($plugin);
                }
                $return = array();
                $return['name'] = $name;
                $return['plugin'] =& $plugin;
            }
        }
        return $return;
    }
}


?>
