<?php
/**
 * 
 */
 define("IM_DEBUG_ON",true,true);
 define("IM_DEBUG_OFF",false,true);
require_once('common/plugin/Plugin.class.php');
require_once(dirname(__FILE__)."/jabbex_api/Jabbex.php");
require_once('www/include/user.php');
require_once('common/user/UserHelper.class.php');

class IMPlugin extends Plugin {
	/**
	 * get API Instant Messaging functions 
	 */
     var $im;
     /**
      * mapp current session
      */
     var $session;
     
     /**
      * icon path
      */
      var $iconsPath;
      
      
      var $debug;
      var $last_im_datas=array();
      
      /**
       * last data remove ====>for testing script
       */
       var $last_im_datas_remove=array();
	/**
	 * @param $id 
	 * class instance
	 */
	 
    function IMPlugin($id,$debug=IM_DEBUG_OFF) {
    	$this->Plugin($id);
        $this->_addHook('plugin_load_language_file', 'imPluginLanguageFile',	false);
       	$this->_addHook('approve_pending_project', 'im_process', false);
        $this->_addHook('project_is_suspended_or_pending', 'im_process_lock_muc_room', false);//can process several function
		$this->_addHook('confirme_account_register', 'account_register', false);
		$this->_addHook('added_user_to_project', 'im_process_muc_add_member', false);
		$this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
		$this->_addHook('project_is_active', 'im_process_unlock_muc_room', false);//unlock_muc_room
		$this->_addHook('delete_project', 'im_process_delete_muc_room', false);
		$this->_addHook('project_admin_remove_user', 'im_process_muc_remove_member', false);//im_process_muc_remove_member
		$this->_addHook('account_pi_entry', 'im_process_display_user_jabber_id_in_account', false);
		$this->_addHook('user_home_pi_entry', 'im_process_display_user_jabber_id', false);
		$this->_addHook('get_user_display_name', 'im_process_display_presence', false);
        $this->_addHook('widget_instance', 'myPageBox', false);
        $this->_addHook('widgets', 'widgets', false);
        $this->_addHook('user_preferences_appearance', 'user_preferences_appearance', false);
        $this->_addHook('update_user_preferences_appearance', 'update_user_preferences_appearance', false);
        $this->debug=$debug;
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'IMPluginInfo')) {
            require_once('IMPluginInfo.class.php');
            $this->pluginInfo =& new IMPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     * to allow test
     * @return string the last room name created  
     */
    function get_last_muc_room_name () {
		return $this->last_im_datas["muc"];
	}
	
	/**
     * to allow test
     * @return string the last room name created  
     */
	function get_last_muc_room_name_delete () {
		return $this->last_im_datas_remove['muc'];
	}
	
	function get_last_grp_name () {
		return $this->last_im_datas["grp"];
	}
	
	/**
	 * 
	 */
	function get_last_muc_room_name_locked () {
		return $this->last_im_datas["name_last_muc_locked"];
	}
	
	function get_last_muc_room_name_unlocked () {
		return $this->last_im_datas["name_last_muc_unlocked"];
	}
	
	/**
	 * To get last information about a member added in once muc
	 * @return string information about a member added in once muc
	 */
	function get_last_member_of_once_muc_room () {
		return $this->last_im_datas["names_last_member_in_muc"];
	}
	
	/**
	 * To get last information about a member removed from once muc
	 * @return string information about a member added in once muc
	 */
	function get_last_remove_member_of_once_muc_room () {
		return $this->last_im_datas["names_remove_member_from_muc"];
	}
    function imPluginLanguageFile($params) {
       $GLOBALS['Language']->loadLanguageMsg('IM','IM');
    }
    
    /**
     * To get an instance of jabdex
     * @return Jabbex object class for im processing
     */
    function &_get_im_object () {
		if(isset($this->im)&&$this->im){
        	//class was already instancied
        	return $this->im;
        }else {
			//
			if(isset($this->session)&&($this->session)){//if current session was saved .
				$this->im= new Jabbex($this->session);
				return $this->im;
			}else{ //we get new sessionID 
				if($this->debug==true){
					$this->session='debugsession123';
				}else{
				$this->session=session_hash();
				}
				
				if((isset($this->session))&&$this->session){
					$this->im=new Jabbex($this->session);
					return $this->im;
				}else{
					echo "<br> Unable to get session !!!";
					 return null; 
				}
			}
		}
	}
	
	/**
	 * 
	 */
	 function get_icon_path () {
		$themes_dir=$this->getThemePath();
		$icon_path=$themes_dir.'/images/icons/';
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
        if (!isset($this->_cache_presence[$jid])) {
            $status=$this->_get_im_object()->user_status($jid);
            //var_dump($status);
            $img_src='';
            $img_title='';
            $retunr_value = '';
            switch($status){
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
            $this->_cache_presence[$jid] = '<img src="'.$img_src.'" title="'.$img_title.'"  alt="'.$img_title.'" border="0" height="16" width="16" style="vertical-align:top">';
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
     * this function can be used to register an IM user  <br>
     */
    function account_register($params) {
	$info_register_new_user  =	'<br>'.$params['realname']."( ".$params['loginname']. ") "."[ ".$params['email']." ]<br>";
	echo $info_register_new_user;  
	} 
	
	/**
	 * This function allow to create muc room and shared group when the corresponding project(s)
	 * is(are) approuved by codex admin.
	 *  @param array params data from the shared CodeX event
	 */
	function im_process ($params) {
		//$this->muc_room_creation($params);
		//$this->create_im_shared_group($params);
		if($this->debug){
			echo "\nPass !!!<br>";
		}
	}
	
	/**
	 * process members added in project hook
	 *  @param array params data from the shared CodeX event
	 */
	function im_process_muc_add_member ($params) {
		//add member in muc room
		$this->im_muc_add_member ($params);
	}
	
	/**
	 * locked group or muc room
	 *  @param array params data from the shared CodeX event
	 */
	function im_process_lock_muc_room ($params) {
		$this->im_lock_muc_room($params);
	}
	
	/**
	 * im_process_unlock_muc_room
	 *  @param array params data from the shared CodeX event
	 */
	 function im_process_unlock_muc_room ($params) {
		$this->im_unlock_muc_room($params);
	}
	
	/**
	 * callback used to delete a muc room.
	 *  @param array params data from the shared CodeX event
	 */
	function im_process_delete_muc_room ($params) {
		$this->im_delete_muc_room($params);
	}
	
	/**
	 * To remove member from muc 
	 *  @param array params data from the shared CodeX event
	 */
	function im_process_muc_remove_member ($params) {
		$this->im_muc_remove_member($params);
	}
	
	/**
	 * Shared group creation
	 * @param array params data from the shared CodeX event 
	 */
	 function create_im_shared_group ($params) {
		$group_ids = $params['group_id'];
		$group_ids=explode(',',$group_ids);
		foreach($group_ids as $project_id){
	        $project = project_get_object($project_id);
	        $unix_project_name = $project->getUnixName();
	        $project_name = $project->getPublicName();
	        $grp=new Group($project_id);//data_array['hide_members']
	        $um = UserManager::instance();
	        $approuver = $um->getCurrentUser();
			
			try{
				$this->_get_im_object()->create_shared_group($unix_project_name,$project_name);
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('info', $project_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_only_msg'));
				}
				$this->last_im_datas["grp"]=$project_name;
			} catch(Exception $e){
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
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
		foreach($group_ids as $val){
	        $project = project_get_object($val);
	        $unix_group_name = $project->getUnixName();
	        $group_name=$project->getPublicName();
	        $group_description = $project->getDescription();
	        $um = UserManager::instance();
	        //to get the current user who eventually create the project
	        $approuver = $um->getCurrentUser();
	        $grp=new Group($val);
	        $project_members_ids=$grp->getMembersId();
	        $group_Owner_object=new User($project_members_ids[0]);
	        $group_Owner_name =$group_Owner_object->getName();
	        $group_Owner_real_name=user_getrealname($project_members_ids[0]);
	        
			try{
				$this->_get_im_object()->create_muc_room($unix_group_name, $group_name, $group_description, $group_Owner_name);
				//echo $unix_group_name.'  ('.$group_name.') << '. $group_description.'>>'.$group_Owner_name;
				if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_only_msg'));
				}
				$this->last_im_datas["muc"]=$group_name;
			} catch(Exception $e){
				if(!$this->debug){
					$GLOBALS['Response']->addFeedback('error', ' #### muc creation :'.$e->getMessage().' ### ');
				}	
			}
		}
	}
	
    /**
     * to lock an MUC room
     * @param array params:contains the data which comes from the envent listened (group_id her ).
     */
     public function im_lock_muc_room($params){
	 	$project_id = $params['group_id'];
        $project = project_get_object($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();
        
		try{
			$this->_get_im_object()->lock_muc_room($unix_project_name);
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('info', $project_name."  ".$GLOBALS['Language']->getText('plugin_im','muc_locked'));
			}
			$this->last_im_datas["name_last_muc_locked"]=$unix_project_name;
		} catch(Exception $e){
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
			}	
		}
        
       
     }
  
   /**
    * unlock a MUC room created and locked by "lock_muc_room($unix_project_name)".
    * @param array $params :contains the data which comes from the envent listened.
    */
   	function im_unlock_muc_room ($params) {
		$project_id = $params['group_id'];
        $project = project_get_object($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();
		
		try{
			$this->_get_im_object()->unlock_muc_room($unix_project_name);
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('info', $project_name."  ".$GLOBALS['Language']->getText('plugin_im','muc_unlocked'));
			}
			$this->last_im_datas["name_last_muc_unlocked"]=$unix_project_name;
		} catch(Exception $e){
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
					$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
				}	
		}
        
 	}
 	
 	/**
 	 * function called in im_process_delete_muc_room($params) to delete an muc room.
 	 * @param array $params :contains the data which comes from the envent listened.
 	 */
 	function im_delete_muc_room($params) {
		$project_id = $params['group_id'];
        $project = project_get_object($project_id);
        $unix_project_name = $project->getUnixName();
        $project_name = $project->getPublicName();
        try{
        $this->_get_im_object()->delete_muc_room($unix_project_name);
        $this->last_im_datas_remove['muc']=$unix_project_name;
        if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('info', $unix_project_name.$GLOBALS['Language']->getText('plugin_im','muc_delete'));
			}
        }catch(Exception $e){
        	if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
			}	
        }
	}
	
	/**
 	 * function called in im_process_muc_add_member($params) to add a member in a given muc room.
 	 * @param array $params :contains the data which comes from the envent listened.
 	 */
	function im_muc_add_member ($params) {
		$user_unix_name=$params['user_unix_name'];
		
		$group_id =$params['group_id'];
		$project = project_get_object($group_id);
        $group_name = $project->getUnixName();
		try{
			$this->_get_im_object()->muc_add_member($group_name, $user_unix_name);
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('info', $user_unix_name.'  '.$GLOBALS['Language']->getText('plugin_im','muc-add-member').' '.$group_name);
			}
			$this->last_im_datas["names_last_member_in_muc"]=$user_unix_name." is added in the muc :".$group_name;
		} catch(Exception $e){
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
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
		$project = project_get_object($group_id);
        $unix_group_name = $project->getUnixName();
        //user infos
        $user_id=$params['user_id'];
        $user=new User($user_id);
        $user_unix_name=$user->getUserName();
        
        try{
			$this->_get_im_object()->muc_remove_member($unix_group_name,$user_unix_name);
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('info',$user_unix_name.'  '.$GLOBALS['Language']->getText('plugin_im','muc-remove-member').' '.$unix_group_name );
			}
			$this->last_im_datas["names_remove_member_from_muc"]=$user_unix_name." is remove from the muc :".$unix_group_name;
		} catch(Exception $e){
			if(!$this->debug){//because when $this->debug is ON(true) we done fonctional test and $GLOBALS['Response'] is not known
				$GLOBALS['Response']->addFeedback('error', ' #### '.$e->getMessage().' ### ');
			}	
		}
	}
	
	/**
	 * for hook administration :display an URL to access IM administration.
	 * @param array $params:contains the data which comes from the envent listened.
	 */
 	function siteAdminHooks($params) {
       global $Language;
	   $Language->loadLanguageMsg('IM','IM');
       $link_title= $GLOBALS['Language']->getText('plugin_im','link_im_admin_title');
        echo '<li><a href="'.$this->getPluginPath().'/?view=codex_im_admin">'.$link_title.'</a></li>';
    }
 	
 	 function im_process_display_jabber_id ($eParams) {
	    global $Language;
	    $Language->loadLanguageMsg('IM','IM');
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
			$user_login=user_getname($eParams['user_id']);
			$jid_value=$user_login.'@'.$server_dns;
			$label=$GLOBALS['Language']->getText('plugin_im','im_user_login');
			//var_dump($label);
			$entry_label['jid'] = $label;
            if ( ! UserManager::instance()->getCurrentUser()->getPreference('plugin_im_hide_users_presence')) {
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
	function im_process_display_user_jabber_id_in_account ($eParams) {
		$this->im_process_display_jabber_id ($eParams);
	}
	

    function getDisplayPresence($user_id) {
        $user_helper = new UserHelper();
        $im_object = $this->_get_im_object();
        $jabberConf = $im_object->get_server_conf();
        
        $server_dns = $jabberConf['server_dns'];
        $user_login = user_getname($user_id);
        
        $jid_value = $user_login.'@'.$server_dns;
        $adm_port_im = $jabberConf['webadmin_unsec_port'];
        
        $presence = $this->_get_presence_status ($jid_value);
        
        return $presence . $user_helper->getDisplayNameFromUserId($user_id);
    }

        function myPageBox($params) {
            if ($params['widget'] == 'myroster') {
                require_once('IM_Widget_MyRoster.class.php');
                $params['instance'] = new IM_Widget_MyRoster($this);
            }
        }
        function widgets($params) {
            require_once('common/widget/WidgetLayoutManager.class.php');
            $lm = new WidgetLayoutManager();
            if ($params['owner_type'] == $lm->OWNER_TYPE_USER) {
                $params['codex_widgets'][] = 'myroster';
            }
        }

        function user_preferences_appearance($params) {
            $input = '<input type="hidden" name="plugin_im_display_users_presence" value="0" />';
            $input .= '<input type="checkbox" id="plugin_im_display_users_presence" name="plugin_im_display_users_presence" value="1" ';
            if ( ! UserManager::instance()->getCurrentUser()->getPreference('plugin_im_hide_users_presence')) {
                $input .= 'checked="checked"';
            }
            $input .= ' style="margin-left:0px;" />';
            $input .= '<label for="plugin_im_display_users_presence">';
            $input .= $GLOBALS['Language']->getText('plugin_im', 'user_appearance_pref_display_users_presence');
            $input .= '</label>';
            
            $params['preferences'][] = array(
                'name'  => $GLOBALS['Language']->getText('plugin_im', 'user_appearance_pref_name'),
                'value' => $input
            );
        }
        
        function update_user_preferences_appearance($params) {
            if ($params['request']->exist('plugin_im_display_users_presence')) {
                if ($params['request']->get('plugin_im_display_users_presence')) {
                    UserManager::instance()->getCurrentUser()->delPreference('plugin_im_hide_users_presence');
                } else {
                    UserManager::instance()->getCurrentUser()->setPreference('plugin_im_hide_users_presence', '1');
                }
            }
        }
        
	/**
 	 * display project members presence 
 	 * @param array $params:contains the data which comes from the envent listened.
 	 */
	function im_process_display_presence ($params) {
        if ( ! UserManager::instance()->getCurrentUser()->getPreference('plugin_im_hide_users_presence')) {
            $params['user_display_name'] = $this->getDisplayPresence($params['user_id']);
        }
	}
    
 	function process() {	
        require_once('IM.class.php');
        $controler =& new IM($this);
        $controler->process();
    }
    
}
?>
