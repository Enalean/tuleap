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
$confirmation = '';
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
                $plug_info  =& $plugin->getPluginInfo();
                $descriptor =& $plug_info->getPluginDescriptor();
                $name = $descriptor->getName();
                if (strlen(trim($name)) === 0) {
                    $name = get_class($plugin);
                }
                if (isset($_REQUEST['confirm'])) {
                    $uninstalled = $plugin_manager->uninstallPlugin($plugin);
                    if (!$uninstalled) {
                         $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_not_uninstalled', array($name)).'</div>';
                    } else {
                         $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_uninstalled', array($name)).'</div>';
                    }
                } else {
                    if (!isset($_REQUEST['cancel'])) {
                        $confirmation = sprintf(file_get_contents($GLOBALS['Language']->getContent('confirm_uninstall', null, 'pluginsadministration')),
                                        $name,
                                        $plugin->getId());
                    }
                }
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

if (isset($confirmation)) {
    $output .= $confirmation;
}

//{{{ Installed Plugins
$output .= '<fieldset class="pluginsadministration"><legend>'.$Language->getText('plugin_pluginsadministration','plugins').'</legend><form>';
if($plugins->isEmpty()) {
    $output .= $Language->getText('plugin_pluginsadministration','there_is_no_plugin');
} else {
    $titles = array();
    $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Plugin');
    $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','Status');
    $titles[] = $GLOBALS['Language']->getText('plugin_pluginsadministration','uninstall');
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
        
        $output .= '<td class="pluginsadministration_plugin_descriptor '.($plugins_table[$i]['enabled']?'':' pluginsadministration_disabled ').'"><span class="pluginsadministration_name_of_plugin">'.$plugins_table[$i]['name'].'</span><span class="pluginsadministration_version_of_plugin">'.$plugins_table[$i]['version'].'</span>';
        $output .= '<br/><span class="pluginsadministration_description_of_plugin">'.$plugins_table[$i]['description'].'</span></td>';
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
    $output .= '<fieldset class="pluginsadministration"><legend>'.$Language->getText('plugin_pluginsadministration','not_yet_installed').'</legend>';
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
        if ($nb_plugins > 0 && !$show_priorities) {
            $show_priorities = true;
        }
    }
    
    if($show_priorities) {
        $form_name = '_'.mt_rand();
        $output .= '<form  name="'.$form_name.'" action="" method="POST" onsubmit="return submitForm(this);">';
        $output .= '<fieldset class="pluginsadministration"><legend>'.$GLOBALS['Language']->getText('plugin_pluginsadministration','priorities').'</legend>';
        $output .= '<input type="hidden" name="action" value="update_priorities" />';
        function emphasis($name, $enable) {
            if (!$enable) {
                $name = '<span class="pluginsadministration_disabled">'.$name.'</span>';
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
        if (isset($_REQUEST['selected_hook']) && array_key_exists($_REQUEST['selected_hook'], $hooks)) {
            $first_hook = $_REQUEST['selected_hook'];
        } else {
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
                    $output_for_hook .= emphasis($infos['name'], $infos['enabled']);
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
/**/
//}}}
echo $output;

$HTML->footer(array());
?>
