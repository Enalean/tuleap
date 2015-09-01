<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

/*
 * PluginsAdministrationViews
 */

require_once('bootstrap.php');

class PluginsAdministrationViews extends Views {

    /** @var PluginManager */
    private $plugin_manager;

    /** @var PluginDependencySolver */
    private $dependency_solver;

    /** @var TemplateRendererFactory */
    private $renderer;

    function PluginsAdministrationViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->plugin_manager    = PluginManager::instance();
        $this->dependency_solver = new PluginDependencySolver($this->plugin_manager);
        $this->renderer          = TemplateRendererFactory::build()->getRenderer(
            PLUGINSADMINISTRATION_TEMPLATE_DIR
        );
    }

    public function header() {
        $title = $GLOBALS['Language']->getText('plugin_pluginsadministration','title');
        $GLOBALS['HTML']->header(array('title'=>$title, 'selected_top_tab' => 'admin'));
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
                $link_to_hooks = implode(', ', $the_hooks);

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
                $output .= '<table border="0" cellpadding="0" cellspacing="2" class="pluginsadministration_plugin_properties table table-striped table-bordered table-condensed">';
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
                if ($props !== '') {
                    $output .=   $props;
                }
                if(($props !== '') || ($plugin->getScope() == Plugin::SCOPE_PROJECT)) {
                    $output .=   '<tr><td>&nbsp;</td><td><input type="hidden" name="action" value="change_plugin_properties" /><input type="submit" class="btn btn-primary" value="Change Properties" /></td></tr>';
                }
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</form>';

                $output .= $plugin->getAdministrationOptions();

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

    public function restrict() {
        $request                    = HTTPRequest::instance();
        $plugin_factory             = PluginFactory::instance();
        $plugin_resource_restrictor = $this->getPluginResourceRestrictor();
        $plugin                     = $plugin_factory->getPluginById($request->get('plugin_id'));

        if ($plugin->getScope() !== Plugin::SCOPE_PROJECT) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'This project cannot be restricted.');
            $GLOBALS['Response']->redirect('/plugins/pluginsadministration/');
            die();
        }

        $presenter = new PluginsAdministration_ManageAllowedProjectsPresenter(
            $plugin,
            $plugin_resource_restrictor->searchAllowedProjectsOnPlugin($plugin),
            $plugin_resource_restrictor->isPluginRestricted($plugin)
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor');
        $renderer->renderToPage(PluginsAdministration_ManageAllowedProjectsPresenter::TEMPLATE, $presenter);
    }

    private function getPluginResourceRestrictor() {
        return new PluginResourceRestrictor(
            new RestrictedPluginDao()
        );
    }

    var $_plugins;

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
                $dont_touch    = (strcasecmp(get_class($plugin), 'PluginsAdministrationPlugin') === 0);
                $dont_restrict = $plugin->getScope() !== Plugin::SCOPE_PROJECT;

                $this->_plugins[] = array(
                    'plugin_id'     => $plugin->getId(),
                    'name'          => $name,
                    'description'   => $descriptor->getDescription(),
                    'version'       => $descriptor->getVersion(),
                    'available'     => $available,
                    'scope'         => $plugin->getScope(),
                    'dont_touch'    => $dont_touch,
                    'dont_restrict' => $dont_restrict);

                if (isset($noFUConfig) && !$forgeUpgradeConfig->existsInPath($plugin->getFilesystemPath())) {
                    $noFUConfig[] = array('name' => $name, 'plugin' => $plugin);
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
        usort($this->_plugins, create_function('$a, $b', 'return strcasecmp($a["name"] , $b["name"]);'));

        $i       = 0;
        $plugins = array();
        foreach ($this->_plugins as $plugin) {
            $plugins[] = array(
                'color'         => util_get_alt_row_color($i),
                'available'     => $plugin['available']? '': 'pluginsadministration_unavailable',
                'name'          => $plugin['name'],
                'version'       => $plugin['version'],
                'description'   => $plugin['description'],
                'flags'         => $this->getPluginAvailableFlags($plugin),
                'scope'         => $GLOBALS['Language']->getText(
                    'plugin_pluginsadministration',
                    'scope_'.$plugin['scope']
                ),
                'plugin_id'     => $plugin['plugin_id'],
                'dont_touch'    => $plugin['dont_touch'],
                'dont_restrict' => $plugin['dont_restrict'],
            );

            $i++;
        }

        $presenter = new PluginsAdministration_Presenter_InstalledPluginsPresenter(
            $this->_getHelp('manage'),
            $plugins
        );

        return $this->renderer->renderToString(
            'installed-plugins',
            $presenter
        );
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
