<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * PluginCreationWizardActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('Template.class.php');

class PluginCreationWizardActions extends Actions {
    
    function PluginCreationWizardActions(&$controler, $view=null) {
        $this->Actions($controler);
        $GLOBALS['Language']->loadLanguageMsg('plugincreationwizard', 'plugincreationwizard');
    }
    
    // {{{ Actions
    function introduction() {
    }
    
    function descriptor() {
        $request =& HTTPRequest::instance();
        $_SESSION['PluginCreationWizard_params']['short_name']                      = strtolower($request->get('class_name'));
        $_SESSION['PluginCreationWizard_params']['class_name']                      = $request->get('class_name');
        $_SESSION['PluginCreationWizard_params']['version']                         = $request->get('version');
        $_SESSION['PluginCreationWizard_params']['descriptor_name']                 = array();
        $descriptor_name = $request->get('descriptor_name');
        $_SESSION['PluginCreationWizard_params']['descriptor_name']['en_US']        = $descriptor_name['en_US'];
        $_SESSION['PluginCreationWizard_params']['descriptor_name']['fr_FR']        = $descriptor_name['fr_FR'];
        $_SESSION['PluginCreationWizard_params']['descriptor_description']          = array();
        $descriptor_description = $request->get('descriptor_description');
        $_SESSION['PluginCreationWizard_params']['descriptor_description']['en_US'] = $descriptor_description['en_US'];
        $_SESSION['PluginCreationWizard_params']['descriptor_description']['fr_FR'] = $descriptor_description['fr_FR'];
    }
    
    function webspace() {
        $request =& HTTPRequest::instance();
        $_SESSION['PluginCreationWizard_params']['use_web_space'] = $request->exist('use_web_space') && $request->get('use_web_space') == 'on';
        $_SESSION['PluginCreationWizard_params']['use_mvc']       = $request->exist('use_mvc') && $request->get('use_mvc') == 'on';
        $_SESSION['PluginCreationWizard_params']['use_css']       = $request->exist('use_css') && $request->get('use_css') == 'on';
    }
    
    function hooks() {
    }
    
    function database() {
        $request =& HTTPRequest::instance();
        $_SESSION['PluginCreationWizard_params']['create_db'] = $request->exist('create_db') && $request->get('create_db') == 'on';
        $_SESSION['PluginCreationWizard_params']['install']   = $request->get('install');
        $_SESSION['PluginCreationWizard_params']['uninstall'] = $request->get('uninstall');
    }
    
    function finish() {
        $this->create();
    }
    function create() {
        //Last options
        $request =& HTTPRequest::instance();
        $_SESSION['PluginCreationWizard_params']['create_cgi_bin'] = $request->exist('create_cgi_bin') && $request->get('create_cgi_bin') == 'on';
        $_SESSION['PluginCreationWizard_params']['create_etc']     = $request->exist('create_etc') && $request->get('create_etc') == 'on';
        
        
        
        
        $params = $_SESSION['PluginCreationWizard_params'];
        
        //Create plugin directory
        mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name']);
        
        //Create include directory
        mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include');
        
        //Create site-content directory
        mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/site-content');
        mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/site-content/en_US');
        mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/site-content/fr_FR');
        
        //create web directory
        if ($params['use_web_space']) {
            mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www');
            if ($params['use_css']) {
                mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www/themes');
                mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www/themes/default');
                mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www/themes/default/css');
            }
        }
        
        //create etc directory
        if ($params['create_etc']) {
            mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/etc');
        }
        
        //create db directory
        if ($params['create_db']) {
            mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/db');
        }
        
        //create cgi-bin directory
        if ($params['create_cgi_bin']) {
            mkdir($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/cgi-bin');
        }
        
        //create language tab
        $cle = 'plugin_'.$params['short_name'];
        $en_US  = $cle."\tdescriptor_description\t".nl2br($_SESSION['PluginCreationWizard_params']['descriptor_description']['en_US'])."\n";
        $en_US .= $cle."\tdescriptor_name\t".$_SESSION['PluginCreationWizard_params']['descriptor_name']['en_US']."\n\n";
        $en_US .= $cle."\ttitle\t".$_SESSION['PluginCreationWizard_params']['descriptor_name']['en_US']."\n\n";
        $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/site-content/en_US/'.$params['short_name'].'.tab', 'w');
        fwrite($f, $en_US);
        fclose($f);
        $fr_FR  = $cle."\tdescriptor_description\t".nl2br($_SESSION['PluginCreationWizard_params']['descriptor_description']['fr_FR'])."\n";
        $fr_FR .= $cle."\tdescriptor_name\t".$_SESSION['PluginCreationWizard_params']['descriptor_name']['fr_FR']."\n\n";
        $fr_FR .= $cle."\ttitle\t".$_SESSION['PluginCreationWizard_params']['descriptor_name']['fr_FR']."\n\n";
        $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/site-content/fr_FR/'.$params['short_name'].'.tab', 'w');
        fwrite($f, $fr_FR);
        fclose($f);
        
        $tpl =& new Template();
        $tpl->set('class_name', $params['class_name']);
        $tpl->set('short_name', $params['short_name']);
        $tpl->set('version', $params['version']);
        $tpl->set('use_web_space', $params['use_web_space']);
        $tpl->set('use_mvc', $params['use_mvc']);
        $tpl->set('use_css', $params['use_css']);
        //{{{create include files
        
            //Create Plugin class
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['short_name'].'Plugin.class.php', 'w');
            fwrite($f, '<'."?php\n");
            fwrite($f, $tpl->fetch('tpl/Plugin.tpl'));
            fwrite($f, '?'.">");
            fclose($f);
            //Create PluginInfo class
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['class_name'].'PluginInfo.class.php', 'w');
            fwrite($f, '<'."?php\n");
            fwrite($f, $tpl->fetch('tpl/Info.tpl'));
            fwrite($f, '?'.">");
            fclose($f);
            //Create PluginDescriptor class
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['class_name'].'PluginDescriptor.class.php', 'w');
            fwrite($f, '<'."?php\n");
            fwrite($f, $tpl->fetch('tpl/Descriptor.tpl'));
            fwrite($f, '?'.">");
            fclose($f);
            
            //{{{ Create MVC
            if ($params['use_web_space'] && $params['use_mvc']) {
                //Create Controler class
                $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['class_name'].'.class.php', 'w');
                fwrite($f, '<'."?php\n");
                fwrite($f, $tpl->fetch('tpl/Controler.tpl'));
                fwrite($f, '?'.">");
                fclose($f);
                //Create Actions class
                $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['class_name'].'Actions.class.php', 'w');
                fwrite($f, '<'."?php\n");
                fwrite($f, $tpl->fetch('tpl/Actions.tpl'));
                fwrite($f, '?'.">");
                fclose($f);
                //Create Views class
                $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/include/'.$params['class_name'].'Views.class.php', 'w');
                fwrite($f, '<'."?php\n");
                fwrite($f, $tpl->fetch('tpl/Views.tpl'));
                fwrite($f, '?'.">");
                fclose($f);
            }
            //}}}
        //}}}
        
        //Create style.css
        if ($params['use_web_space']) {
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www/index.php', 'w');
            fwrite($f, '<'."?php\n");
            if ($params['use_mvc']) {
                fwrite($f, $tpl->fetch('tpl/index_mvc.tpl'));
            } else {
                fwrite($f, ' echo "Hello !"; ');
            }
            fwrite($f, "\n".'?'.">");
            fclose($f);
            if ($params['use_css']) {
                $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/www/themes/default/css/style.css', 'w');
                fclose($f);
            }
        }
        
        //create install/uninstal sql scripts
        if ($params['create_db']) {
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/db/install.sql', 'w');
            fwrite($f, $params['install']);
            fclose($f);
            $f = fopen($GLOBALS['sys_pluginsroot'].'/'.$params['short_name'].'/db/uninstall.sql', 'w');
            fwrite($f, $params['uninstall']);
            fclose($f);
        }
    }
    // }}}
    
    
}


?>
