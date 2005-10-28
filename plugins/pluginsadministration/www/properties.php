<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: Sanitizer.class,v 1.1 2005/05/10 09:48:11 nterray Exp $
 *
 * front-end to plugins administration
 */
define('PLUGINS_ADMINISTRATION', 1);
require_once('pre.php');
require_once('./common.php');
require_once('common/plugin/PluginFactory.class');
require_once('common/plugin/PluginManager.class');
require_once('common/include/HTTPRequest.class');

$GLOBALS['Language']->loadLanguageMsg('pluginsAdministration', 'pluginsadministration');

session_require(array('group'=>'1','admin_flags'=>'A'));

$link_to_plugins = dirname($_SERVER['REQUEST_URI']).'/';

function redirect() {
    session_redirect($link_to_plugins);
    exit();
}

$request =& HTTPRequest::instance();

if (!$request->exist('plugin_id')) {
    redirect();
} else {
    $plugin_manager =& PluginManager::instance();
    $plugin_factory =& PluginFactory::instance();
    $plugin =& $plugin_factory->getPluginById($request->get('plugin_id'));
    if(!$plugin) {
        redirect();
    } else {
        $plug_info  =& $plugin->getPluginInfo();
        $descriptor =& $plug_info->getPluginDescriptor();

        $enabled = $plugin_manager->isPluginEnabled($plugin);
        $name = $descriptor->getFullName();
        if (strlen(trim($name)) === 0) {
            $name = get_class($plugin);
        }
        
        $col_hooks =& $plugin->getHooks();
        $hooks =& $col_hooks->iterator();
        $the_hooks = array();
        while($hooks->valid()) {
            $hook =& $hooks->current();
            $the_hooks[] = $hook->getInternalString();
            $hooks->next();
        }
        natcasesort($the_hooks);
        $link_to_hooks = array();
        foreach($the_hooks as $hook) {
            $link_to_hooks[] = '<a href="'.$link_to_plugins.'?selected_hook='.$hook.'" title="'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','display_priorities', array($hook)).'">'.$hook.'</a>';
        }
        $link_to_hooks = implode(', ', $link_to_hooks);
        
        $output  = '<h3>'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_plugin', array($name)).'</h3>';
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
        $output .=     '<td class="pluginsadministration_label">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','properties_hooks:').' </td>';
        $output .=     '<td>'.$link_to_hooks.'</td>';
        $output .=   '</tr>';
        $output .= '</tbody>';
        $output .= '</table>';
        
        $output .= '<div><a href="'.$link_to_plugins.'">'.$GLOBALS['Language']->getText('plugin_pluginsadministration_properties','return').'</a></div>';
        $title = $GLOBALS['Language']->getText('plugin_pluginsadministration','title');
        $HTML->header(array('title'=>$title));
        echo '<h2>'.$title.'&nbsp;'.getHelp().'</h2>'.$output;
        $HTML->footer(array());
    }
}

?>
