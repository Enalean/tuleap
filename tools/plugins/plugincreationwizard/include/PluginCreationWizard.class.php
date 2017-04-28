<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * PluginCreationWizard
 */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginCreationWizardViews.class.php');
require_once('PluginCreationWizardActions.class.php');
class PluginCreationWizard extends Controler {

    function PluginCreationWizard() {
        HTTPRequest::instance()->checkUserIsSuperUser();
    }

    function request() {
        $request =& HTTPRequest::instance();
        $views    = array('introduction', 'descriptor', 'webspace',       'hooks', 'database', 'finish');

        if (!isset($_SESSION['PluginCreationWizard_view']) || $request->exist('cancel')) {
            $_SESSION['PluginCreationWizard_view']   = 0;
            $_SESSION['PluginCreationWizard_params'] = array();
        }
        if ($request->exist('next')) {
            $this->action = $views[$_SESSION['PluginCreationWizard_view']];
            $_SESSION['PluginCreationWizard_view']++;
        }
        if ($request->exist('back')) {
            $_SESSION['PluginCreationWizard_view']--;
        }
        if ($_SESSION['PluginCreationWizard_view'] < 0) {
            $_SESSION['PluginCreationWizard_view'] = 0;
        }
        if ($_SESSION['PluginCreationWizard_view'] >= count($views)) {
            $_SESSION['PluginCreationWizard_view'] = count($views) - 1;
        }
        $this->view = $views[$_SESSION['PluginCreationWizard_view']];


        if ($request->exist('goto') && (($key = array_search($request->get('goto'), $views)) !== false)) {
            $this->view                            = $request->get('goto');
            $_SESSION['PluginCreationWizard_view'] = $key;
        }

        if ($request->exist('finish')) {
            unset($_SESSION['PluginCreationWizard_view']);
            $this->action = 'create';
            $this->view   = 'end';
        }
    }
}


?>
