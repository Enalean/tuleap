<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * salomePlugin
 */
require_once('common/plugin/Plugin.class.php');

$GLOBALS['disable_soap']=true;

class salomePlugin extends Plugin {
	
	function salomePlugin($id) {
		$this->Plugin($id);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('service_public_areas', 'service_public_areas', false);
        $this->_addHook('service_admin_pages', 'service_admin_pages', false);
        $this->_addHook('service_is_used', 'serviceSalomeSwitching', false);
        $this->_addHook('register_project_creation', 'projectCreation', false);
        $this->_addHook('project_admin_edition', 'projectEdition', false);
        $this->_addHook('project_admin_add_user', 'projectAddUser', false);
        $this->_addHook('project_admin_remove_user', 'projectRemoveUser', false);
        $this->_addHook('project_admin_remove_user_from_project_ugroups', 'projectRemoveUserUGroups', false);
        $this->_addHook('project_admin_ugroup_creation', 'projectCreateUGroup', false);
        $this->_addHook('project_admin_ugroup_edition', 'projectEditUGroup', false);
        $this->_addHook('project_admin_ugroup_deletion', 'projectDeleteUGroup', false);
        $this->_addHook('project_admin_change_user_permissions', 'projectChangeUserPermissions', false);
        $this->_addHook('anonymous_access_to_script_allowed', 'anonymous_access_to_script_allowed', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'salomePluginInfo')) {
            require_once('salomePluginInfo.class.php');
            $this->pluginInfo =& new salomePluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function service_public_areas($params) {
        if ($params['project']->usesService('salome')) {
            $params['areas'][] = '<a href="/plugins/salome/?group_id='. $params['project']->getId() .'">' .
                '<img src="'. $this->getThemePath() .'/images/ic/tests.png" />&nbsp;' .
                $GLOBALS['Language']->getText('plugin_salome', 'title') .': '.
                $GLOBALS['Language']->getText('plugin_salome', 'service_desc_key') .
                '</a>';
        }
    }
        
    function service_admin_pages($params) {
        if ($params['project']->usesService('salome')) {
            $params['admin_pages'][] = '<a href="/plugins/salome/?action=admin&amp;group_id='. $params['project']->getId() .'">' .
                $GLOBALS['Language']->getText('plugin_salome', 'service_lbl_key') .' - '. 
                $GLOBALS['Language']->getText('plugin_salome', 'toolbar_admin') .
                '</a>';
        }              
    }
    
    /** 
     * Salomé must allow anonymous access to the codebase script that rewrites jnlp and config files
     * because they are read by the Java client (no cookie, no session)
     */
    function anonymous_access_to_script_allowed($params) {
	if (strcmp(substr($params['script_name'],0,21),'/plugins/salome/c.php') == 0) {
            $params['anonymous_allowed']=true;
        }
    }

    /**
     * Create the salome project corresponding to the codendi group $group_id,
     * with the name $name and description $description.
     * This function will link the codendi project with the salome project (DB table CONFIG)
     * 
     * Warning: this function does not do anything with user and user groups.
     * For a complete project setup, please use the function projectCreation
     * 
     * @param int $group_id the codendi group ID of the project
     * @return boolean the ID of the salome project just created, or false if the creation failed
     */
    function _createProject($group_id) {
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $group_name = $group->getUnixName();
        $group_description = $group->getDescription();
        
        $spm =& SalomeTMFProjectManager::instance();
        $project_id = $spm->createSalomeProject($group_id, $group_name, $group_description);
        return $project_id;
    }
    
    /**
     * Create the user $codendi_username in the salome DB and return the salome ID of the user
     * If the user already exist in the salome DB, just return its ID.
     *
     * @param Object $controler the controler of this plugin
     * @param string $codendi_username the Codendi login
     * @return int the ID of the user in the salome database, or 0 if the user couldn't have been created
     */
    function _createUser($codendi_username) {
        require_once('SalomeTMFUserManager.class.php');
        
        $salome_user_id = $this->_getSalomeUserID($codendi_username);
        if (! $salome_user_id) {
            // create the salome user
            $sum =& SalomeTMFUserManager::instance();
            $salome_user_id = $sum->createSalomeUser($codendi_username);
        }
        return $salome_user_id;
    }
    
    /**
     * Create the salome groups corresponding with the codendi dynamic UGroup
     * project members and project admins for the project.
     * Set the default permissions for these groups
     * Add user $salome_user_id in these groups.
     * 
     * @param int $codendi_ugroup_id the ID of the codendi group
     */
    function _createDynamicUGroups($codendi_group_id) {
        require_once('SalomeTMFProjectManager.class.php');
        require_once('SalomeTMFGroupManager.class.php');
        
        $spm =& SalomeTMFProjectManager::instance();
        $salome_project = $spm->getSalomeProjectFromCodendiGroupID($codendi_group_id);
        if ($salome_project) {
            $salome_project_id = $salome_project->getID();
            $salome_admin_user_id = 0;
            
            $sgm =& SalomeTMFGroupManager::instance();
            
            // create a salome group for each ugroup of the project 
            // Warning: salome name of the group is the Codendi ugroup_id
            //  dynamic ugroups
            $result = db_query("SELECT * FROM ugroup WHERE group_id=100 ORDER BY ugroup_id");
            while ($row = db_fetch_array($result)) {
                if ($row['ugroup_id'] == 4 ||
                    $row['ugroup_id'] == 3) {
                            
                    if ($row['ugroup_id'] == 4) $default_permissions = 254;	// project admins (ugroup_id=4)
                    if ($row['ugroup_id'] == 3) $default_permissions = 182; // project members (ugroup_id=3)
                    $new_salome_group_id = $sgm->createSalomeGroup($salome_project_id, $row['ugroup_id'], util_translate_desc_ugroup($row['description']), $default_permissions);
                    if (! $new_salome_group_id) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_admgroup_not_created'));
                    } else {
                        // add the members of these ugroups in salome groups
                        $sql = ugroup_db_get_dynamic_members($row['ugroup_id'], null, $codendi_group_id);
                        $res = db_query($sql);
                        while ($codendi_member_row = db_fetch_array($res)) {
                            // create user if does not exist
                            $slm_member_id = $this->_createUser($codendi_member_row['user_name']);
                            // add the user in the group
                            $sgm->addUserInGroup($slm_member_id, $codendi_group_id, $row['ugroup_id']);
                            
                            // take the first project admin found (to add him in the Administrateur group later)
                            if ($row['ugroup_id'] == 4) {
                                $salome_admin_user_id = $slm_member_id;
                            }
                        }
                    }
                }
            }
            
            // add the user in the 'Administrateur' salome group
            if ($salome_admin_user_id != 0) {
                $this->_createSalomeAdminGroup($salome_project_id, $salome_admin_user_id);
            }
            
        } else {
            return false;    
        }
    }
    
    /**
     * Create the salome groups corresponding with the codendi static UGroup for the project.
     * Set the default permissions for these groups (0 == no permissions)
     * Add members of these ugroups as salome user in these salome groups.
     * 
     * @param int $salome_project_id the ID of the salome project
     * @param int $codendi_ugroup_id the ID of the codendi group (corresponding to the salome project id) 
     */
    function _createStaticUGroups($codendi_group_id) {
        require_once('SalomeTMFProjectManager.class.php');
        
        $spm =& SalomeTMFProjectManager::instance();
        $salome_project = $spm->getSalomeProjectFromCodendiGroupID($codendi_group_id);
        if ($salome_project) {
            $salome_project_id = $salome_project->getID();
            
            $sgm =& SalomeTMFGroupManager::instance();
        
            // static ugroups
            $result_ugroups = ugroup_db_get_existing_ugroups($codendi_group_id);
            if ($result_ugroups) {
                while ($ugroup = mysql_fetch_array($result_ugroups)) {
                    if (! $sgm->createSalomeGroup($salome_project_id, $ugroup['ugroup_id'], $ugroup['name'], 0)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_admgroup_not_created'));
                    }
                    // foreach member of this group, add them as member of the group in salome DB
                    $sql = ugroup_db_get_members($ugroup['ugroup_id']);
                    $result = db_query($sql);
                    while ($row = db_fetch_array($result)) {
                        $slm_member_id = $this->_createUser($row['user_name']);
                        // search for the salome group ID
                        $sgm->addUserInGroup($slm_member_id, $codendi_group_id, $ugroup['ugroup_id']);
                    }
                }
            }
        } else {
            return false;
        }
    }
    
    /**
     * Delete all the salome groups of the salome project $salome_project_id
     *
     * @param int $salome_project_id the ID of the salome project
     * @return false if the deletion failed
     */
    function _deleteAllGroups($salome_project_id) {
        require_once('SalomeTMFGroupManager.class.php');
        
        $sgm =& SalomeTMFGroupManager::instance();
        return $sgm->deleteAllSalomeGroups($salome_project_id);
    }
    
    /**
     * Create the salome admin group 'Administrateur', and set the user $salome_user_id as member of this group
     * 
     * @param int $salome_project_id the ID of the salome project
     * @param int $salome_admin_user_id the ID of the admin salome user
     * @return int the ID of the salome group newly created, or false if the creation failed
     */
    function _createSalomeAdminGroup($salome_project_id, $salome_admin_user_id) {
        // create group Administrator for this project
        $sgm =& SalomeTMFGroupManager::instance();
        $salome_admin_group_id = $sgm->createSalomeGroup($salome_project_id, "Administrateur", "Groupe des administrateurs du projet", "0000000254");
        if ($salome_admin_group_id) {
            // set salome user as project admin
            $sgm->addUserInSalomeGroup($salome_admin_user_id, $salome_admin_group_id);
        }
        return $salome_admin_group_id;
    }
    
    /**
     * Returns the ID of the Salome User corresponding to the codendi user $codendi_username
     * or 0 if the user doens't exist in the salome database
     *
     * @param string codendi_username the codendi username (login)
     * @return int the ID of the salome user, or 0 if it doesn't exist
     */
    function _getSalomeUserID($codendi_username) {
        require_once('SalomeTMFUserManager.class.php');
        $sum =& SalomeTMFUserManager::instance();
        $u = $sum->getSalomeUserFromCodendiUsername($codendi_username);
        if (! $u) {
            return 0;
        } else {
            return $u->getID();
        }
    }
    
    /**
     * Function called when a project is created 
     * If a project is created, the corresponding salome project
     * is also created, and set properly
     *
     * See https://partners.xrce.xerox.com/wiki/index.php?pagename=CodendiProjectCreation&action=edit&group_id=155
     * for further details about what is done during project creation.
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     *
     * @param array $params the parameters from the hook.
     */
    function projectCreation($params) {
        global $Language;
        
        require_once('SalomeTMFProjectManager.class.php');
        require_once('SalomeTMFGroupManager.class.php');
        require_once('SalomeTMFTrackerManager.class.php');
        require_once('SalomeTMFPluginsManager.class.php');
        require_once('PluginSalomeProjectdataDao.class.php');
        require_once('PluginSalomeConfigurationDao.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        
        if ($project->usesService('salome')) {
            // create salome project
            $project_id = $this->_createProject($group_id);
            if ($project_id) {
                $controler = new salome($this);
                
                // create user if does not exist, and set her as project administrator
                $salome_user_id = $this->_createUser(user_getname());
                
                // create dynamic ugroups (only project members and project admins)
                $this->_createDynamicUGroups($group_id);
                // create static ugroups
                $this->_createStaticUGroups($group_id);
                
                //Copy permissions
                $this->_copyPermissions($project->getTemplate(), $group_id, $params['ugroupsMapping']);
                
                //Copy salome tracker
                $stm = SalomeTMFTrackerManager::instance();
                $template_tracker = $stm->getSalomeTracker($project->getTemplate());
                if ($template_tracker) {
                    $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($controler));
                    $salome_dao->create($group_id, 
                                $params['trackerMapping'][$template_tracker->getCodendiTrackerID()],
                                $params['reportMapping'][$template_tracker->getCodendiReportID()],
                                $template_tracker->getSpecialField('environment_field'),
                                $template_tracker->getSpecialField('campaign_field'),
                                $template_tracker->getSpecialField('family_field'),
                                $template_tracker->getSpecialField('suite_field'),
                                $template_tracker->getSpecialField('test_field'),
                                $template_tracker->getSpecialField('action_field'),
                                $template_tracker->getSpecialField('execution_field'),
                                $template_tracker->getSpecialField('dataset_field'));
                }
                
                //Copy plugins settings
                $pm = new SalomeTMFPluginsManager($controler);
                $pm->setPlugins($pm->getActivatedPlugins($project->getTemplate()), $group_id);
                
                //Copy Options settings
                $dao = new PluginSalomeConfigurationDao(CodendiDataAccess::instance());
                $dao->updateOption($group_id, 'WithICAL',         ($this->getConfigurationOption($project->getTemplate(), 'WithICAL') ? 1 : 0));
                $dao->updateOption($group_id, 'LockOnTestExec',   ($this->getConfigurationOption($project->getTemplate(), 'LockOnTestExec') ? 1 : 0));
                $dao->updateOption($group_id, 'LockExecutedTest', ($this->getConfigurationOption($project->getTemplate(), 'LockExecutedTest') ? 1 : 0));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_project_not_created'));
            }
        }
        
    }
    
    /**
     * Copy permissions from the project $from to the project $to
     */
    protected function _copyPermissions($from, $to, $ugroup_mapping) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance(new Salome($this)));
        
        // dynamic ugroups
        $result = db_query("SELECT * FROM ugroup WHERE group_id=100 ORDER BY ugroup_id");
        while ($row = db_fetch_array($result)) {
            // we only keep project admins (ugroup_id=4) and project members (ugroup_id=3) for dynamic ugroups
            if ($row['ugroup_id'] == 4 || $row['ugroup_id'] == 3) {
                if ($salome_dar = $salome_dao->getPermissions($from, $row['ugroup_id'])) {
                    if ($perms = $salome_dar->getRow()) {
                        $salome_dao->setPermissions($to, $row['ugroup_id'], $perms['permission']);
                    }
                }
            }
        }
        // static ugroups
        $result_ugroups = ugroup_db_get_existing_ugroups($from);
        if ($result_ugroups) {
            while ($row = db_fetch_array($result_ugroups)) {
                if ($row['ugroup_id'] > 100) { //Don't copy dynamic permissions when inheriting from project 100
                    if ($salome_dar = $salome_dao->getPermissions($from, $row['ugroup_id'])) {
                        if ($perms = $salome_dar->getRow()) {
                            $salome_dao->setPermissions($to, $ugroup_mapping[$row['ugroup_id']], $perms['permission']);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Function called when a project is edited 
     * If a project is edited, the corresponding salome project
     * is updated too (name and description)
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     *
     * @param array $params the parameters from the hook.
     */
    function projectEdition($params) {
        require_once('SalomeTMFProjectManager.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $pm = ProjectManager::instance();
            $group = $pm->getProject($group_id);
            $group_name = $group->getUnixName();
            $group_description = $group->getDescription();
            
            // update salome project
            $spm =& SalomeTMFProjectManager::instance();
            if ( ! $spm->updateSalomeProject($group_id, $group_name, $group_description)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_edition_project_not_updated'));
            }
        }
    }
    
    /**
     * Function called when a user is added to a project 
     * If a user is added to a project, the corresponding salome user
     * is also added to the corresponding salome group.
     * If the salome user doesn't exist, it will be created before.
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['user_id'] = the ID of the Codendi user
     *
     * @param array $params the parameters from the hook.
     */
    function projectAddUser($params) {
        require_once('SalomeTMFGroupManager.class.php');
        
        global $Language;
        
        $group_id = $params['group_id'];
        $user_id = $params['user_id'];
        
        $user = UserManager::instance()->getUserById($user_id);
        
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome') && $user->isMember($group_id)) {
            // create user if does not exist
            $salome_user_id = $this->_createUser($user->getName());
            // add user in salome group 'project member'
            $sgm =& SalomeTMFGroupManager::instance();
            $ok = $sgm->addUserInGroup($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_MEMBERS']);
            if (! $ok) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_usergroup_not_created'));
            }
        }
    }
    
    /**
     * Function called when a user is removed from a project 
     * If a user is removed from a project, the corresponding salome user
     * is also removed from the corresponding salome group.
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['user_id'] = the ID of the Codendi user
     *
     * @param array $params the parameters from the hook.
     */
    function projectRemoveUser($params) {
        require_once('SalomeTMFGroupManager.class.php');
        
        global $Language;
        
        $group_id = $params['group_id'];
        $user_id = $params['user_id'];
        
        $user = UserManager::instance()->getUserById($user_id);
        
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome') && ! $user->isMember($group_id)) {
            $salome_user_id = $this->_getSalomeUserID($user->getName());
            if ($salome_user_id != 0) {
                $sgm =& SalomeTMFGroupManager::instance();
                $ok = $sgm->removeUserInGroup($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_MEMBERS']);
                if (! $ok) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_usergroup_not_removed'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_usergroup_not_removed'));
            }
        }
    }
    
    /**
     * Function called when a user is removed from several ugroups 
     * (done when a user is removed from a project)
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['user_id'] = the ID of the Codendi user
     * $params['ugroup_id'] = the ID of the Codendi ugroup
     * $params['ugroups'] = the array of Codendi ugroup ID
     *
     * @param array $params the parameters from the hook.
     */
    function projectRemoveUserUGroups($params) {
        require_once('SalomeTMFGroupManager.class.php');
        
        global $Language;
        
        $group_id = $params['group_id'];
        $user_id = $params['user_id'];
        $ugroups = $params['ugroups'];
        
        $user = UserManager::instance()->getUserById($user_id);
        
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome') && ! $user->isMember($group_id)) {
            $salome_user_id = $this->_getSalomeUserID($user->getName());
            if ($salome_user_id != 0) {
                foreach ($ugroups as $ugroup_id) {
                    // remove user in salome group 'project member'
                    $sgm =& SalomeTMFGroupManager::instance();
                    $ok = $sgm->removeUserInGroup($salome_user_id, $group_id, $ugroup_id);
                    if (! $ok) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_usergroup_not_removed'));
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_usergroup_not_removed'));
            }
        }
    }
    
    /**
     * Function called when a ugroup is created
     * If a ugroup is created, a salome group will be created too
     * The name of the salome group is the Codendi ugroup_id,
     * The description of the salome group is the name and the description of the codendi ugroup
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['ugroup_id'] = the ID of the Codendi ugroup
     *
     * @param array $params the parameters from the hook.
     */
    function projectCreateUGroup($params) {
        require_once('SalomeTMFGroupManager.class.php');
        require_once('SalomeTMFProjectManager.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $spm =& SalomeTMFProjectManager::instance();
            $salome_project = $spm->getSalomeProjectFromCodendiGroupID($group_id);
            if ( ! $salome_project) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_ugroup_not_created'));
                return false;
            } else {
                $ugroup_id = $params['ugroup_id'];
                
                $res = ugroup_db_get_ugroup($ugroup_id);
                $ugroup_name = db_result($res,0,'name');
                $ugroup_desc = db_result($res,0,'description');
                // create salome group with no permissions (0)
                $sgm =& SalomeTMFGroupManager::instance();
                $salome_group_id = $sgm->createSalomeGroup($salome_project->getID(), $ugroup_id, $ugroup_name.' '.$ugroup_desc, 0);
                return $salome_group_id;
            }
        }
    }
    
    /**
     * Function called when a ugroup is edited
     * If a ugroup is edited, the corresponding salome group will be updated too
     * (name and description).
     * If users are removed and/or added, it will be done in salome too
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['ugroup_id'] = the ID of the Codendi ugroup
     * $params['ugroup_name'] = the name of the Codendi ugroup
     * $params['ugroup_desc'] = the description of the Codendi ugroup
     * $params['pick_list'] = the array of the Codendi user ID members of the group
     *
     * @param array $params the parameters from the hook.
     */
    function projectEditUGroup($params) {
        require_once('SalomeTMFGroupManager.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $ugroup_id = $params['ugroup_id'];
            $ugroup_name = $params['ugroup_name'];
            $ugroup_desc = $params['ugroup_desc'];
            $pick_list = $params['pick_list'];
            
            $sgm =& SalomeTMFGroupManager::instance();
            // update the name and description
            if (! $sgm->updateSalomeGroup($group_id, $ugroup_id, $ugroup_name.' '.$ugroup_desc)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_ugroup_not_created'));
                return false;
            } else {
                // delete all the user of this group
                $sgm->removeAllUserInGroup($group_id, $ugroup_id);
                // update the members of this group
                $user_count = count($pick_list);
                for ($i=0; $i<$user_count; $i++) {
                    // if user doesn't exist in salome DB, we create it, otherwise, we just get the salome user ID
                    $salome_user_id = $this->_createUser(user_getname($pick_list[$i]));
                    // set the user as member of the group
                    $sgm->addUserInGroup($salome_user_id, $group_id, $ugroup_id);
                }
            }
            return true;
        }
    }
    
    /**
     * Function called when a ugroup is deleted
     * If a ugroup is deleted, the corresponding salome group will be deleted too,
     * as well as the user/group associations.
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['ugroup_id'] = the ID of the Codendi ugroup
     *
     * @param array $params the parameters from the hook.
     */
    function projectDeleteUGroup($params) {
        require_once('SalomeTMFGroupManager.class.php');
        require_once('SalomeTMFProjectManager.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $spm =& SalomeTMFProjectManager::instance();
            $salome_project = $spm->getSalomeProjectFromCodendiGroupID($group_id);
            if ( ! $salome_project) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_ugroup_not_deleted'));
                return false;
            } else {
                $ugroup_id = $params['ugroup_id'];
                
                $sgm =& SalomeTMFGroupManager::instance();
                if (! $sgm->deleteSalomeGroup($salome_project->getID(), $ugroup_id)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_admin_ugroup_not_deleted'));
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
    
    /**
     * Function called when the user permissions is changed
     * This function treats only the 'project admin' changes.
     * If a user is added as project admin, the same will be done in salome.
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the Codendi group
     * $params['user_id'] = the ID of the Codendi user
     * $params['user_permissions'] = the array of permissions for the user in this group
     *                             = array('admin_flages' => ...,
     *                                     'svn_flags' => ...,
     *                                     ...
     *                               )
     *
     * @param array $params the parameters from the hook.
     */
    function projectChangeUserPermissions($params) {
        require_once('SalomeTMFGroupManager.class.php');
        
        $group_id = $params['group_id'];
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $user_id = $params['user_id'];
            $user = UserManager::instance()->getUserById($user_id);
            $user_permissions = $params['user_permissions'];
            $user_admin_flag = $user_permissions['admin_flags'];
            
            $salome_user_id = $this->_getSalomeUserID($user->getName());
            
            if ($salome_user_id != 0) {
                $sgm =& SalomeTMFGroupManager::instance();
                if ($user_admin_flag == 'A') {
                    // don't add it if user is already a member
                    if (! $sgm->isSalomeUserMemberOf($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_ADMIN'])) {
                        $sgm->addUserInGroup($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_ADMIN']);
                    }
                } else {
                    // don't remove if user is not a member of the group
                    if ($sgm->isSalomeUserMemberOf($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_ADMIN'])) {
                        $sgm->removeUserInGroup($salome_user_id, $group_id, $GLOBALS['UGROUP_PROJECT_ADMIN']);
                    }
                }
            }
        }
    }
    
    /**
     * Function called when a service is switched (from disabled to enabled or from enabled to disabled)
     *
     * The parameters from the hook are:
     * $params['group_id'] = the ID of the codendi project
     * $params['shortname'] = the shortname of the service disabled or enabled
     * $params['is_used'] = 1 if the service is used (enabled), false or not set otherwise  
     * 
     * @param array $params the parameters from the hook.
     */
    function serviceSalomeSwitching($params) {
        require_once('SalomeTMFProjectManager.class.php');
        
        $codendi_group_id = $params['group_id'];
        $spm =& SalomeTMFProjectManager::instance();
        $salome_project = $spm->getSalomeProjectFromCodendiGroupID($codendi_group_id);
                
        if ($params['shortname'] == 'salome') {
            if (isset($params['is_used']) && $params['is_used']) {
                // the salome service is now active for this project
                if (! $salome_project) {
                    $salome_project_id = $this->_createProject($codendi_group_id);
                } else {
                    $salome_project_id = $salome_project->getID();
                }
                
                // recreate all the groups, with the associations user/group 
                // dynamic ugroups
                $this->_createDynamicUGroups($codendi_group_id);
                // static ugroups
                $this->_createStaticUGroups($codendi_group_id);                
                
            } else {
                // else salome service is not active : we delete all the groups, with all the associations user/group
                if ($salome_project) {
                    $salome_project_id = $salome_project->getID();
                    $this->_deleteAllGroups($salome_project_id);
                }
            }
        }
    }
    
	/**
     * Function called when this plugin is set as available or unavailable
     *
     * @param boolean $available true if the plugin is available, false if unavailable
     */
    function setAvailable($available) {
    	require_once('SalomeTMFProjectManager.class.php');
    	
        $spm =& SalomeTMFProjectManager::instance();
        	
        $pm = ProjectManager::instance();
        $gf = new GroupFactory();
        $res_groups = $gf->getAllGroups();
        while ($res_group = db_fetch_array($res_groups)) {
        	$codendi_group_id = $res_group['group_id'];
        	$salome_project = $spm->getSalomeProjectFromCodendiGroupID($codendi_group_id);
        		
        	if ($available) {
	       		$project = $pm->getProject($codendi_group_id);
	       		if ($project->usesService('salome')) {
	       			if (! $salome_project) {
		       			$salome_project_id = $this->_createProject($codendi_group_id);
		            } else {
		                $salome_project_id = $salome_project->getID();
		            }
		                
		            // recreate all the groups, with the associations user/group 
		            // dynamic ugroups
		            $this->_createDynamicUGroups($codendi_group_id);
		            // static ugroups
		            $this->_createStaticUGroups($codendi_group_id);
		                
	     		}
        	} else {
		   		if ($salome_project) {
		   			$salome_project_id = $salome_project->getID();
		           	$this->_deleteAllGroups($salome_project_id);
		   		}
    		}
        }
    }
    
    /**
     * Check if Salome plugin can be made available if 
     * - the Salome database connection is ok
     *  
     * @return boolean true if connection to Salome database is possible, false otherwise 
     */
	public function canBeMadeAvailable() {
		require_once('salome.class.php');
		try {
			$controler = new salome($this);
			$sda = new SalomeDataAccess($controler);
		} catch (DataAccessException $dae) {
			$etc_root = $this->getPluginEtcRoot();
        	$config_file = $etc_root . '/' . $controler->getProperty('salome_db_config_file');
        	$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_plugin_cannotbemadeavailable', array($config_file)));
			return false;
        }
        return true;
    }
    
    /**
     * Function executed after plugin installation
     * Just display a message to warn the siteadmin that he has to configure the salome database configuration file  
     */
	public function postInstall() {
		require_once('salome.class.php');
		
		$controler = new salome($this);
		$etc_root = $this->getPluginEtcRoot();
        $config_file = $etc_root . '/' . $controler->getProperty('salome_db_config_file');
        
		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_salome','salome_plugin_postinstall', array($config_file)));
    }
    
    /**
     * Return true if the salome tracker is well configured, false otherwise
     *
     * The Salome tracker is considered as well configured if:
     * - the salome service is active for this codendi project AND
     * - there is a salome project for this codendi project
     * - there is a codendi tracker set as "salome tracker"
     * - there is a codendi report set as "salome report"
     * - all the "salome fields" are defined
     * - all the "salome fields" are different from the others
     *
     * @param int $codendi_group_id the ID of the codendi project we want to test
     * @return boolean true if the salome tracker is well configured, false otherwise
     */
    function isSalomeTrackerWellConfigured($codendi_group_id) {
        require_once('SalomeTMFProjectManager.class.php');
        require_once('SalomeTMFTrackerManager.class.php');
        
        $group_id = $codendi_group_id;
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->usesService('salome')) {
            $spm =& SalomeTMFProjectManager::instance();
            $salome_project = $spm->getSalomeProjectFromCodendiGroupID($group_id);
            if ($salome_project) {
                // there is a codendi tracker set as "salome tracker"
                $group_artifact_id = null;
                // there is a codendi report set as "salome report"
                $report_id = null;
                // all the "salome fields" are defined
                $environment_field = 0;
                $campaign_field = 0;
                $family_field = 0;
                $suite_field = 0;
                $test_field = 0;
                $action_field = 0;
                $execution_field = 0;
                $dataset_field = 0;
                
                $stm =& SalomeTMFTrackerManager::instance();
                $salome_tracker = $stm->getSalomeTracker($codendi_group_id);
                
                if ($salome_tracker) {
                    $group_artifact_id = $salome_tracker->getCodendiTrackerID();
                    if ($group_artifact_id == null) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_tracker_notfound'));
                        return false;
                    }
                    $report_id = $salome_tracker->getCodendiReportID();
                    if ($report_id == null) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_report_notfound'));
                        return false;
                    }
                    $environment_field = $salome_tracker->getSpecialField('environment_field');
                    $campaign_field = $salome_tracker->getSpecialField('campaign_field');
                    $family_field = $salome_tracker->getSpecialField('family_field');
                    $suite_field = $salome_tracker->getSpecialField('suite_field');
                    $test_field = $salome_tracker->getSpecialField('test_field');
                    $action_field = $salome_tracker->getSpecialField('action_field');
                    $execution_field = $salome_tracker->getSpecialField('execution_field');
                    $dataset_field = $salome_tracker->getSpecialField('dataset_field');
                    
                    if ($environment_field == '0' || $campaign_field == '0' || $family_field == '0' || $suite_field == '0' ||
                        $test_field == '0' || $action_field == '0' || $execution_field == '0' || $dataset_field == '0') {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_fields_notfound'));
                        return false;
                    }
                    
                    // all the "salome fields" are different from the others
                    $fields = array();
                    $fields[] = $environment_field;
                    $fields[] = $campaign_field;
                    $fields[] = $family_field;
                    $fields[] = $suite_field;
                    $fields[] = $test_field;
                    $fields[] = $action_field;
                    $fields[] = $execution_field;
                    $fields[] = $dataset_field;
                    for ($i=0; $i < count($fields); $i++) {
                        for ($j=$i+1; $j < count($fields); $j++) {
                            if ($fields[$i] == $fields[$j]) {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_same_fields', array($fields[$i])));
                                return false;
                            }
                        }
                    }
                    
                    // the field priority exists
                    /*$at = new ArtifactType($project, $group_artifact_id);
                    $aff = new ArtifactfieldFactory($at);
                    $priority_field = $aff->getFieldFromName('priority');
                    var_dump($priority_field);*/                    
                    
                    return true;
                    
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_tracker_notfound'));
                    return false;
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_project_notfound'));
                return false;
            }
        } else {
            return true;
        }
    }
    
    /**
     * getConfigurationOption
     *
     * @param int $group_id  
     * @param string $option  the name of the option to retrive
     */
    function getConfigurationOption($group_id, $option) {
        require_once('PluginSalomeConfigurationDao.class.php');
        $dao = new PluginSalomeConfigurationDao(CodendiDataAccess::instance());
        $dar = $dao->searchOption($group_id, $option);
        if (!$dar || !($row = $dar->getRow())) {
            return false;
        }
        return $row['value'];
    }
    
    /**
     * getPluginsList
     *
     * @param int $group_id  
     */
    function getPluginsList($group_id) {
        require_once('SalomeTMFPluginsManager.class.php');
        require_once('salome.class.php');
        $spm = new SalomeTMFPluginsManager(new salome($this));
        return array_merge(array('core'), $spm->getActivatedPlugins($group_id));
    }
    
    function process() {
        require_once('salome.class.php');
        $controler =& new salome($this);
        $controler->process();
    }
}

?>
