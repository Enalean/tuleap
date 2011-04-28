<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * salomeViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');
require_once('PluginSalomeProjectdataDao.class.php');
require_once('SalomeDao.class.php');
require_once('SalomeDataAccess.class.php');
        
require_once('SalomeTMFPluginsManager.class.php');
require_once('SalomeTMFProxyManager.class.php');
require_once('SalomeTMFPermissions.class.php');

class salomeViews extends Views {
    
    function salomeViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function header() {
        $request =& HTTPRequest::instance();
        $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $request->get('group_id'), 'toptab' => 'salome'));
        if (user_ismember($request->get('group_id'),'A')) {
            echo '<b><a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=admin">'. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</a> | </b>';
        }
        echo $this->_getHelp();
    }
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_salome','title');
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/documentation/user_guide/html/'.UserManager::instance()->getCurrentUser()->getLocale().'/TestManagerPlugin.html'.$section.'\');">'.$GLOBALS['Language']->getText('global', 'help').'</a></b>';
    }
    function _intro() {
        echo file_get_contents($GLOBALS['Language']->getContent('intro', null, 'salome'));
    }
    /**
    * @ovveride
    */
    function display($view = '') {
        if ($view != 'jnlp') $this->header();
        if(!empty($view)) $this->$view();
        if ($view != 'jnlp') $this->footer();
    }
    
    // {{{ Views
    function salome() {
        echo '<h2>'.$this->_getTitle().'</h2>';
        $this->_intro();
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        
        $controler = $this->getControler();
        $plugin = $controler->getPlugin();
        if ($plugin->isSalomeTrackerWellConfigured($request->get('group_id'))) {
            
            // check if the user has some permissions for salome
            $user = UserManager::instance()->getCurrentUser();
            $ugroups = $user->getUgroups($request->get('group_id'), null);
            $hasPermissionsToAccessSalome = false;
            $i = 0;
            while (! $hasPermissionsToAccessSalome && $i < count($ugroups)) {
                $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->getControler()));
                $salome_dar = $salome_dao->getPermissions($request->get('group_id'), $ugroups[$i]);
                if ($salome_dar && $salome_dar->valid()) {
                    $row = $salome_dar->current();
                    $permissions = $row['permission'];
                    $hasPermissionsToAccessSalome = ($permissions != 0);    // 0 means no permissions (no access to salome)
                }
                $i++;
            }
            
            echo '<form>';
            if (! $hasPermissionsToAccessSalome) {
            		echo ' <input type="button" disabled="disabled" value="'.$GLOBALS['Language']->getText('plugin_salome','launch_salome').'" />';
            } else {
                ?>
                <style type="text/css">
                a.salome_button {
                    background:url(<?php echo $plugin->getThemePath(); ?>/images/launch.png); 
                    margin-left:20px;
                    display:block; 
                    width:248px; 
                    height:68px; 
                    padding:20px 60px 0px 0px; 
                    text-align:right; 
                    color:#666;
                    text-decoration:none;
                }
                a.salome_button:hover {
                    background:url(<?php echo $plugin->getThemePath(); ?>/images/launch-hover.png); 
                }
                a.salome_button span {
                    display:block;
                }
                a.salome_button span strong {
                    font-size:1.4em; 
                    font-weight:bold;
                    text-decoration:underline;
                }
                a.salome_button span em {
                    display:block;
                    font-size:0.8em; 
                    text-align:right; 
                    padding-top:4px;
                    font-style:normal;
                }
                </style><?php
                echo '<br /><a class="salome_button" href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=jnlp">';
      			//echo ' <input type="button" value="'.$GLOBALS['Language']->getText('plugin_salome','launch_salome').'" />';
                echo '<span>';
                echo '<strong>'. $GLOBALS['Language']->getText('plugin_salome','launch_salome') .'</strong>';
                
                if ($user->getPreference('plugin_salome_use_soap_'. $group_id)) {
                    $spm =& SalomeTMFProxyManager::instance();
                    $salome_proxy = $spm->getSalomeProxyFromCodendiUserID(user_getid());
                    if ($salome_proxy && $salome_proxy->isActive()) {
                        $network_prefs = $GLOBALS['Language']->getText('plugin_salome', 'net_soap_and_proxy');
                    } else {
                        $network_prefs = $GLOBALS['Language']->getText('plugin_salome', 'net_soap_no_proxy');
                    }
                } else {
                    $network_prefs = $GLOBALS['Language']->getText('plugin_salome', 'net_jdbc');
                }
                
	            echo '<em>'. $network_prefs .'</em>';
                echo '</span>';
	            echo '</a>'; 
                if (!$GLOBALS['disable_soap']) {
			echo '<div style="font-size:0.8em; text-align:right;width:300px;margin-left:20px;"><a href="/plugins/salome/?group_id='. $group_id .'&amp;action=proxy">'. $GLOBALS['Language']->getText('plugin_salome', 'net_change') .'</a></div>';
		}
            }
            echo '</form>';
            
        } else {
            echo '<h3>'.$GLOBALS['Language']->getText('plugin_salome','launch_salome').'</h3>';
        }
    }
    
    function jnlp() {
        $group_artifact_id = $this->_getTrackerId();
        include('SalomeWithCodendi.jnlp.php');
    }
    
    function proxy() {
        if ($GLOBALS['disable_soap']) { return; }

    	$request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_proxy') .'</h2>';
        
        $group_id = $request->get('group_id');
        
        $um = UserManager::instance();
        $current_user = $um->getCurrentUser();
        
        $spm =& SalomeTMFProxyManager::instance();
        $salome_proxy = $spm->getSalomeProxyFromCodendiUserID($current_user->getId());
        if ($salome_proxy) {
        	if ($salome_proxy->isActive()) {
        		$disabled = '';
        		$checked = 'checked="checked"';
        	} else {
        		$disabled = 'disabled="disabled"';
        		$checked = '';
        	}
        	$proxy = $salome_proxy->getProxy();
        	$proxy_user = $salome_proxy->getProxyUser();
        	$proxy_password = $salome_proxy->getProxyPassword();
        } else {
        	$disabled = 'disabled="disabled"';
        	$checked = '';
        	$proxy = '';
        	$proxy_user = '';
        	$proxy_password = ''; 
        }
        
        echo '<form action="?group_id='. $request->get('group_id') .'&amp;action=updateProxy" method="POST">';
        echo '<fieldset>';
        $soap_checked = $current_user->getPreference('plugin_salome_use_soap_'. $group_id) ? 'checked="checked"' : '';
        echo '<legend><input type="checkbox" '.$soap_checked.' name="use_soap" id="use_soap" />'. 'Use Web Services with Salomé' .'</legend>';
        echo '<p>'. "Un petit blabla sur les avantages et les inconvénients d'utiliser SOAP (lenteur, config entreprise, ...)";
        echo '</p>';
        echo '</fieldset>';
        
        echo '<fieldset>';
        echo '<legend><input type="checkbox" '.$checked.' name="enable_proxy" id="enable_proxy" />'.$GLOBALS['Language']->getText('plugin_salome','enable_proxy').'</legend>';
        echo '<p>';
        echo ' <label for="proxy" id="proxy_label">'.$GLOBALS['Language']->getText('plugin_salome','proxy').'</label>';
        echo ' <input name="proxy" id="proxy" '.$disabled.' type="text" value="'.$proxy.'" />';
        echo '</p>';
        echo '<p>';
        echo ' <label for="proxy_user" id="proxy_user_label">'.$GLOBALS['Language']->getText('plugin_salome','proxy_user').'</label>';
        echo ' <input name="proxy_user" id="proxy_user" '.$disabled.' type="text" value="'.$proxy_user.'" />';
        echo '</p>';
        echo '<p>';
        echo ' <label for="proxy_password" id="proxy_password_label">'.$GLOBALS['Language']->getText('plugin_salome','proxy_password').'</label>';
        echo ' <input name="proxy_password" id="proxy_password" '.$disabled.' type="password" value="'.$proxy_password.'" />';
        echo '</p>';
        echo '</fieldset>';
        echo '<br />';
        echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</form>';
        
        // Javascript to implement action on checkboxes
        echo '<script type="text/javascript" src="salome_proxy.js"></script>'."\n";
        
    }
    
    function admin() {
        $request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
        
        echo '<h3><a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=adminTracker">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_title').'</a></h3>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_tracker_desc');
        echo '<h3><a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=adminPermissions">'.$GLOBALS['Language']->getText('plugin_salome','admin_permissions_title').'</a></h3>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_permissions_desc');
        echo '<h3><a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=adminPlugins">'.$GLOBALS['Language']->getText('plugin_salome','admin_plugins_title').'</a></h3>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_plugins_desc');
        echo '<h3><a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;action=adminOptions">'.$GLOBALS['Language']->getText('plugin_salome','admin_options_title').'</a></h3>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_options_desc');
        
    }
    
    function adminOptions() {
        $request =& HTTPRequest::instance();
        if ($group_id = $request->getValidated('group_id', 'GroupId')) {
            echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
            
            echo '<form action="?group_id='. $request->get('group_id') .'&amp;action=updateAdminOptions" method="POST">';
            
            $controler = $this->getcontroler();
            $plugin = $controler->getPlugin();
            
            $checked = $plugin->getConfigurationOption($group_id, 'WithICAL') ? 'checked="checked"' : '';
            echo '<p><input type="checkbox" name="WithICAL" id="field_WithICAL" '. $checked .' /><label for="field_WithICAL">'. $GLOBALS['Language']->getText('plugin_salome','admin_options_include_ical') . '</label></p>';
            
            $checked = $plugin->getConfigurationOption($group_id, 'LockOnTestExec') ? 'checked="checked"' : '';
            echo '<p><input type="checkbox" name="LockOnTestExec" id="field_LockOnTestExec" '. $checked .' /><label for="field_LockOnTestExec">'. $GLOBALS['Language']->getText('plugin_salome','admin_options_lock_plan') .'</label></p>';
            
            $checked = $plugin->getConfigurationOption($group_id, 'LockExecutedTest') ? 'checked="checked"' : '';
            echo '<p><input type="checkbox" name="LockExecutedTest" id="field_LockExecutedTest" '. $checked .' /><label for="field_LockExecutedTest">'. $GLOBALS['Language']->getText('plugin_salome','admin_options_lock_test') .'</label></p>';
            
            echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</form>';
        }
    }
    
    function adminTracker() {
        $request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
        
        echo '<form action="?group_id='. $request->get('group_id') .'&amp;action=updateAdminTrackerInfo" method="POST">';
        
        $group_id = $request->get('group_id');
        
        $pm = ProjectManager::instance();
        $atf = new ArtifactTypeFactory($pm->getProject($group_id));
        $at_arr = $atf->getArtifactTypes();
        $actual_tracker_id = $this->_getTrackerId();
        
        $this->_displayTrackerFieldSet($actual_tracker_id, $at_arr);
        
        $arf = new ArtifactReportFactory();
        $actual_report_id = $this->_getReportId();
        $reports = $arf->getReports($actual_tracker_id, 100);  // 100 is the user_id
        
        $this->_displayReportFieldSet($actual_report_id, $reports);
        
        
        $pm = ProjectManager::instance();
        $grp = $pm->getProject($request->get('group_id'));
        if (!$grp || !is_object($grp)) {
            return false;
        } elseif ($grp->isError()) {
            return false;
        }
        $at = new ArtifactType($grp, $actual_tracker_id);
        if (!$at || !is_object($at)) {
            return false;
        } elseif (! $at->userCanView()) {
            return false;
        } elseif ($at->isError()) {
            return false;
        }
        $afsf = new ArtifactFieldSetFactory($at);
        $aff = new ArtifactFieldFactory($at);
        $fields = $aff->getAllUsedFields();
        
        
        $salome_special_fields = $this->_getSalomeSpecialFields();
        $this->_displaySpecialFieldsFieldSet($salome_special_fields, $fields);
        
        
        echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</form>';
        
        // Javascript to link the two select box
        $this->_displayJsTrackerListObserver($request->get('group_id'));
        
    }
    
    function _displayTrackerFieldSet($actual_tracker_id, $arr_artifact_types) {
        echo '<fieldset>';
        echo '<legend>'.$GLOBALS['Language']->getText('plugin_salome', 'admin_tracker_mapping').'</legend>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_tracker');
        // Tracker select box
        echo ' <select name="tracker_id" id="tracker_list">';
        echo '<option value="0">--</option>';
        foreach($arr_artifact_types as $t) {
            $selected = $actual_tracker_id == $t->getID() ? 'selected="selected"' : '';
            echo '<option value="'. $t->getID() .'" '. $selected .'>'. $t->getName() .'</option>';
        }
        echo '</select>';
        echo '</fieldset>';
    }
    
    function _displayReportFieldSet($actual_report_id, $reports) {
        echo '<fieldset>';
        echo '<legend>'.$GLOBALS['Language']->getText('plugin_salome', 'admin_report_mapping').'</legend>';
        echo $GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_report');
        // Report select box
        echo '<span id="report_list">';
        echo ' <select name="report_id" id="report_list">';
        foreach ($reports as $report) {
            $selected = $actual_report_id == $report->getID() ? 'selected="selected"' : '';
            echo '<option value="'. $report->getID() .'" '. $selected .'>'. $report->getName() .'</option>';
        }
        echo '</select>';
        echo '</span>';
        echo '</fieldset>';
    }
    
    function _displaySpecialFieldsFieldSet($salome_special_fields, $fields) {
        
        echo '<fieldset>';
        echo '<legend>'.$GLOBALS['Language']->getText('plugin_salome', 'admin_field_mapping').'</legend>';
        
        $this->_displayEnvironmentSelect($salome_special_fields['environment_field'], $fields);
        $this->_displayCampaignSelect($salome_special_fields['campaign_field'], $fields);
        $this->_displayFamilySelect($salome_special_fields['family_field'], $fields);
        $this->_displaySuiteSelect($salome_special_fields['suite_field'], $fields);
        $this->_displayTestSelect($salome_special_fields['test_field'], $fields);
        $this->_displayActionSelect($salome_special_fields['action_field'], $fields);
        $this->_displayExecutionSelect($salome_special_fields['execution_field'], $fields);
        $this->_displayDatasetSelect($salome_special_fields['dataset_field'], $fields);
        
        echo '</fieldset>';
    }
    
    function _displayEnvironmentSelect($actual_field_environment, $fields) {
        echo '<label id="field_environment_label" for="field_environment">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_environment_field').'</label>';
        echo '<span id="field_environment_list">';
        // Environment Field select box
        echo '<select name="field_environment">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_environment == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayCampaignSelect($actual_field_campaign, $fields) {
        echo '<label id="field_campaign_label" for="field_campaign">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_campaign_field').'</label>';
        echo '<span id="field_campaign_list">';
        // Campaign Field select box
        echo '<select name="field_campaign">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_campaign == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayFamilySelect($actual_field_family, $fields) {
        echo '<label id="field_family_label" for="field_family">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_family_field').'</label>';
        echo '<span id="field_family_list">';
        // Family Field select box
        echo '<select name="field_family">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_family == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displaySuiteSelect($actual_field_suite, $fields) {
        echo '<label id="field_suite_label" for="field_suite">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_suite_field').'</label>';
        echo '<span id="field_suite_list">';
        // Suite Field select box
        echo '<select name="field_suite">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_suite == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayTestSelect($actual_field_test, $fields) {
        echo '<label id="field_test_label" for="field_test">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_test_field').'</label>';
        echo '<span id="field_test_list">';
        // Test Field select box
        echo '<select name="field_test">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_test == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayActionSelect($actual_field_action, $fields) {
        echo '<label id="field_action_label" for="field_action">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_action_field').'</label>';
        echo '<span id="field_action_list">';
        // Action Field select box
        echo '<select name="field_action">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_action == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayExecutionSelect($actual_field_execution, $fields) {
        echo '<label id="field_execution_label" for="field_execution">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_execution_field').'</label>';
        echo '<span id="field_execution_list">';
        // Execution Field select box
        echo '<select name="field_execution">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_execution == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
        echo '<br />';
    }
    
    function _displayDatasetSelect($actual_field_dataset, $fields) {
        echo '<label id="field_dataset_label" for="field_dataset">'.$GLOBALS['Language']->getText('plugin_salome','admin_tracker_choose_dataset_field').'</label>';
        echo '<span id="field_dataset_list">';
        // Dataset Field select box
        echo '<select name="field_dataset">';
        echo '<option value="0">--</option>';
        foreach ($fields as $field_name => $field) {
            if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
                $selected = $actual_field_dataset == $field->getName() ? 'selected="selected"' : '';
                echo '<option value="'. $field->getName() .'" '. $selected .'>'. $field->getLabel() .'</option>';
            }
        }
        echo '</select>';
        echo '</span>';
    }
    
    function _displayJsTrackerListObserver($group_id) {
        echo '<script type="text/javascript">';
        echo "document.observe('dom:loaded', function() {
          		\$('tracker_list').observe('change', function() {  
              		new Ajax.Updater('report_list',   
                                     'salome_report_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_environment_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_environment',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_campaign_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_campaign',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_family_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_family',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_suite_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_suite',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_test_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_test',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_action_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_action',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_execution_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_execution',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                    new Ajax.Updater('field_dataset_list',   
                                     'salome_tracker_ajax.php',   
                                     { method: 'get',   
                                       parameters: { group_id: ".$group_id.",   
                                                     special_field: 'field_dataset',  
                                                     tracker_id: \$F('tracker_list') }  
                                     }  
                    );  
                });  
              });";
        echo "</script>";
    }
    
    function adminPlugins() {
        $request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
        
        $group_id = $request->get('group_id');
        $salome_plugins_manager = new SalomeTMFPluginsManager($this->getControler());
        $mode_base = 'j';
        $user = UserManager::instance()->getCurrentUser();
        $mode_base = $user->getPreference('plugin_salome_use_soap_'. $group_id) ? 's' : 'j';
        if ($GLOBALS['disable_soap']) {
            $salome_plugins_jdbc = $salome_plugins_manager->getAvailablePlugins('j');
            $salome_plugins = array_keys($salome_plugins_jdbc);
            sort($salome_plugins);
            echo '<form action="?group_id='. $request->get('group_id') .'&amp;action=updateAdminPlugins" method="POST">';
            echo $GLOBALS['Language']->getText('plugin_salome', 'admin_used_plugins');
            echo '<table>';
            echo '<thead><tr><th class="boxtitle">Plugin</th></tr></thead>';
            echo '<tbody>';
            $i = 0;
            foreach ($salome_plugins as $salome_plugin) {
                $p            = null;
                $p = $salome_plugins_jdbc[$salome_plugin];
                $disabled = ($p->getName() == 'core') ? 'disabled="disabled"' : '';
                $checked = $p->getName() == 'core' || $salome_plugins_manager->isPluginActivated($p->getName(), $request->get('group_id')) ? 'checked="checked"' : '';
                echo '<tr class="'. html_get_alt_row_color($i++) .'"><td>';
                echo '<input type="checkbox" name="plugins['. $p->getName() .']" '. $checked .' '.$disabled.' />'. $p->getName() .' <br />';
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</form>';
        } else {
          $salome_plugins_jdbc = $salome_plugins_manager->getAvailablePlugins('j');
          $salome_plugins_soap = $salome_plugins_manager->getAvailablePlugins('s');
          if ($salome_plugins_jdbc && $salome_plugins_soap) {
            $salome_plugins = array_unique(array_merge(array_keys($salome_plugins_jdbc), array_keys($salome_plugins_soap)));
            sort($salome_plugins);
            echo '<form action="?group_id='. $request->get('group_id') .'&amp;action=updateAdminPlugins" method="POST">';
            echo $GLOBALS['Language']->getText('plugin_salome', 'admin_used_plugins');
            echo '<table>';
            echo '<thead><tr><th class="boxtitle">Plugin</th><th class="boxtitle">jdbc</th><th class="boxtitle">soap</th></tr></thead>';
            echo '<tbody>';
            $i = 0;
            foreach ($salome_plugins as $salome_plugin) {
                $p            = null;
                $used_by_jdbc = false;
                $used_by_soap = false;
                if (isset($salome_plugins_jdbc[$salome_plugin])) {
                    $used_by_jdbc = true;
                    $p = $salome_plugins_jdbc[$salome_plugin];
                }
                if (isset($salome_plugins_soap[$salome_plugin])) {
                    $used_by_soap = true;
                    if (!$p) {
                        $p = $salome_plugins_soap[$salome_plugin];
                    }
                }
                $disabled = ($p->getName() == 'core') ? 'disabled="disabled"' : '';
                $checked = $p->getName() == 'core' || $salome_plugins_manager->isPluginActivated($p->getName(), $request->get('group_id')) ? 'checked="checked"' : '';
                echo '<tr class="'. html_get_alt_row_color($i++) .'"><td>';
                echo '<input type="checkbox" name="plugins['. $p->getName() .']" '. $checked .' '.$disabled.' />'. $p->getName() .' <br />';
                echo '</td><td>';
                echo $used_by_jdbc ? $GLOBALS['HTML']->getImage('ic/tick.png') : '&nbsp;';
                echo '</td><td>';
                echo $used_by_soap ? $GLOBALS['HTML']->getImage('ic/tick.png') : '&nbsp;';
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</form>';
          } else {
            if (!$salome_plugins_jdbc) {
                $mode_base = 'j';
            } else {
                $mode_base = 's';
            }
            $plugins_dir = $salome_plugins_manager->getPluginsDirectory($mode_base);
            echo '<p class="feedback_error">' . $GLOBALS['Language']->getText('plugin_salome', 'err_plugins_dir', array($plugins_dir)) . '</p>';
          }
        }
    }
    
    function adminPermissions() {
        $request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
        
        echo $GLOBALS['Language']->getText('plugin_salome','admin_permissions_ugroups') . '<br />';
        
        // dynamic ugroups
        $result = db_query("SELECT * FROM ugroup WHERE group_id=100 ORDER BY ugroup_id");
        while ($row = db_fetch_array($result)) {
            // we only keep project admins (ugroup_id=4) and project members (ugroup_id=3) for dynamic ugroups
            if ($row['ugroup_id'] == 4 ||
                $row['ugroup_id'] == 3) {
                echo '<a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;ugroup_id='.$row['ugroup_id'].'&amp;action=adminPermissionsUGroup">'.util_translate_name_ugroup($row['name']).'</a><br />';
            }
        }
        // static ugroups
        $result_ugroups = ugroup_db_get_existing_ugroups($request->get('group_id'));
        if ($result_ugroups) {
            while ($ugroup = mysql_fetch_array($result_ugroups)) {
                if ($ugroup['ugroup_id'] > 100) { //Don't set permissions for project 100
                    echo '<a href="/plugins/salome/?group_id='. $request->get('group_id') .'&amp;ugroup_id='.$ugroup['ugroup_id'].'&amp;action=adminPermissionsUGroup">'.$ugroup['name'].'</a><br />';
                }
            }
        }
        
    }
    
    function adminPermissionsUGroup() {
        $request =& HTTPRequest::instance();
        echo '<h2>'.$this->_getTitle().' - '. $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .'</h2>';
        
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->getControler()));
        $salome_dar = $salome_dao->getPermissions($request->get('group_id'), $request->get('ugroup_id'));
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $int_permissions = $row['permission'];
            
            $perm = new SalomeTMFPermissions($int_permissions);
            $checked_all_suite = ($perm->canAddSuite() && $perm->canModifySuite() && $perm->canDeleteSuite()) ? 'checked="checked"' : '';
            $checked_add_suite = $perm->canAddSuite() ? 'checked="checked"' : '';
            $checked_modify_suite = $perm->canModifySuite() ? 'checked="checked"' : '';
            $checked_delete_suite = $perm->canDeleteSuite() ? 'checked="checked"' : '';
            $checked_all_campaign = ($perm->canAddCampaign() && $perm->canModifyCampaign() && $perm->canDeleteCampaign() && $perm->canExecuteCampaign()) ? 'checked="checked"' : '';
            $checked_add_campaign = $perm->canAddCampaign() ? 'checked="checked"' : '';
            $checked_modify_campaign = $perm->canModifyCampaign() ? 'checked="checked"' : '';
            $checked_delete_campaign = $perm->canDeleteCampaign() ? 'checked="checked"' : '';
            $checked_execute_campaign = $perm->canExecuteCampaign() ? 'checked="checked"' : '';
            
            echo '<form action="?group_id='. $request->get('group_id') .'&amp;ugroup_id='. $request->get('ugroup_id') .'&amp;action=updateAdminPermissions" method="POST">';
            echo $GLOBALS['Language']->getText('plugin_salome','admin_permissions_ugroup', array(util_translate_name_ugroup(ugroup_get_name_from_id($request->get('ugroup_id')))));
            
            echo '<fieldset>';
            echo ' <legend>Test Suite</legend>';
            echo ' <input type="checkbox" '.$checked_all_suite.' id="test_suite_all" name="test_suite_all">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_all') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_add_suite.' id="test_suite_add" name="test_suite_add">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_add') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_modify_suite.' id="test_suite_modify" name="test_suite_modify">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_modify') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_delete_suite.' id="test_suite_delete" name="test_suite_delete">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_delete') . '</input><br />';
            echo '</fieldset>';
            
            echo '<fieldset>';
            echo ' <legend>Test Campaign</legend>';
            echo ' <input type="checkbox" '.$checked_all_campaign.' id="test_campaign_all" name="test_suite_all">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_all') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_add_campaign.' id="test_campaign_add" name="test_campaign_add">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_add') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_modify_campaign.' id="test_campaign_modify" name="test_campaign_modify">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_modify') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_delete_campaign.' id="test_campaign_delete" name="test_campaign_delete">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_delete') . '</input><br />';
            echo ' &nbsp;&nbsp;&nbsp;<input type="checkbox" '.$checked_execute_campaign.' id="test_campaign_execute" name="test_campaign_execute">' . $GLOBALS['Language']->getText('plugin_salome','admin_permissions_execute') . '</input><br />';
            echo '</fieldset>';
            
            
            echo '<br />';
            echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</form>';
            
            // Javascript to implement action on checkboxes
            echo '<script type="text/javascript" src="salome_permissions.js"></script>'."\n";
            
        } else {
            // feedback : unable to find this (u)group
            echo '<span class="feedback_error">'.$GLOBALS['Language']->getText('plugin_salome', 'salome_group_notfound', array(util_translate_name_ugroup(ugroup_get_name_from_id($request->get('ugroup_id'))))).'</span>';
        }
    }
    
    // }}}
    
    function _getTrackerId() {
        $group_artifact_id = null;
        $request =& HTTPRequest::instance();
        $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->getControler()));
        $salome_dar = $salome_dao->searchByGroupId($request->get('group_id'));
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $group_artifact_id = $row['group_artifact_id'];
        }
        return $group_artifact_id;
    }
    
    function _getReportId() {
        $report_id = null;
        $request =& HTTPRequest::instance();
        $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->getControler()));
        $salome_dar = $salome_dao->searchByGroupId($request->get('group_id'));
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $report_id = $row['report_id'];
        }
        return $report_id;
    }
    
    function _getSalomeSpecialFields() {
        $environment_field = null;
        $campaign_field = null;
        $family_field = null;
        $suite_field = null;
        $test_field = null;
        $action_field = null;
        $execution_field = null;
        $dataset_field = null;
    	$request =& HTTPRequest::instance();
        $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->getControler()));
        $salome_dar = $salome_dao->searchByGroupId($request->get('group_id'));
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $environment_field = $row['environment_field'];
            $campaign_field = $row['campaign_field'];
            $family_field = $row['family_field'];
            $suite_field = $row['suite_field'];
            $test_field = $row['test_field'];
            $action_field = $row['action_field'];
            $execution_field = $row['execution_field'];
            $dataset_field = $row['dataset_field'];
        }
        
        return array(
                'environment_field' => $environment_field,
                'campaign_field' => $campaign_field,
                'family_field' => $family_field,
                'suite_field' => $suite_field,
                'test_field' => $test_field,
                'action_field' => $action_field,
                'execution_field' => $execution_field,
                'dataset_field' => $dataset_field
            );
        
    }
    
}
?>
