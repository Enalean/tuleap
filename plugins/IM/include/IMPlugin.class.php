<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Dashboard\User\UserDashboardController;

 define("IM_DEBUG_ON",true,true);
 define("IM_DEBUG_OFF",false,true);
require_once('common/plugin/Plugin.class.php');
require_once('www/include/user.php');
require_once('common/user/UserHelper.class.php');

require_once('JabbexFactory.class.php');

class IMPlugin extends Plugin
{

    var $debug;
    /**
     * last data remove ====>for testing script
     */
    var $last_im_datas=array();
    var $last_im_datas_remove=array();

    function __construct($id,$debug=IM_DEBUG_OFF) {
        parent::__construct($id);

        $this->addHook('javascript_file', 'jsFile', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('approve_pending_project', 'projectIsApproved', false);
        $this->addHook('project_is_suspended_or_pending', 'projectIsSuspendedOrPending', false);
        $this->addHook('project_is_deleted', 'projectIsDeleted', false);
        $this->addHook('project_is_active', 'projectIsActive', false);
        $this->addHook('project_admin_add_user', 'projectAddUser', false);
        $this->addHook('project_admin_remove_user', 'projectRemoveUser', false);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('account_pi_entry', 'im_process_display_user_jabber_id_in_account', false);
        $this->addHook('user_home_pi_entry', 'im_process_display_user_jabber_id', false);
        $this->addHook('user_preferences_appearance', 'user_preferences_appearance', false);
        $this->addHook('update_user_preferences_appearance', 'update_user_preferences_appearance', false);
        $this->addHook('project_export_entry', 'provide_exportable_items', false);
        $this->addHook('get_available_reference_natures', 'getAvailableReferenceNatures', false);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);

        $this->debug=$debug;

    }

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'IMPluginInfo')) {
            require_once('IMPluginInfo.class.php');
            $this->pluginInfo = new IMPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return 'IM';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e809';
    }

    /**
     * Functions used for "tests"
     */
    /**
     * @return string the last room name created
     */
    function get_last_muc_room_name () {
		return $this->last_im_datas["muc"];
	}
	/**
     * @return string the last room name created
     */
	function get_last_muc_room_name_delete () {
		return $this->last_im_datas_remove['muc'];
	}
	function get_last_grp_name () {
		return $this->last_im_datas["grp"];
	}
	function get_last_muc_room_name_locked () {
		return $this->last_im_datas["name_last_muc_locked"];
	}
	function get_last_muc_room_name_unlocked () {
		return $this->last_im_datas["name_last_muc_unlocked"];
	}
	/**
	 * @return string information about a member added in once muc
	 */
	function get_last_member_of_once_muc_room () {
		return $this->last_im_datas["names_last_member_in_muc"];
	}

	/**
	 * @return string information about a member added in once muc
	 */
	function get_last_remove_member_of_once_muc_room () {
		return $this->last_im_datas["names_remove_member_from_muc"];
	}
    /**
     * End functions for tests.
     */

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the IM plugin pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    /**
     * Returns an instance of jabdex
     * @return Jabbex object class for im processing
     */
	function _get_im_object() {
		return JabbexFactory::getJabbexInstance();
	}

	function _this_muc_exist($unix_project_name) {
		require_once("IM.class.php");
        require_once("IMDao.class.php");
		require_once('IMDataAccess.class.php');
        $controler = new IM($this);
		$dao    = new IMDao(IMDataAccess::instance($controler));
		$roomID = $dao->get_room_id_by_unix_name ($unix_project_name);
		return (isset($roomID)&&$roomID);
	}
	/**
	 * to get pictures path
	 * @return string directory path of icons
	 */
	 function get_icon_path() {
		$themes_dir = $this->getThemePath();
		$icon_path = $themes_dir.'/images/icons/';
		return $icon_path;
	}

    protected $_cache_presence = array();
	/**
	 *
	 * send status for a geven user
	 * @return string a string mapping of a img html balise .
	 * @param string $jid the user jabber identification .
	 *
	 */
    function _get_presence_status ($jid) {
        $presence = $this->getPresence($jid);
        $purifier = Codendi_HTMLPurifier::instance();
        return '<img src="'.$purifier->purify($presence['icon']).'" title="'.$purifier->purify($presence['status']).'"  alt="'.$purifier->purify($presence['status']).'" border="0" height="16" width="16" style="vertical-align:top">';
    }

    function getPresence($jid) {
        if (!isset($this->_cache_presence[$jid])) {
           if($this->_get_im_object()){
	            $status=$this->_get_im_object()->user_status($jid);
	            $img_src='';
	            $img_title='';

	            $custom_msg = ($status["message"]) ? $status["message"] : '';

	            switch($status["status"]){
	                case "dnd":
	                    $img_title= $GLOBALS['Language']->getText('plugin_im_status','dnd');
	                    $img_src=$this->get_icon_path ().'busy.gif';
	                    break;
	                case "away";
		                $img_title = $GLOBALS['Language']->getText('plugin_im_status','away');
		                $img_src=$this->get_icon_path ().'away.gif';
	                    break;
	                case "chat":
	                    $img_title = $GLOBALS['Language']->getText('plugin_im_status','chat');
	                    $img_src=$this->get_icon_path ().'on_line.gif';
	                    break;
	                case "xa":
	                    $img_title = $GLOBALS['Language']->getText('plugin_im_status','xa');
	                    $img_src=$this->get_icon_path ().'away.gif';
	                    break;
	                case "unavailable":
	                    $img_title = $GLOBALS['Language']->getText('plugin_im_status','unavailable');
	                    $img_src=$this->get_icon_path ().'off_line.gif';
	                    break;
	                case "available":
	                    $img_title = $GLOBALS['Language']->getText('plugin_im_status','available');
	                    $img_src=$this->get_icon_path ().'on_line.gif';
	                    break;
	                case "forbidden":
	                    $img_title = $GLOBALS['Language']->getText('plugin_im_status','forbidden');
	                    $img_src=$this->get_icon_path ().'off_line.gif';
	                    break;
	            }

	            if(!empty($custom_msg)){
	            	$img_title = ($img_title == $custom_msg) ? $img_title : ($img_title.' - '.$custom_msg);
	            }
                $this->_cache_presence[$jid] = array(
                    'icon' => $img_src,
                    'status' => $img_title
                );
            }
        }
        return $this->_cache_presence[$jid];
    }


    function instance() {
        static $_plugin_instance;
        if (!$_plugin_instance) {
            $_plugin_instance = new IMPlugin($this->id);
        }
        return $_plugin_instance;
    }

	/**
	 * This function is called when a (or several) project(s) is (are) approved.
     * Action: create a muc room and a shared group for the corresponding projects
     *
	 * @param array $params : contains the array of group_id
	 */
	function projectIsApproved($params) {
		$this->muc_room_creation($params);
		$this->create_im_shared_group($params);
		if ($this->debug) {
			echo "\nIM: projectIsApproved for projects: ".$params['group_id']."<br>";
		}
	}

    /**
	 * This function is called when a project is supsended or pending
     * Action: lock the muc room
     *
	 * @param array $params contains the group_id ($params['group_id'])
	 */
	function projectIsSuspendedOrPending($params) {
		$this->im_lock_muc_room($params);
	}

    /**
	 * This function is called when the event "project_is_deleted" is called
     * Action: lock the muc room
     *
	 * @param array $param : contains the group_id ($params['group_id'])
	 *
	 * Before, we deleted the MUC room, but now, we only lock it,
	 * because we want to be able to go back to Active status (that will unlock the MUC Room).
	 *
	 */
	function projectIsDeleted($params) {
		$this->im_lock_muc_room($params);
	}

	/**
	 * This function is called when the project is set to active
     * Action: unlock the muc room
     *
	 * @param array $params contains the group_id ($params['group_id'])
	 */
	 function projectIsActive($params) {
		$this->im_unlock_muc_room($params);
	}

	/**
	 * This function is called when a user is added into a project
     * Action: add user to muc room
     *
	 * @param array $params contains the group_id, the user_id and the user_unix_name
	 */
	function projectAddUser($params) {
		$this->im_muc_add_member($params);
	}


	/**
	 * This function is called when a user is removed from a project
     * Action: remove user from the muc room
     *
	 * @param array $params contains the group_id and the user_id
	 */
	function projectRemoveUser($params) {
		$this->im_muc_remove_member($params);
	}

	/**
	 * Shared group creation
	 * @param array params data from the shared Codendi event
	 */
	 function create_im_shared_group ($params) {
		$group_ids = $params['group_id'];
		$group_ids=explode(',',$group_ids);

        $project_manager = $this->getProjectManager();

        foreach($group_ids as $project_id){
	        $project = $project_manager->getProject($project_id);
	        $unix_project_name = $project->getUnixName();
	        $project_name = $project->getPublicName();

            try{
				if($this->_get_im_object()){
					$this->_get_im_object()->create_shared_group($unix_project_name,$project_name);
					$this->last_im_datas["grp"]=$project_name;
				}else{
					if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', 'IM object no available to create the shared group, '.$project_name);
					}
				}
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', $e->getMessage());
				}
			}
		}

	}
	/**
	 * Is called by "muc_room_creation ($params)" to create a muc room.
	 * @param array $params :contains the data which comes from the envent listened.
	 */
    function muc_room_creation($params) {
		$group_ids = $params['group_id'];
		$group_ids=explode(',',$group_ids);
		//var_dump($group_ids);

        $project_manager = $this->getProjectManager();
        $user_manager = $this->getUserManager();

		foreach($group_ids as $val){
	        $project = $project_manager->getProject($val);
	        $unix_group_name = $project->getUnixName();
	        $group_name=($project->getPublicName()?$project->getPublicName():$unix_group_name);
	        $group_description = $project->getDescription();
	        if(!(isset($group_description)&&$group_description!=null)){
				$group_description='No description';
			}
	        $project_members_ids = $project->getMembersId();
            $group_Owner_object = $user_manager->getUserById($project_members_ids[0]);
	        $group_Owner_name = $group_Owner_object->getName();
            if(!$this->_this_muc_exist ($unix_group_name)){
				try{
					if($this->_get_im_object()){
						$this->_get_im_object()->create_muc_room($unix_group_name, $group_name, $group_description, $group_Owner_name);
						$this->last_im_datas["muc"]=$group_name;
					}else{
						if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
						$GLOBALS['Response']->addFeedback('error', 'IM object no available to create the shared group, '.$group_name);
						}
					}
				} catch(Exception $e){
					if(!$this->debug){
						$GLOBALS['Response']->addFeedback('error', 'MUC creation: '.$e->getMessage());
					}
				}
			}
		}
	}

    /**
     * to lock an MUC room
     * @param array params:contains the data which comes from the envent listened (group_id her ).
     */
    public function im_lock_muc_room($params){
        $project_manager = $this->getProjectManager();
        $project_id = $params['group_id'];
        $project = $project_manager->getProject($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();

		if($this->_this_muc_exist ($unix_project_name)){
			try{
				if($this->_get_im_object()){
					$this->_get_im_object()->lock_muc_room($unix_project_name);
					$this->last_im_datas["name_last_muc_locked"]=$unix_project_name;
				}else{
						if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
						$GLOBALS['Response']->addFeedback('error', 'IM object no available to create the shared group, '.$project_name);
						}
					}
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', $e->getMessage());
				}
			}
		}else{
			//if muc not exist i do nothing about IM
		}

     }

   /**
    * unlock a MUC room created and locked by "lock_muc_room($unix_project_name)".
    * @param array $params :contains the data which comes from the envent listened.
    */
   	function im_unlock_muc_room($params) {
        $project_manager = $this->getProjectManager();
		$project_id = $params['group_id'];
        $project = $project_manager->getProject($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();

		if($this->_this_muc_exist ($unix_project_name)){
			try{
				if($this->_get_im_object()){
					$this->_get_im_object()->unlock_muc_room($unix_project_name);
					$this->last_im_datas["name_last_muc_unlocked"]=$unix_project_name;
				}else{
						if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
						$GLOBALS['Response']->addFeedback('error', 'IM object no available to unlock muc, '.$project_name);
						}
					}
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
						$GLOBALS['Response']->addFeedback('error', $e->getMessage());
					}
			}
			}

 		}

 	/**
 	 * function called in im_process_delete_muc_room($params) to delete an muc room.
 	 * @param array $params :contains the data which comes from the envent listened.
 	 */
 	function im_delete_muc_room($params) {
		$project_manager = $this->getProjectManager();
		$project_id = $params['group_id'];
        $project = $project_manager->getProject($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();
        if($this->_this_muc_exist ($unix_project_name)){
	        try{
	        $this->_get_im_object()->delete_muc_room($unix_project_name);
	        $this->last_im_datas_remove['muc']=$unix_project_name;
	        }catch(Exception $e){
	        	if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', $e->getMessage());
				}
	        }
        }
	}

	/**
 	 * function called in im_process_muc_add_member($params) to add a member in a given muc room.
 	 * @param array $params :contains the data which comes from the envent listened.
 	 */
	function im_muc_add_member($params) {
		$user_unix_name=$params['user_unix_name'];

        $project_manager = $this->getProjectManager();
		$group_id =$params['group_id'];
		$project = $project_manager->getProject($group_id);
        $group_name = $project->getUnixName();
        if($this->_this_muc_exist ($group_name)){
			try{
				$this->_get_im_object()->muc_add_member($group_name, $user_unix_name);
				$this->last_im_datas["names_last_member_in_muc"]=$user_unix_name." is added in the muc :".$group_name;
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', $e->getMessage());
				}
			}
        }

	}
	/**
	 * to remove a member on a muc
	 * @param array $params:contains the data which comes from the envent listened.
	 */
	public function im_muc_remove_member($params){
		//group infos
		$group_id =$params['group_id'];
        $project_manager = $this->getProjectManager();
		$project = $project_manager->getProject($group_id);
        $unix_group_name = $project->getUnixName();
        //user infos
        $user_id=$params['user_id'];

        $user = $this->getUserManager()->getUserById($user_id);
        $user_unix_name=$user->getUserName();
        if($this->_this_muc_exist ($unix_group_name)){
	        try{
				$this->_get_im_object()->muc_remove_member($unix_group_name,$user_unix_name);
				$this->last_im_datas["names_remove_member_from_muc"]=$user_unix_name." is remove from the muc :".$unix_group_name;
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', $e->getMessage());
				}
			}
        }
	}

	/**
	 * for hook administration :display an URL to access IM administration.
	 * @param array $params:contains the data which comes from the envent listened.
	 */
    function siteAdminHooks($params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_im', 'link_im_admin_title'),
            'href'  => $this->getPluginPath() . '/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

 	 function im_process_display_jabber_id($eParams) {
	    global $Language;
		$plugin= & IMPlugin::instance() ;
		$pm=$plugin->_getPluginManager();
		$entry_label['jid']='';
		$entry_value['jid']='';
		if(!$pm->isPluginAvailable($plugin)){
			//nothing to do actualy
		}else{
			$im_object=$this->_get_im_object();
			$jabberConf=$im_object->get_server_conf();
			$server_dns=$jabberConf['server_dns'];

			$user_login = $this->getUserManager()->getUserById($eParams['user_id'])->getName();

			$jid_value=$user_login.'@'.$server_dns;
			$label=$GLOBALS['Language']->getText('plugin_im','im_user_login');
			//var_dump($label);
			$entry_label['jid'] = $label;
            if ( ! $this->getUserManager()->getCurrentUser()->getPreference('plugin_im_hide_users_presence')) {
                $entry_value['jid'] = $this->_get_presence_status ($jid_value) .' ';
            }
            $entry_value['jid'] .= $jid_value;
			$entry_change['jid']="";
			$eParams['entry_label'] = $entry_label;
            $eParams['entry_value'] =$entry_value;
            $eParams['entry_change'] =$entry_change;
		}
	}
	/**
	 * to display an user's jabber identification JID in web interface personnal page
	 * @param array $eParams:contains the data which comes from the envent listened.
	 */
 	function im_process_display_user_jabber_id ($eParams) {
		$this->im_process_display_jabber_id ($eParams);
	}

	/**
	 * to display an user's jabber identification JID in web interface developper profil
	 * @param array $eParams:contains the data which comes from the envent listened.
	 */
	function im_process_display_user_jabber_id_in_account (array $params) {
            $im_object  = $this->_get_im_object();
            $jabberConf = $im_object->get_server_conf();
            $value      = $params['user']->getName().'@'.$jabberConf['server_dns'];
            if ( ! $params['user']->getPreference('plugin_im_hide_users_presence')) {
                $value = $this->_get_presence_status ($value) .' ';
            }
            $params['user_info'][] = new User_ImmutableInfoPresenter(
                $GLOBALS['Language']->getText('plugin_im','im_user_login'),
                $value
            );
	}

    public function uninstall()
    {
        $this->removeOrphanWidgets(array('plugin_im_myroster'));
    }

        function user_preferences_appearance($params) {
            $input = '<label class="checkbox" for="plugin_im_display_users_presence">';
            $input .= '<input type="hidden" name="plugin_im_display_users_presence" value="0" />';
            $input .= '<input type="checkbox" id="plugin_im_display_users_presence" name="plugin_im_display_users_presence" value="1" ';
            if ( ! $this->getUserManager()->getCurrentUser()->getPreference('plugin_im_hide_users_presence')) {
                $input .= 'checked="checked"';
            }
            $input .= '/>';
            $input .= $GLOBALS['Language']->getText('plugin_im', 'user_appearance_pref_display_users_presence');
            $input .= '</label>';

            $params['preferences'][] = array(
                'name'  => $GLOBALS['Language']->getText('plugin_im', 'user_appearance_pref_name'),
                'value' => $input
            );
        }

        /**
         * Only display presence for active (non restricted users)
         *
         * @param $params
         */
        function update_user_preferences_appearance($params) {
            if (!$this->getUserManager()->getCurrentUser()->isRestricted() && $params['request']->exist('plugin_im_display_users_presence')) {
                if ($params['request']->get('plugin_im_display_users_presence')) {
                    $this->getUserManager()->getCurrentUser()->delPreference('plugin_im_hide_users_presence');
                } else {
                    $this->getUserManager()->getCurrentUser()->setPreference('plugin_im_hide_users_presence', '1');
                }
            }
        }

        function provide_exportable_items($exportable_items) {
            $exportable_items['labels']['im_muc_logs']            = $GLOBALS['Language']->getText('plugin_im', 'muc_logs_title');
            $exportable_items['data_export_links']['im_muc_logs'] = '/plugins/IM/?log_start_date=&log_end_date=&action=muc_logs&type=export&group_id='.$exportable_items['group_id'];
        }

    function jsFile($params) {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
        	$GLOBALS['HTML']->includeCalendarScripts();
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
        }
    }

    function getAvailableReferenceNatures($params) {
        $im_plugin_reference_natures = array(
            'im_chat'  => array('keyword' => 'chat', 'label' => $GLOBALS['Language']->getText('plugin_im', 'reference_chat_nature_key')));
        $params['natures'] = array_merge($params['natures'], $im_plugin_reference_natures);
    }

	function process() {
        require_once('IM.class.php');
        $controler = new IM($this);
        $controler->process();
    }


    function getUserManager() {
        return UserManager::instance();
    }
    function getProjectManager() {
        return ProjectManager::instance();
    }

}
