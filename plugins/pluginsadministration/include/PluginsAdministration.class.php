<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
                case 'available':
                case 'properties':
                case 'restrict':
                case 'ajax_projects':
                    $this->view = $request->get('view');
                    break;
                default:
                    $this->view = 'installed';
                    break;
            }
        } else {
            $this->view = 'installed';
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
                    if ($request->exist('confirm')) {
                        $this->action = 'install';
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

                case 'change_plugin_properties':
                    if ($request->exist('plugin_id')) {
                        $this->action = 'changePluginProperties';
                        $this->view   = 'properties';
                    }
                    break;
                case 'set-plugin-restriction':
                    $this->action = 'setPluginRestriction';
                    $this->view   = 'restrict';
                    break;
                case 'update-allowed-project-list':
                    $this->action = 'updateAllowedProjectList';
                    $this->view   = 'restrict';
                    break;
                default:
                    break;
            }
        }
    }
}
