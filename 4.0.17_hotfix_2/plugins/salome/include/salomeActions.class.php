<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * salomeActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginSalomeProjectdataDao.class.php');
require_once('PluginSalomeGroupDao.class.php');
require_once('SalomeTMFProxyManager.class.php');
require_once('SalomeTMFPermissions.class.php');
require_once('PluginSalomeConfigurationDao.class.php');

class salomeActions extends Actions {
    
    function salomeActions(&$controler, $view=null) {
        $this->Actions($controler);
    }
    
    // {{{ Actions
    
    function checkSalomeTrackerConfiguration() {
        $request =& HTTPRequest::instance();
        $controler = $this->getcontroler();
        $plugin = $controler->getPlugin();
        if (! $plugin->isSalomeTrackerWellConfigured($request->get('group_id'))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','tracker_not_configured'));
        }
    }
    
    function updateAdminOptions() {
        $request = HTTPRequest::instance();
        if ($group_id = $request->getValidated('group_id', 'GroupId')) {
            $dao = new PluginSalomeConfigurationDao(CodendiDataAccess::instance());
            if (!$dao->updateOption($group_id, 'WithICAL',         ($request->get('WithICAL') ? 1 : 0)) ||
                !$dao->updateOption($group_id, 'LockOnTestExec',   ($request->get('LockOnTestExec') ? 1 : 0)) ||
                !$dao->updateOption($group_id, 'LockExecutedTest', ($request->get('LockExecutedTest') ? 1 : 0))) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_unable_update'));
            } else {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_salome','admin_options_updated'));
            }
        }
    }
    
    function updateAdminTrackerInfo() {
        $request =& HTTPRequest::instance();
        if ($request->exist('tracker_id') && $request->exist('report_id') &&
            $request->exist('field_environment') && $request->exist('field_campaign') && 
            $request->exist('field_family') && $request->exist('field_suite') && 
            $request->exist('field_test') && $request->exist('field_action') && 
            $request->exist('field_execution') && $request->exist('field_dataset')) {
        
            $dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->getControler()));
            if ($dao->updateByGroupId($request->get('group_id'), 
                                      $request->get('tracker_id'), 
                                      $request->get('report_id'),
                                      $request->get('field_environment'), 
                                      $request->get('field_campaign'),
                                      $request->get('field_family'),
                                      $request->get('field_suite'),
                                      $request->get('field_test'),
                                      $request->get('field_action'),
                                      $request->get('field_execution'),
                                      $request->get('field_dataset'))) {
                $controler = $this->getcontroler();
                $plugin = $controler->getPlugin();
                if (! $plugin->isSalomeTrackerWellConfigured($request->get('group_id'))) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','tracker_not_configured'));
                } else {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_salome','admin_tracker_updated'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_unable_update'));
            }
        }
    }
    
    function updateAdminPlugins() {
        $request =& HTTPRequest::instance();
        $plugins_on = $request->get('plugins');
        if (!$plugins_on) {
            $plugins_on = array();
        }
        $plugins_to_activate = array();
        foreach ($plugins_on as $plugin_name => $activation) {
            $plugins_to_activate[] = $plugin_name;
        }

        $spm = new SalomeTMFPluginsManager($this->getControler());
        $ok = $spm->setPlugins($plugins_to_activate, $request->get('group_id'));
        if ($ok) {
            array_unshift($plugins_to_activate, 'core');
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_salome','admin_plugins_updated', array(implode(', ', $plugins_to_activate))), CODENDI_PURIFIER_LIGHT);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_unable_update'));
        }
    }
    
    function updateAdminPermissions() {
        $request =& HTTPRequest::instance();
        if ($request->exist('group_id') && $request->exist('ugroup_id')) {
            
            $perm = SalomeTMFPermissions::getPermissionFromCheckbox($request->get('test_suite_add'), 
                                                                    $request->get('test_suite_modify'), 
                                                                    $request->get('test_suite_delete'), 
                                                                    $request->get('test_campaign_add'), 
                                                                    $request->get('test_campaign_modify'), 
                                                                    $request->get('test_campaign_delete'), 
                                                                    $request->get('test_campaign_execute'));
            $int_perm = $perm->getIntPermissions();
            
            if ($perm->isAllowedValue()) {
                $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->getControler()));
                if ($salome_dao->setPermissions($request->get('group_id'), $request->get('ugroup_id'), $int_perm)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_salome','admin_permissions_updated'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_unable_update'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_permissions_notallowed'));
            }
        }
    }
    
    function updateProxy() {
    	$request =& HTTPRequest::instance();
        
		$active = ($request->get('enable_proxy') == 'on');
       	$spm =& SalomeTMFProxyManager::instance();
       	$salome_proxy = $spm->getSalomeProxyFromCodendiUserID(user_getid());
      	if ($salome_proxy) {
      		$ok = $spm->updateSalomeProxy(user_getid(), $request->get('proxy'), $request->get('proxy_user'), $request->get('proxy_password'), $active);
      	} else {
			$ok = $spm->createSalomeProxy(user_getid(), $request->get('proxy'), $request->get('proxy_user'), $request->get('proxy_password'), $active);      		
      	}
        
        if ($ok) {
         	$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_salome','admin_proxy_updated'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','admin_unable_update'));
        }
        
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        $user->setPreference('plugin_salome_use_soap_'. $request->get('group_id'), ($request->get('use_soap') ? 1 : 0));
        $GLOBALS['HTML']->redirect('/plugins/salome/?group_id='.(int)$request->get('group_id'));
    }
    // }}}
    
    
}


?>
