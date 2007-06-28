<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PluginsAdministration
 */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginsAdministrationViews.class.php');
require_once('PluginsAdministrationActions.class.php');
class PluginsAdministration extends Controler {
    
    function PluginsAdministration() {
        session_require(array('group'=>'1','admin_flags'=>'A'));
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        
        if ($request->exist('view')) {
            switch ($request->get('view')) {
                case 'properties':
                case 'ajax_projects':
                    $this->view = $request->get('view');
                    break;
                default:
                    $this->view = 'browse';
                    break;
            }
        } else {
            $this->view = 'browse';
        }
        
        if ($request->exist('action')) {
            switch ($request->get('action')) {
                case 'available':
                    $this->action = 'available';
                    break;
                case 'unavailable':
                    $this->action = 'unavailable';
                    break;
                case 'install':
                    if ($request->exist('confirm')) { //The user confirm installation
                        $this->action = 'install';
                        $this->view   = 'postInstall';
                    } else {
                        if (!$request->exist('cancel')) { //The user has not see warning yet
                            $this->view   = 'confirmInstall';
                        }
                    }
                    break;
                case 'uninstall':
                    if ($request->exist('confirm')) { //The user confirm uninstallation
                        $this->action = 'uninstall';
                    } else {
                        if (!$request->exist('cancel')) { //The user has not see warning yet
                            $this->view   = 'confirmUninstall';
                        }
                    }
                    break;
                case 'update_priorities':
                    $this->action = 'updatePriorities';
                    break;
                case 'change_plugin_properties':
                    if ($request->exist('plugin_id')) {
                        $this->action = 'changePluginProperties';
                        $this->view   = 'properties';
                    }
                    break;
                default:
                    break;
            }
        }
    }
}


?>
