    <?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: Sanitizer.class,v 1.1 2005/05/10 09:48:11 nterray Exp $
 *
 * front-end to plugins administration
 */
require_once('pre.php');
require_once('common/plugin/PluginManager.class');
require_once('common/plugin/PluginHookPriorityManager.class');
require_once('common/collection/MultiMap.class');

$GLOBALS['Language']->loadLanguageMsg('pluginsAdministration', 'pluginsadministration');

session_require(array('group'=>'1','admin_flags'=>'A'));

//get managers
$plugin_manager               =& PluginManager::instance();
$plugin_hook_priority_manager =& new PluginHookPriorityManager();

//Process request
if (isset($_REQUEST['action']) && isset($_REQUEST['plugin_id'])) {
    $plugin_factory =& PluginFactory::instance();
    $plugin =& $plugin_factory->getPluginById($_REQUEST['plugin_id']);
    if($plugin) {
        switch ($_REQUEST['action']) {
            case 'enable':
                $plugin_manager->enablePlugin($plugin);
                break;
            case 'disable':
                $plugin_manager->disablePlugin($plugin);
                break;
            case 'uninstall':
                $plugin_manager->uninstallPlugin($plugin);
            default:
                break;
        }
    }
} else {
    if (isset($_REQUEST['action'])) {
        $plugin_factory =& PluginFactory::instance();
        switch ($_REQUEST['action']) {
            case 'install':
                if (isset($_REQUEST['name'])) {
                    $plugin_manager->installPlugin($name);
                }
                break;
            case 'update_priorities':
                if (isset($_REQUEST['priorities'])) {
                    $updated = false;
                    foreach($_REQUEST['priorities'] as $hook => $plugins) {
                        if (is_array($plugins)) {
                            foreach($plugins as $id => $priority) {
                                $plugin =& $plugin_factory->getPluginById((int)$id);
                                $updated = $updated || $plugin_hook_priority_manager->setPriorityForPluginHook($plugin, $hook, (int)$priority);
                            }
                        }
                    }
                    if ($updated) {
                        $GLOBALS['feedback'] .= 'Priorities updated';
                    }
                }
                break;
            default:
                break;
        }
    }
}

//get all plugins
$plugins    =& $plugin_manager->getAllPlugins();
$priorities =  array();

$title = $Language->getText('plugin_pluginsadministration','title');
$HTML->header(array('title'=>$title));
$output = '<h2>'.$title.'</h2>';

//{{{ Installed Plugins
$output .= '<fieldset style="margin-bottom:10px;"><legend style="font-size:1.3em; font-weight:bold;">'.$Language->getText('plugin_pluginsadministration','plugins').'</legend><form>';
if($plugins->isEmpty()) {
    $output .= $Language->getText('plugin_pluginsadministration','there_is_no_plugin');
} else {
    $titles = array();
    $titles[] = $Language->getText('plugin_pluginsadministration','Plugin');
    $titles[] = $Language->getText('plugin_pluginsadministration','Status');
    $titles[] = $Language->getText('plugin_pluginsadministration','Actions');
    $output .= html_build_list_table_top($titles);
    $iter =& $plugins->iterator();
    $plugins_table = array();
    while ($iter->hasNext()) {
        $plugin     =& $iter->next();
        $plug_info  =& $plugin->getPluginInfo();
        $descriptor =& $plug_info->getPluginDescriptor();
        $enabled = $plugin_manager->isPluginEnabled($plugin);
        $name = $descriptor->getName();
        if (strlen(trim($name)) === 0) {
            $name = get_class($plugin);
        }
        $plugins_table[] = array(
            'plugin_id'   => $plugin->getId(), 
            'name'        => $name, 
            'description' => $descriptor->getDescription(), 
            'version'     => $descriptor->getVersion(), 
            'enabled'     => $enabled);
        $col_hooks =& $plugin->getHooks();
        $hooks =& $col_hooks->iterator();
        while($hooks->hasNext()) {
            $hook     =& $hooks->next();
            $priority = $plugin_hook_priority_manager->getPriorityForPluginHook($plugin, $hook->getInternalString());
            if (!isset($priorities[$hook->getInternalString()])) {
                $priorities[$hook->getInternalString()] = array();
            }
            if (!isset($priorities[$hook->getInternalString()][$priority])) {
                $priorities[$hook->getInternalString()][$priority] = array();
            }
            $priorities[$hook->getInternalString()][$priority][$plugin->getId()] = array('name' => $name, 'enabled' => $enabled);
        }
    }
    usort($plugins_table, create_function('$a, $b', 'return strcasecmp($a["name"] , $b["name"]);'));
    for($i = 0; $i < count($plugins_table) ; $i++) {
        $output .= '<tr class="'.util_get_alt_row_color($i).'" >';
        
        $output .= '<td style="vertical-align:top;'.($plugins_table[$i]['enabled']?'':'font-style:italic; color:gray;').'"><span style="font-size:1.1em; font-weight:bold;">'.$plugins_table[$i]['name'].'</span> &nbsp; <span style="font-size:0.9em;">'.$plugins_table[$i]['version'].'</span><br />';
        $output .= $plugins_table[$i]['description'].'</td>';
        if ($plugins_table[$i]['enabled']) {
            $output .= '<td><a href="?action=disable&plugin_id='.$plugins_table[$i]['plugin_id'].'" title="'.$Language->getText('plugin_pluginsadministration','change_to_disabled').'">'.$Language->getText('plugin_pluginsadministration','enabled').'</a></td>';
        } else {
            $output .= '<td><a href="?action=enable&plugin_id='.$plugins_table[$i]['plugin_id'].'" title="'.$Language->getText('plugin_pluginsadministration','change_to_enabled').'">'.$Language->getText('plugin_pluginsadministration','disabled').'</a></td>';
        }
        $output .= '<td><a href="?action=uninstall&plugin_id='.$plugins_table[$i]['plugin_id'].'" title="'.$Language->getText('plugin_pluginsadministration','uninstall_plugin').'"><img src="'.util_get_image_theme("ic/trash.png").'" height="16" width="16" border="0" alt="'.$Language->getText('plugin_pluginsadministration','uninstall_plugin').'"></a></td>';
        $output .= '<tr>';
    }
    $output .= '</table>';
}
$output .= '</form></fieldset>';
//}}}

//{{{ Not yet installed plugins
$not_yet_installed =& $plugin_manager->getNotYetInstalledPlugins();
if ($not_yet_installed && count($not_yet_installed) > 0) {
    $output .= '<fieldset style="margin-bottom:10px;"><legend style="font-size:1.3em; font-weight:bold;">'.$Language->getText('plugin_pluginsadministration','not_yet_installed').'</legend>';
    $output .= '<div>Select the plugin you want to install:</div>';
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
//}}}

//{{{ Install new plugin
/**
$output .= '<fieldset><legend>'.$Language->getText('plugin_pluginsadministration','install').'</legend>';
$output .= '<form><div><input type="file" name="archive" /><input type="submit" name="install" value="'.$Language->getText('plugin_pluginsadministration','upload').'" /></div></form>';
$output .= '</fieldset>';
/**/
//}}}

//{{{ manage priorities
/**/

if (count($priorities) > 0) {
    $show_priorities = false;
    $hooks = array();
    foreach($priorities as $hook => $priorities_plugins) {
        $nb_plugins = 0;
        foreach($priorities_plugins as $priority => $plugins) {
            $nb_plugins += count($plugins);
        }
        $hooks[$hook] = $nb_plugins;
        if ($nb_plugins > 1) {
            $show_priorities = true;
        }
    }
    
    if($show_priorities) {
        $output .= '<form action="?" method="POST">';
        $output .= '<fieldset style="margin-bottom:10px;"><legend style="font-size:1.3em; font-weight:bold;">'.$Language->getText('plugin_pluginsadministration','priorities').'</legend>';
        $output .= '<input type="hidden" name="action" value="update_priorities" />';
        function emphasis($name, $enable) {
            if (!$enable) {
                $name = '<span style="font-style:italic; color:gray;">'.$name.'</span>';
            }
            return $name;
        }
        ksort($priorities);
        ksort($hooks);
        $javascript = '<script type="text/javascript">';
		$javascript .= <<<END
        var currentIdLayer = null;
		function switchBlock(id) {
			if (currentIdLayer && currentIdLayer != null) {
				hideBlock(currentIdLayer);
			}
			currentIdLayer = id;
			showBlock(currentIdLayer);
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
        function displayHook(hook_name) {
            id = 'hook_'+hook_name;
            switchBlock(id);
        }
END;
        $javascript .= "document.write('Select hook: ');";
        $javascript .= "document.write('<select onclick=\"displayHook(this.options[this.selectedIndex].value)\">');";
        $first_hook = false;
        foreach($hooks as $hook => $nb) {
            $etoile = "  ";
            if ($nb > 1) {
                $etoile = " *";
            }
            $javascript .= "document.write('<option value=\"".$hook."\">".$hook.$etoile."</option>');";
            if (!$first_hook) {
                $first_hook = $hook;
            }
        }
        $javascript .= "document.write('</select>');";
        $javascript .= "document.write('<div>* : hooks listened to by several plugins are followed by an asterisk.</div>');";
        $javascript .= '</script>';
        $output .= $javascript;
        foreach($priorities as $hook => $priorities_plugins) {
            $output_for_hook = '';
            krsort($priorities_plugins);
            $class      = 0;
            $nb_plugins = 0;
            foreach($priorities_plugins as $priority => $plugins) {
                $nb_plugins += count($plugins);
                foreach($plugins as $id => $infos) {
                    $output_for_hook .= '<tr class="'.util_get_alt_row_color($class).'"><td>';
                    $output_for_hook .= emphasis($infos['name'], $infos['enabled']);
                    $output_for_hook .= '</td><td>';
                    $output_for_hook .= '<input type="text" name="priorities['.$hook.']['.$id.']" size="4" value="'.$priority.'" />';
                    $output_for_hook .= '</td>';
                    $output_for_hook .= '</tr>';
                    $class++;
                }
            }
            $output .= '<div id="hook_'.$hook.'"><h3>Hook: '.$hook.'</h3>';
            $titles = array();
            $titles[] = $Language->getText('plugin_pluginsadministration','Plugin');
            $titles[] = 'Priority';
            $output .= html_build_list_table_top($titles, false, false, false);
            $output .= $output_for_hook;
            $output .= '</table></div>';
        }
        $javascript_after = '<script type="text/javascript">';
        $hooks = array_keys($priorities);
        foreach($hooks as $hook) {
            $javascript_after .= "hideBlock('hook_".$hook."');";
        }
        $javascript_after .= "displayHook('".$first_hook."');";
        $javascript_after .= "</script>";
        $output .= $javascript_after;
        $output .= '<div style="text-align:center;"><input type="submit" value="Update priorities" onclick="this.disabled = true;"/></div>';
        $output .= '</form>';
        $output .= '</fieldset>';

    }
}
/**/
//}}}
echo $output;

$HTML->footer(array());
?>
