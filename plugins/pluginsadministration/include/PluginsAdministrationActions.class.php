<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

    /** @var PluginManager */
    private $plugin_manager;

    /** @var PluginDependencySolver */
    private $dependency_solver;

    function PluginsAdministrationActions(&$controler, $view=null) {
        $this->Actions($controler);
        $this->plugin_manager = PluginManager::instance();
        $this->dependency_solver = new PluginDependencySolver($this->plugin_manager);
    }
    
    // {{{ Actions
    function available() {
        $plugin_data = $this->_getPluginFromRequest();
        if ($plugin_data) {
            $plugin_manager = $this->plugin_manager;
            $dependencies = $this->dependency_solver->getUnmetAvailableDependencies($plugin_data['plugin']);
            if ($dependencies) {
                $error_msg = 'Unable to avail '. $plugin_data['plugin']->getName() .'. Please avail the following plugins before: '. implode(', ', $dependencies);
                $GLOBALS['Response']->addFeedback('error', $error_msg);
                return;
            }
            if (!$plugin_manager->isPluginAvailable($plugin_data['plugin'])) {
                $plugin_manager->availablePlugin($plugin_data['plugin']);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_pluginsadministration', 'feedback_available', array($plugin_data['name'])));
            }
        }
    }
    
    function install() {
        $request =& HTTPRequest::instance();
        $name = $request->get('name');
        if ($name) {
            $this->plugin_manager->installPlugin($name);
        }
    }
    
    function unavailable() {
        $plugin_data = $this->_getPluginFromRequest();
        if ($plugin_data) {
            $plugin_manager = $this->plugin_manager;
            $dependencies = $this->dependency_solver->getAvailableDependencies($plugin_data['plugin']);
            if ($dependencies) {
                $error_msg = 'Unable to unavail '. $plugin_data['plugin']->getName() .'. Please unavail the following plugins before: '. implode(', ', $dependencies);
                $GLOBALS['Response']->addFeedback('error', $error_msg);
                return;
            }
            if ($plugin_manager->isPluginAvailable($plugin_data['plugin'])) {
                $plugin_manager->unavailablePlugin($plugin_data['plugin']);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_pluginsadministration', 'feedback_unavailable', array($plugin_data['name'])));
            }
        }
    }
    
    function uninstall() {
        $plugin = $this->_getPluginFromRequest();
        if ($plugin) {
            $plugin_manager = $this->plugin_manager;
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
            $plugin_manager               = $this->plugin_manager;
            $plugin_hook_priority_manager = new PluginHookPriorityManager();
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
        $plugin_manager = $this->plugin_manager;
        $plugin_manager->addProjectForPlugin($plugin['plugin'], $prjList);
    }

    function _delAllowedProjects($prjList) {
        $plugin = $this->_getPluginFromRequest();
        $plugin_manager = $this->plugin_manager;
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
            $plugin_manager = $this->plugin_manager;
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
                if (isset($user_properties[$prop_name])) {
                    $val = $user_properties[$prop_name];
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
            $plugin_manager = $this->plugin_manager;
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
