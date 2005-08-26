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
require_once('common/collection/MultiMap.class');

$GLOBALS['Language']->loadLanguageMsg('pluginsAdministration', 'pluginsadministration');

session_require(array('group'=>'1','admin_flags'=>'A'));

$plugin_manager =& PluginManager::instance();

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
        switch ($_REQUEST['action']) {
            case 'install':
                if (isset($_REQUEST['name'])) {
                    $plugin_manager->installPlugin($name);
                }
                break;
            default:
                break;
        }
    }
}
$plugins    =& $plugin_manager->getAllPlugins();
$priorities =  array();
    
$title = $Language->getText('plugin_pluginsadministration','title');
$HTML->header(array('title'=>$title));
$output = '<h2>'.$title.'</h2>';

//{{{ Installed Plugins
$output .= '<fieldset><legend>'.$Language->getText('plugin_pluginsadministration','plugins').'</legend><form>';
if($plugins->isEmpty()) {
    $output .= $Language->getText('plugin_pluginsadministration','there_is_no_plugin');
} else {
    $titles = array();
    $titles[] = '';
    $titles[] = $Language->getText('plugin_pluginsadministration','Plugin');
    $titles[] = $Language->getText('plugin_pluginsadministration','Description');
    $titles[] = $Language->getText('plugin_pluginsadministration','Version');
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
            'icon'        => $enabled?$descriptor->getEnabledIconPath():$descriptor->getDisabledIconPath(),
            'plugin_id'   => $plugin->getId(), 
            'name'        => $name, 
            'description' => $descriptor->getDescription(), 
            'version'     => $descriptor->getVersion(), 
            'enabled'     => $enabled);
        $col_hooks =& $plugin->getHooks();
        $hooks =& $col_hooks->iterator();
        while($hooks->hasNext()) {
            $hook     =& $hooks->next();
            $priority = 0;
            if (!isset($priorities[$hook->getInternalString()])) {
                $priorities[$hook->getInternalString()] = array();
            }
            if (!isset($priorities[$hook->getInternalString()][$priority])) {
                $priorities[$hook->getInternalString()][$priority] = array();
            }
            $priorities[$hook->getInternalString()][$priority][$name] = $enabled;
        }
    }
    usort($plugins_table, create_function('$a, $b', 'return $a["name"] > $b["name"];'));
    for($i = 0; $i < count($plugins_table) ; $i++) {
        $output .= '<tr class="'.util_get_alt_row_color($i).'" '.($plugins_table[$i]['enabled']?'':'style="font-style:italic;"').' >';
        
        $output .= '<td width="26" style="text-align:center;"><img src="'.$plugins_table[$i]['icon'].'" alt="'.$plugins_table[$i]['name'].'" width="24" /></td>';
        $output .= '<td>'.$plugins_table[$i]['name'].'</td>';
        $output .= '<td>'.$plugins_table[$i]['description'].'</td>';
        $output .= '<td>'.$plugins_table[$i]['version'].'</td>';
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
    $output .= '<fieldset><legend>'.$Language->getText('plugin_pluginsadministration','not_yet_installed').'</legend>';
    
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
    foreach($priorities as $hook => $priorities_plugins) {
        if (count($priorities_plugins, COUNT_RECURSIVE)-1 > 1) {
            $show_priorities = true;
            break;
        }
    }
    
    if($show_priorities) {
        $output .= '<fieldset><legend>'.$Language->getText('plugin_pluginsadministration','priorities').'</legend>';
        $titles = array();
        $titles[] = $Language->getText('plugin_pluginsadministration','Hook');
        $titles[] = $Language->getText('plugin_pluginsadministration','Plugins');
        $output .= html_build_list_table_top($titles);
        function emphasis($name, $enable) {
            if (!$enable) {
                $name = '<span style="font-style:italic;">'.$name.'</span>';
            }
            return $name;
        }
        $class = 0;
        ksort($priorities);
        foreach($priorities as $hook => $priorities_plugins) {
            if (count($priorities_plugins, COUNT_RECURSIVE)-1 > 1) {
                $output .= '<tr class="'.util_get_alt_row_color($class).'">';
                $output .= '<td style="vertical-align:top;" rowspan="'.(count($priorities_plugins)+1).'" >'.$hook.'</td></tr>';
                krsort($priorities_plugins);
                foreach($priorities_plugins as $priority => $plugins) {
                    
                    $output .= '<tr class="'.util_get_alt_row_color($class).'"><td>';
                    reset($plugins);
                    list($name, $enabled) = each($plugins);
                    $output .= emphasis($name, $enabled);
                    while (list($name, $enabled) = each($plugins)) {
                        $output .= ', '.emphasis($name, $enabled);
                    }
                    $output .= '</td>';
                    $output .= '</tr>';
                }
                $class++;
            }
        }
        
        $output .= '</table>';
        $output .= '</fieldset>';
    }
}
/**/
//}}}
echo $output;

$HTML->footer(array());
?>
