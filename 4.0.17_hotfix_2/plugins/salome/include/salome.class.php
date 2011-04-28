<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * $Id$
 *
 * salome */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('salomeViews.class.php');
require_once('salomeActions.class.php');

class salome extends Controler {

	var $plugin;

	function salome(&$plugin) {
		$this->plugin =& $plugin;
	}

	function getProperty($name) {
		$info =& $this->plugin->getPluginInfo();
		return $info->getPropertyValueForName($name);
	}

	function request() {
		$request =& HTTPRequest::instance();
		$vgi = new Valid_GroupId();
		$vgi->required();
		if ($request->valid($vgi)) {
			$group_id = $request->get('group_id');
			$pm = ProjectManager::instance();
            $project = $pm->getProject($group_id);
			if ($project->usesService('salome')) {

				switch($request->get('action')) {
					case 'updateAdminOptions':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->action = 'updateAdminOptions';
							$this->view = 'adminOptions';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'updateAdminTrackerInfo':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->action = 'updateAdminTrackerInfo';
							$this->view = 'adminTracker';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'updateAdminPlugins':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->action = 'updateAdminPlugins';
							$this->view = 'adminPlugins';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'updateAdminPermissions':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->action = 'updateAdminPermissions';
							$this->view = 'adminPermissionsUGroup';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'updateProxy':
						$this->action = 'updateProxy';
						$this->view = 'proxy';
						break;
					case 'proxy':
						$this->view = 'proxy';
						break;
					case 'admin':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'admin';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'adminOptions':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'adminOptions';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'adminTracker':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'adminTracker';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'adminPlugins':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'adminPlugins';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'adminPermissions':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'adminPermissions';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'adminPermissionsUGroup':
						if (user_ismember($request->get('group_id'),'A')) {
							$this->view = 'adminPermissionsUGroup';
						} else {
							$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
							$this->view = 'salome';
						}
						break;
					case 'jnlp':
						$this->view = 'jnlp';
						break;
					default:
						$this->action = 'checkSalomeTrackerConfiguration';
						$this->view = 'salome';
						break;
				}
			} else {
				$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','service_not_used'));
			}
		} else {
			$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','group_id_missing'));
		}


	}

	function getPlugin() {
		return $this->plugin;
	}

}

?>