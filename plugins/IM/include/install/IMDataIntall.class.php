<?php
require_once('pre.php');
require_once(dirname(__FILE__).'/../IMDao.class.php');
require_once('common/include/GroupFactory.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/dao/CodexDataAccess.class.php');

class IMDataIntall {
    
    var $codexdata;
    var $openfire_dao;
    var $codex_dao;
    /**
     * im_object allow to access Jabbex
     */
     var $im;
    function IMDataIntall() {
    	$this->codexdata=array();
   		$GLOBALS['Language']->loadLanguageMsg('IM', 'IM');	
   		$this->codex_dao= & new IMDao(CodexDataAccess::instance());
    }

    /**
     * Get the only one Jabbex instance with the onelyone session ID in this script
     * @return Jabbex IM datas encapsulation 
     */
	function _get_im_object () {
		try{
			require_once(dirname(__FILE__)."/../jabbex_api/Jabbex.php");
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', 'Jabbex require_once error #### '.$e->getMessage().' ### ');
		  	return null;
		}
		if(isset($this->im)&&$this->im){
        	//class was already instancied
        	return $this->im;
        }else {
			//Jabbex was never instancied in the current script
			try{
				if(isset($this->session)&&($this->session)){//if current session was saved .
					$this->im= new Jabbex($this->session);
				}else{ //we get new sessionID 
					$this->session=session_hash();
					if((isset($this->session))&&$this->session){
						$this->im=new Jabbex($this->session);
					}else{
						echo "<br> Unable to get session !!!";
					}
				}
			}catch(Exception $e){
				$GLOBALS['Response']->addFeedback('error', 'Jabbex instance #### '.$e->getMessage().' ### ');
				return null;
			}
		}
		return $this->im;
	}
	
	/**
	 * add members and affiliate admins and owner room for the group identified by $group_id
	 * @param long $group_id.
	 */
	 function muc_member_build ($group_id) {
		//IM infos
		$im_object=$this->_get_im_object();
		$jabberConf=$im_object->get_server_conf();
		$server_dns=$jabberConf['server_dns'];
		$admin_server=$jabberConf['username'];
		
		//muc affiliation infos
		$admin_affiliation=20;
		$super_admin_affiliation=10;
		
		//about projet to be synchronize
		$grp=new Group($group_id);
		$roomID=$this->codex_dao->get_rom_id_by_unix_name ($grp->getUnixName());
		$project_members_ids=$grp->getMembersId();
		
		foreach($project_members_ids as $user_id){
			$user_object=new User($user_id);
			$user_name =trim($user_object->getName());
			$jid_value=trim($user_name.'@'.$server_dns);
			if(!($user_object->isMember($group_id,'A'))){
				$this->codex_dao->add_muc_room_user($roomID,$jid_value);
			}
		}
	}
	
    /**
	 *	return a resultset of Group (with group_id,group_name,unix_group_name fields)
	 *
	 *	@return	resultset
	 */
    function getProjectInfos () {
		return $this->codexdata['groups']->getAllGroups(); ;
	}
   
	/**
	 * synchronize_muc_only :
	 * 
	 */
	 function synchronize_muc_only () {
		session_require(array('group'=>'1','admin_flags'=>'A'));
		$request =& HTTPRequest::instance();
		$unix_group_name=$request->get('unix_group_name');
		$group_name=$request->get('group_name');
		if(!(isset($group_name)&&$group_name!=null)){
			$group_name=$unix_group_name;
		}
		$group_id=$request->get('group_id');
		$group_description=$request->get('group_description');
		if(!(isset($group_description)&&$group_description!=null)){
			$group_description='No description';
		}
		$group_Owner_name=$request->get('group_Owner_name');
		$im_object=$this->_get_im_object();
		try{
			$im_object->create_muc_room(strtolower($unix_group_name), $group_name, $group_description, $group_Owner_name);
			$this->muc_member_build($group_id);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error').$e->getMessage());
		}
	}
	
	/**
	 * synchronize_grp_only
	 */
	function synchronize_grp_only () {
		session_require(array('group'=>'1','admin_flags'=>'A'));
		$request =& HTTPRequest::instance();
		$unix_group_name=$request->get('unix_group_name');
		$group_name=$request->get('group_name');
		if(!(isset($group_name)&&$group_name!=null)){
			$group_name=$unix_group_name;
		}
		$im_object=$this->_get_im_object();
		try{
			$im_object->create_shared_group(strtolower($unix_group_name), $group_name);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error').$e->getMessage());
		}
	}
	
	/**
	 * synchronize_muc_and_grp
	 */
	 
	 function synchronize_muc_and_grp_together () {
		session_require(array('group'=>'1','admin_flags'=>'A'));
		$request =& HTTPRequest::instance();
		$unix_group_name=$request->get('unix_group_name');
		$group_name=$request->get('group_name');
		if(!(isset($group_name)&&$group_name!=null)){
			$group_name=$unix_group_name;
		}
		$group_id=$request->get('group_id');
		$group_description=$request->get('group_description');
		if(!(isset($group_description)&&$group_description!=null)){
			$group_description='No description';
		}
		$group_Owner_name=$request->get('group_Owner_name');
		$im_object=$this->_get_im_object();
		try{
			if(isset($im_object)&&$im_object){
				$im_object->create_muc_room(strtolower($unix_group_name), $group_name, $group_description, $group_Owner_name);
				$this->muc_member_build($group_id);
				$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
			}else{
				$GLOBALS['Response']->addFeedback('error', "IM Object no available for muc room creation.");
			}
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error').$e->getMessage());
		}
		try{
			if(isset($im_object)&&$im_object){
				$im_object->create_shared_group(strtolower($unix_group_name), $group_name);
				$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
			}else{
				$GLOBALS['Response']->addFeedback('error', "IM Object no available for shared group creation.");
			}
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error').$e->getMessage());
		}
	}
    
}
?>
