<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/ForgeUpgradeConfig.class.php');
require_once('common/plugin/PluginManager.class.php');
require_once('common/plugin/PluginHookPriorityManager.class.php');
require_once('common/plugin/PluginDependencySolver.class.php');

class PluginsAdministrationViews extends Views {

    /** @var PluginManager */
    private $plugin_manager;

    /** @var PluginDependencySolver */
    private $dependency_solver;

    function PluginsAdministrationViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->plugin_manager = PluginManager::instance();
        $this->dependency_solver = new PluginDependencySolver($this->plugin_manager);
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_pluginsadministration','title');
        //$GLOBALS['HTML']->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');
        $GLOBALS['HTML']->header(array('title'=>$title, 'selected_top_tab' => 'admin'));
        echo '<h2>'.$title.'&nbsp;'.$this->_getHelp().'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    function display($view='') {
        switch ($view) {
        case 'ajax_projects':
            $this->$view();
            break;

        case 'browse':
            $this->_searchPlugins();
        default:
            parent::display($view);
        }
    }
    // {{{ Views
    function browse() {
        $output = '';
        $output .= $this->_installedPlugins();
        $output .= $this->_notYetInstalledPlugins();
        $output .= $this->_installNewPlugin();
        $output .= $this->_managePriorities();
        echo $output;
    }
    
    function postInstall() {
        $request =& HTTPRequest::instance();
        $name = $request->get('name');
        if ($name) {
            $plugin_manager = $this->plugin_manager;
            $p =& $plugin_manager->getPluginByName($name);
            if ($p) {
                echo '<h2>Congratulations!</h2>';
                echo '<p>You\'ve just installed '.$name.'</p>';
                $pi = $plugin_manager->getPostInstall($name);
                if ($pi) {
                    echo '<p>Please read the following:</p>';
                    echo '<pre style="border:1px solid black;">'. $pi .'</pre>';
                }
                echo '<a href="?">&lt;&lt; Go back to Plugins Administration</a>';
            }
        }
    }
    
    function confirmInstall() {
        $name = HTTPRequest::instance()->get('name');
        if ($this->isPluginAlreadyInstalled($name)) {
            $this->browse();
            return;
        }

        $dependencies = $this->dependency_solver->getUnmetInstalledDependencies($name);
        if ($dependencies) {
            $error_msg = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'error_install_dependency');
            $this->displayDependencyError($dependencies, $error_msg);
            $this->displayInstallReadme($name);
            return;
        }

        echo '<p>You\'re about to install '. $name .'.</p>';
        $this->displayInstallReadme($name);
        $this->displayConfirmationInstallForm($name);
    }

    private function isPluginAlreadyInstalled($name) {
        if ($this->plugin_manager->getPluginByName($name)) {
            return true;
        }
        return false;
    }

    private function displayInstallReadme($name) {
        $readme_file    = $this->plugin_manager->getInstallReadme($name);
        $readme_content = $this->plugin_manager->fetchFormattedReadme($readme_file);
        if ($readme_content) {
            echo $readme_content;
        }
    }

    private function displayConfirmationInstallForm($name) {
        echo '<form action="?" method="GET">';
        echo '<input type="hidden" name="action" value="install" />';
        echo '<input type="hidden" name="name" value="'. $name .'" />';
        echo '<input type="submit" name="cancel" value="No, I do not want to install this plugin" />';
        echo '<input type="submit" name="confirm" value="Yes, I am sure !" />';
        echo '</form>';
    }

    private function displayDependencyError($dependencies, $error_message) {
        $dependencies = implode('</em>, <em>', $dependencies);
        $return_msg   = $GLOBALS['Language']->getText('plugin_pluginsadministration_properties','return');

        echo '<p class="feedback_error">'. $error_message .' <em>'. $dependencies .'</em></p>';
        echo '<p><a href="/plugins/pluginsadministration/">'. $return_msg .'</a></p>';
    }

    function confirmUninstall() {
        $request =& HTTPRequest::instance();
        if (! $request->exist('plugin_id')) {
            $this->browse();
            return;
        }

        $plugin_manager = $this->plugin_manager;
        $plugin = $plugin_manager->getPluginById((int)$request->get('plugin_id'));
        if (! $plugin) {
            $this->browse();
            return;
        }

        $dependencies = $this->dependency_solver->getInstalledDependencies($plugin);
        if ($dependencies) {
            $error_msg = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'error_uninstall_dependency', $plugin->getName());
            $this->displayDependencyError($dependencies, $error_msg);
            return;
        }

        $this->displayUninstallationConfirmScreen($plugin);
    }

    private function displayUninstallationConfirmScreen(Plugin $plugin) {
        $plug_info  =& $plugin->getPluginInfo();
        $descriptor =& $plug_info->getPluginDescriptor();
        $name = $descriptor->getFullName();
        if (strlen(trim($name)) === 0) {
            $name = get_class($plugin);
        }
        $output = sprintf(file_get_contents($GLOBALS['Language']->getContent('confirm_uninstall', null, 'pluginsadministration')),
                                        $name,
                                        $plugin->getId());
        echo $output;
    }

    function ajax_projects() {
        $request =& HTTPRequest::instance();
        $p = $request->get('gen_prop');
        if ($p && isset($p['allowed_project'])) {
            $value = db_escape_string($p['allowed_project']);
            $sql = db_query("SELECT group_id, unix_group_name FROM groups WHERE group_id LIKE '%$value%' OR unix_group_name LIKE '%$value%'");
            if (db_numrows($sql)) {
                echo '<ul>';
                while($row = db_fetch_array($sql)) {
                    echo '<li>'. $row[0] .' ('. $row[1] .')</li>';
                }
                echo '</ul>';
            }
        }
    }
    function properties() {
        $pm = ProjectManager::instance();
        $link_to_plugins = dirname($_SERVER['REQUEST_URI']).'/';
        $request =& HTTPRequest::instance();
        if ($request->exist('plugin_id')) {
            $plugin_manager = $this->plugin_manager;
            $plugin_factory =& PluginFactory::instance();
            $plugin =& $plugin_factory->getPluginById($request->get('plugin_id'));
            if(!$plugin) {
                $GLOBALS['HTML']->redirect('/plugins/pluginsadministration/');
            } else {
                $plug_info  =& $plugin->getPluginInfo();
                $descriptor =& $plug_info->getPluginDescriptor();
        
                $available = $plugin_manager->isPluginAvailable($plugin);
                $name = $descriptor->getFullName();
                if (strlen(trim($name)) === 0) {
                    $name = get_class($plugin);
                }
                
                $col_hooks =& $plugin->getHooks();
                $hooks =& $col_hooks->iterator();
                $the_hooks = array();
                while($hooks->valid()) {
                    $hook =& $hooks->current();
                    $the_hooks[] = $hook;
                    $hooks->next();
                }
                natcasesort($the_hooks);
                $link_to_hooks = array();
                foreach($the_hooks as $hook) {
                    $link_to_hooks[] = '<a href="'.$link_to_plugins.'?selected_hook='.$hook.'" title="'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','display_priorities', array($hook)).'">'.$hook.'</a>';
                }
                $link_to_hooks = implode(', ', $link_to_hooks);
                
                //PropertyDescriptor
                $descs = $plug_info->getPropertyDescriptors();
                $keys  = $descs->getKeys();
                $iter  = $keys->iterator();
                $props = '';
                while($iter->valid()) {
                    $key   = $iter->current();
                    $desc  = $descs->get($key);
                    $prop_name = $desc->getName();
                    $props .= '<tr><td class="pluginsadministration_label">'. $prop_name .'</td><td>';
                    if (is_bool($desc->getValue())) {
                        $props .= '<input type="hidden"   name="properties['. $prop_name .']" value="0" />';
                        $props .= '<input type="checkbox" name="properties['. $prop_name .']" value="1" '. ($desc->getValue() ? 'checked="checked"' : '') .'/>';
                    } else {
                        $props .= sprintf('<input type="text" size="%d" name="properties[%s]" value="%s" />', strlen($desc->getValue()), $prop_name, $desc->getValue());
                    }
                    $props .= '</td></tr>';
                    $iter->next();
                }
                
                $output  = '<h3>'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_plugin', array($name)).'</h3>';
                $output .= '<form action="'. $_SERVER['REQUEST_URI'] .'" method="POST"><div><input type="hidden" name="plugin_id" value="'.$request->get('plugin_id').'" /></div>';
                $output .= '<table border="0" cellpadding="0" cellspacing="2" class="pluginsadministration_plugin_properties">';
                $output .= '<tbody>';
                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_name:').' </td>';
                $output .=     '<td>'.$descriptor->getFullName().'</td>';
                $output .=   '</tr>';
                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_version:').' </td>';
                $output .=     '<td>'.$descriptor->getVersion().'</td>';
                $output .=   '</tr>';
                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_description:').' </td>';
                $output .=     '<td>'.$descriptor->getDescription().'</td>';
                $output .=   '</tr>';
                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_scope:').' </td>';
                $output .=     '<td>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'scope_'.$plugin->getScope()).'</td>';
                $output .=   '</tr>';

                $dependencies = implode(', ', $plugin->getDependencies());
                if (! $dependencies) {
                    $dependencies = '–';
                }
                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_dependencies:').' </td>';
                $output .=     '<td>'. $dependencies .'</td>';
                $output .=   '</tr>';

                $output .=   '<tr>';
                $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_hooks:').' </td>';
                $output .=     '<td>'.$link_to_hooks.'</td>';
                $output .=   '</tr>';
                if($plugin->getScope() == Plugin::SCOPE_PROJECT) {
                    $output .=   '<tr>';
                    $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_projects:').' </td>';
                    $output .=     '<td>';
                    $_isProjectPluginRestricted = $plugin_manager->isProjectPluginRestricted($plugin);
                    if($_isProjectPluginRestricted) {
                        $projectIds = $plugin_manager->getAllowedProjects($plugin);
                        if(count($projectIds) > 0) {
                            $projects = '';
                            foreach($projectIds as $project_id) {
                                if ($p = $pm->getProject($project_id)) {
                                    $projects .= ' '.$project_id .' ('. $p->getUnixName() .'),';
                                }
                            }
                            $output .= substr($projects, 0, -1);
                        }
                        else {
                            $output .= $GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_no_prj_for_plugin');
                        }
                    }
                    else {
                        $output .= $GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_av_for_prj');
                    }

                    $output .=       '<br>';
                    $output .=       $GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_addproject').' <span><input name="gen_prop[allowed_project]" id="gen_prop_allowed_project" type="text" value="" /></span>';
                    $output .=       '&nbsp;';
                    $output .=       $GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_delproject').' <input name="gen_prop[disallowed_project]" type="text" value="" />';

                    $output .= <<<EOS
                    <style type="text/css">
                    #gen_prop_allowed_project_choices {
                        background:white;
                    }
                    #gen_prop_allowed_project_choices ul {
                        margin:0;
                        padding:0;
                        list-style:none;
                    }
                    #gen_prop_allowed_project_choices ul li.selected {
                        background:#eef;
                    }
                    </style>
                    <script type="text/javascript">
                    Event.observe(window, 'load', function () {
                            var ori = $('gen_prop_allowed_project');
                            if (ori) {
                                var update = Builder.node('div', {id:'gen_prop_allowed_project_choices', style:'background:white'});
                                Element.hide(update);
                                ori.parentNode.appendChild(update);
                                new Ajax.Autocompleter('gen_prop_allowed_project', update, '?view=ajax_projects', {
                                        tokens: ','
                                });
                            }
                    });
                    </script>
EOS;
                    $yesChecked = 'checked="checked" ';
                    $noChecked  = '';
                    if($_isProjectPluginRestricted) {
                        $yesChecked = '';
                        $noChecked  = 'checked="checked" ';
                    }

                    $output .=       '<br>';
                    $output .=       $GLOBALS['Language']->getText('plugin_pluginsadministration_properties', 'properties_is_av_for_prj').'&nbsp;';
                    $output .=       $GLOBALS['Language']->getText('plugin_pluginsadministration_properties', 'properties_yes').' <input type="radio" name="gen_prop[prj_restricted]" value="0" '.$yesChecked.' />';
                    $output .=       $GLOBALS['Language']->getText('plugin_pluginsadministration_properties', 'properties_no').' <input type="radio" name="gen_prop[prj_restricted]" value="1" '.$noChecked.' />';

                    $output .=     '</td>';
                    $output .=   '</tr>';
                    
                }
                if ($props !== '') {
                    $output .=   '<tr><td colspan="2"><hr /></td></tr>';
                    $output .=   $props;
                }
                if(($props !== '') || ($plugin->getScope() == Plugin::SCOPE_PROJECT)) {
                    $output .=   '<tr><td>&nbsp;</td><td><input type="hidden" name="action" value="change_plugin_properties" /><input type="submit" value="Change Properties" /></td></tr>';
                }
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</form>';
                
                $readme_file    = $plugin->getReadme();
                $readme_content = $plugin_manager->fetchFormattedReadme($readme_file);
                if ($readme_content) {
                    $output .= '<h3>Readme</h3>';
                    $output .= $readme_content;
                }
                
                $output .= '<div><a href="'.$link_to_plugins.'">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','return').'</a></div>';
                echo $output;
            }
        }
    }
    // }}}
    
    var $_plugins;
    var $_priorities;
    
    function _emphasis($name, $enable) {
        if (!$enable) {
            $name = '<span class="pluginsadministration_unavailable">'.$name.'</span>';
        }
        return $name;
    }
        
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<a href="javascript:help_window(\''.get_server_url().'/plugins/pluginsadministration/documentation/'.UserManager::instance()->getCurrentUser()->getLocale().'/'.$section.'\');">[?]</a>';
    }
    
    function _searchPlugins() {
        if (!$this->_plugins) {
            $this->_plugins    = array();
            $this->_priorities =  array();
            
            $plugin_hook_priority_manager = new PluginHookPriorityManager();
            $plugin_manager               = $this->plugin_manager;
            try {
                $forgeUpgradeConfig = new ForgeUpgradeConfig();
                $forgeUpgradeConfig->loadDefaults();
                $noFUConfig = array();
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('warning', $e->getMessage());
            }

            $plugins = $plugin_manager->getAllPlugins();
            foreach($plugins as $plugin) {
                $plug_info  =& $plugin->getPluginInfo();
                $descriptor =& $plug_info->getPluginDescriptor();
                $available = $plugin_manager->isPluginAvailable($plugin);
                $name = $descriptor->getFullName();
                if (strlen(trim($name)) === 0) {
                    $name = get_class($plugin);
                }
                $dont_touch = (strcasecmp(get_class($plugin), 'PluginsAdministrationPlugin') === 0);
                $this->_plugins[] = array(
                    'plugin_id'   => $plugin->getId(), 
                    'name'        => $name, 
                    'description' => $descriptor->getDescription(), 
                    'version'     => $descriptor->getVersion(), 
                    'available'   => $available,
                    'scope'       => $plugin->getScope(),
                    'dont_touch'  => $dont_touch);

                if (isset($noFUConfig) && !$forgeUpgradeConfig->existsInPath($plugin->getFilesystemPath())) {
                    $noFUConfig[] = array('name' => $name, 'plugin' => $plugin);
                }

                $col_hooks =& $plugin->getHooks();
                $hooks =& $col_hooks->iterator();
                while($hooks->valid()) {
                    $hook     =& $hooks->current();
                    $priority = $plugin_hook_priority_manager->getPriorityForPluginHook($plugin, $hook);
                    if (!isset($this->_priorities[$hook])) {
                        $this->_priorities[$hook] = array();
                    }
                    if (!isset($this->_priorities[$hook][$priority])) {
                        $this->_priorities[$hook][$priority] = array();
                    }
                    $this->_priorities[$hook][$priority][$plugin->getId()] = array('name' => $name, 'available' => $available);
                    $hooks->next();
                }
            }

            // ForgeUpgrade configuration warning
            if (isset($noFUConfig) && count($noFUConfig) && isset($GLOBALS['forgeupgrade_file'])) {
                $txt = 'Some plugins are not referenced in ForgeUpgrade configuration, please add the following in <code>'.$GLOBALS['forgeupgrade_file'].'.</code><br/>';
                foreach ($noFUConfig as $plugInfo) {
                    $txt .= '<code>path[]="'.$plugInfo['plugin']->getFilesystemPath().'"</code><br/>';
                }
                $GLOBALS['Response']->addFeedback('warning', $txt, CODENDI_PURIFIER_DISABLED);
            }
        }
    }
    
    function _installedPlugins() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        $output .= '<fieldset class="pluginsadministration"><legend>'.$Language->getText('plugin_pluginsadministration','plugins').'&nbsp;'.$this->_getHelp('manage').'</legend><form>';
        $titles = array();
        $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Plugin');
        $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Available?');
        $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Scope');
        $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Actions');
        $output .= html_build_list_table_top($titles);
        usort($this->_plugins, create_function('$a, $b', 'return strcasecmp($a["name"] , $b["name"]);'));
        for($i = 0; $i < count($this->_plugins) ; $i++) {
            $output .= '<tr class="'.util_get_alt_row_color($i).'">';
            
            $output .= '<td class="pluginsadministration_plugin_descriptor '.($this->_plugins[$i]['available']?'':' pluginsadministration_unavailable ').'"><span class="pluginsadministration_name_of_plugin">'.$this->_plugins[$i]['name'].'</span><span class="pluginsadministration_version_of_plugin">'.$this->_plugins[$i]['version'].'</span>';
            $output .= '<br/><span class="pluginsadministration_description_of_plugin">'.$this->_plugins[$i]['description'].'</span></td>';
            $output .= '<td>';
            $output .= $this->getPluginAvailableFlags($this->_plugins[$i]);
            $output .= '</td>';

            //Scope
            $output .= '<td>';
            $output .= $Language->getText('plugin_pluginsadministration','scope_'.$this->_plugins[$i]['scope']);
            $output .= '</td>';
            
            //Actions
            $output .= '<td>';
            //Properties
            $output .=   '<a class="pluginsadministration_action" href="?view=properties&plugin_id='.$this->_plugins[$i]['plugin_id'].'" title="'.$Language->getText('plugin_pluginsadministration','properties').'">';
            $output .=     '<img src="'.util_get_image_theme("ic/taskman16b.png").'" border="0" alt="'.$Language->getText('plugin_pluginsadministration','properties').'">';
            $output .=   '</a>';
            //Uninstall
            if(!$this->_plugins[$i]['dont_touch']) {
                $output .=   '<a class="pluginsadministration_action" href="?action=uninstall&plugin_id='.$this->_plugins[$i]['plugin_id'].'" title="'.$Language->getText('plugin_pluginsadministration','uninstall_plugin').'">';
                $output .=     '<img src="'.util_get_image_theme("ic/trash.png").'" border="0" alt="'.$Language->getText('plugin_pluginsadministration','uninstall_plugin').'">';
                $output .=   '</a>';
            }
            $output .= '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</form></fieldset>';
        return $output;
    }

    private function getPluginAvailableFlags(array $plugin_data) {
        $unavailable_flag = $this->getFlag($plugin_data['plugin_id'], 'unavailable', ! $plugin_data['available'], $plugin_data['dont_touch']);
        $available_flag   = $this->getFlag($plugin_data['plugin_id'], 'available', $plugin_data['available'], $plugin_data['dont_touch']);

        return $unavailable_flag .' '. $available_flag;
    }

    private function getFlag($plugin_id, $state, $is_active, $dont_touch) {
        $style  = '';
        $badge  = '';
        $output = '';
        $content = $GLOBALS['Language']->getText('plugin_pluginsadministration', $state);
        if ($is_active) {
            $badge = 'badge badge-'. ($state == 'available' ? 'success' : 'important');
        } else if ($dont_touch) {
            $style = 'style="visibility:hidden;"';
        } else {
            $title   = $GLOBALS['Language']->getText('plugin_pluginsadministration','change_to_'. $state);
            $content = '<a href="?action='. $state .'&plugin_id='. $plugin_id .'" title="'.$title.'">'. $content .'</a>';
        }
        $output .= '<span class="'. $badge .'" '. $style .'>'. $content .'</span>';
        return $output;
    }

    function _installNewPlugin() {
        //Not yet implemented
        /**
        $output .= '<fieldset><legend>'.$Language->getText('plugin_pluginsadministration','install').'</legend>';
        $output .= '<form><div><input type="file" name="archive" /><input type="submit" name="install" value="'.$Language->getText('plugin_pluginsadministration','upload').'" /></div></form>';
        $output .= '</fieldset>';
        /**/
        return '';
    }
    
    function _managePriorities() {
        $request        =& HTTPRequest::instance();
        $plugin_manager = $this->plugin_manager;
        $Language       =& $GLOBALS['Language'];
        $output = '';
        $this->_searchPlugins();
        $priorities = $this->_priorities;
        if (count($priorities) > 0) {
            $show_priorities = false;
            $hooks = array();
            foreach($priorities as $hook => $priorities_plugins) {
                $nb_plugins = 0;
                foreach($priorities_plugins as $priority => $plugins) {
                    $nb_plugins += count($plugins);
                }
                $hooks[$hook] = $nb_plugins;
                if ($nb_plugins > 0 && !$show_priorities) {
                    $show_priorities = true;
                }
            }
            
            if($show_priorities) {
                $form_name = '_'.mt_rand();
                $output .= '<form  name="'.$form_name.'" action="?" method="POST" onsubmit="return submitForm(this);">';
                $output .= '<fieldset class="pluginsadministration"><legend>'.$GLOBALS['Language']->getText('plugin_pluginsadministration','priorities').'&nbsp;'.$this->_getHelp('priorities').'</legend>';
                $output .= '<input type="hidden" name="action" value="update_priorities" />';
                function emphasis($name, $enable) {
                    if (!$enable) {
                        $name = '<span class="pluginsadministration_unavailable">'.$name.'</span>';
                    }
                    return $name;
                }
                ksort($priorities);
                ksort($hooks);
                $discard_changes = $GLOBALS['Language']->getText('plugin_pluginsadministration','discard_changes');
                $javascript = '<script type="text/javascript">';
                $javascript .= <<<END
                
                function submitForm(form) {
                    form.submit_button.disabled = true;
                    return true;
                }
                
                var currentIdLayer = null;
                function switchBlock(id) {
                    if (currentIdLayer && currentIdLayer != null) {
                        hideBlock(currentIdLayer);
                    }
                    currentIdLayer = id;
                    showBlock(currentIdLayer);
                }
                function getElement(id) {
                    the_element = null;
                    if(document.getElementById) {//NN6,Mozilla,IE5?
                        the_element = document.getElementById(id);
                    } else if(document.all) {      //IE4?
                        the_element = document.all(id);
                    } else if(document.layers) {   //NN4?
                        the_element = document.layers[id];
                    }
                    return the_element;
                }
                
                function showOrHideBlock(idName, show){ 
                    if(document.getElementById) {//NN6,Mozilla,IE5?
                        document.getElementById(idName).style.display = (show?"block":"none");
                    }
                    else if(document.all) {      //IE4?
                        document.all(idName).style.display = (show?"block":"none");
                    }
                    else if(document.layers) {   //NN4?
                        document.layers[idName].display = (show?"block":"none");
                    }
                }
                function showBlock(idName){
                    showOrHideBlock(idName, true);
                }
                function hideBlock(idName){ 
                    showOrHideBlock(idName, false);
                }
                function changeHook(select) {
                    
                    nb = inputs_for_hook[currentIdLayer].length;
                    changes_have_been_made = false;
                    //Search for changes
                    for(i = 0 ; i < nb && !changes_have_been_made ; i++) {
                        element         = getElement(inputs_for_hook[currentIdLayer][i]);
                        default_element = getElement('default_'+inputs_for_hook[currentIdLayer][i]);
                        
                        if (element.value != default_element.value) {
                            changes_have_been_made = true;
                        }
                    }
                    if (!changes_have_been_made || confirm('$discard_changes')) {
                        if (changes_have_been_made) {
                            //Discard changes: reset values
                            for(i = 0 ; i < nb ; i++) {
                                element         = getElement(inputs_for_hook[currentIdLayer][i]);
                                default_element = getElement('default_'+inputs_for_hook[currentIdLayer][i]);
                                element.value   = default_element.value;
                            }
                        }
                        currentIndex = select.selectedIndex;
                        hook_name = select.options[select.selectedIndex].value;
                        switchBlock('hook_'+hook_name);
                        getElement('selected_hook').value = hook_name;
                        return true;
                    } else {
                        //Changes have been made and user doesn't want to discard those changes
                        select.selectedIndex = currentIndex;
                        return false;
                    }
                }
END;
                $javascript .= "document.write('".$GLOBALS['Language']->getText('plugin_pluginsadministration', 'select_hook')." ');";
                $javascript .= "document.write('<select onchange=\"changeHook(this)\">');";
                
                $first_hook = $request->get('selected_hook');
                if ($first_hook !== false && !array_key_exists($first_hook, $hooks)) {
                    $first_hook = false;
                }
                $first_index = 0;
                $i = 0;
                foreach($hooks as $hook => $nb) {
                    $etoile = "  ";
                    if ($nb > 1) {
                        $etoile = " *";
                    }
                    $selected = "";
                    if (!$first_hook) {
                        $first_hook  = $hook;
                    }
                    if ($first_hook == $hook) {
                        $first_index = $i;
                        $selected    = " selected=\"selected\" ";
                    }
                    $javascript .= "document.write('<option value=\"".$hook."\" ".$selected." >".$hook.$etoile."</option>');";
                    $i++;
                }
                $javascript .= "document.write('</select>');";
                $javascript .= "document.write('<input type=\"hidden\" name=\"selected_hook\" id=\"selected_hook\" value=\"".$first_hook."\" />');";
                $javascript .= "document.write('<div>".addslashes($GLOBALS['Language']->getText('plugin_pluginsadministration', 'hook_asterisk'))."</div>');";
                $javascript .= '</script>';
                $output .= $javascript;
                $javascript_after = '<script type="text/javascript">';
                $javascript_after .= 'var currentIndex = '.$first_index.';';
                $javascript_after .= 'var inputs_for_hook = new Array();';
                foreach($priorities as $hook => $priorities_plugins) {
                    $javascript_after .= "hideBlock('hook_".$hook."');";
                    $javascript_after .= "inputs_for_hook['hook_".$hook."'] = new Array();";
                    $output_for_hook = '';
                    krsort($priorities_plugins);
                    $class      = 0;
                    $nb_plugins = 0;
                    foreach($priorities_plugins as $priority => $plugins) {
                        $nb_plugins += count($plugins);
                        foreach($plugins as $id => $infos) {
                            $output_for_hook .= '<tr class="'.util_get_alt_row_color($class).'"><td>';
                            $output_for_hook .= emphasis($infos['name'], $infos['available']);
                            $output_for_hook .= '</td><td>';
                            $input_name = 'priorities['.$hook.']['.$id.']';
                            $input_id   = 'priorities_'.$hook.'_'.$id;
                            $output_for_hook .= '<input type="text" name="'.$input_name.'" id="'.$input_id.'" size="4" value="'.$priority.'" />';
                            $output_for_hook .= '<input type="hidden" name="default_'.$input_name.'" id="default_'.$input_id.'" value="'.$priority.'" />';
                            $output_for_hook .= '</td>';
                            $output_for_hook .= '</tr>';
                            $javascript_after .= "inputs_for_hook['hook_".$hook."'][inputs_for_hook['hook_".$hook."'].length] = '".$input_id."';";
                            $class++;
                        }
                    }
                    $output .= '<div id="hook_'.$hook.'"><h3>'.$GLOBALS['Language']->getText('plugin_pluginsadministration','Hook:').' '.$hook.'</h3>';
                    $titles = array();
                    $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Plugin');
                    $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'Priority');
                    $output .= html_build_list_table_top($titles, false, false, false);
                    $output .= $output_for_hook;
                    $output .= '</table></div>';
                }
                $javascript_after .= "switchBlock('hook_".$first_hook."');";
                $javascript_after .= "</script>";
                $output .= $javascript_after;
                $output .= '<div class="pluginsadministration_buttons"><input type="submit" name="submit_button" value="'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'update_priorities').'" /></div>';
                $output .= '</form>';
                $output .= '</fieldset>';
        
            }
        }
        return $output;
    }
    
    function _notYetInstalledPlugins() {
        $plugin_manager = $this->plugin_manager;
        $Language       =& $GLOBALS['Language'];
        $output = '';
        $not_yet_installed =& $plugin_manager->getNotYetInstalledPlugins();
        if ($not_yet_installed && count($not_yet_installed) > 0) {
            $output .= '<fieldset class="pluginsadministration"><legend>'.$Language->getText('plugin_pluginsadministration','not_yet_installed').'&nbsp;'.$this->_getHelp('install').'</legend>';
            $output .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration','select_install').'</div>';
            $prefixe = '<a href="?action=install&name=';
            $middle  = '" title="'.$Language->getText('plugin_pluginsadministration','install_plugin').'">';
            $suffixe = '</a>';
            sort($not_yet_installed);
            reset($not_yet_installed);
            list($key,$value) = each($not_yet_installed);
            $output .= $prefixe.urlencode($value).$middle.$value.$suffixe;
            while(list($key,$value) = each($not_yet_installed)) {
                $output .= ', '.$prefixe.$value.$middle.$value.$suffixe;
            }
            $output .= '</fieldset>';
        }
        return $output;
    }
}


?>
