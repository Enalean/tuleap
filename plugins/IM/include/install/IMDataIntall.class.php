<?php
require_once('pre.php');
require_once('IMPluginDao.class.php');
require_once('common/include/GroupFactory.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/dao/CodexDataAccess.class.php');

class IMDataIntall {
    
    var $codexdata;
    var $openfire_conexion;
    var $codex_conexion;
    var $openfire_db;
    var $codex_bd;
    var $openfire_dao;
    var $codex_dao;
    /**
     * im_object allow to access Jabbex
     */
     var $im;
    function IMDataIntall() {
    	$this->codexdata=array();
   		$GLOBALS['Language']->loadLanguageMsg('IM', 'IM');	
   		$this->codex_dao= & new IMPluginDao(CodexDataAccess::instance());
   		
    }
    
    function &_get_im_object () {
		if(isset($this->im)&&$this->im){
        	//class déjà instanciée
        	return $this->im;
        }else {
			//
			if(isset($this->session)&&($this->session)){//si la session courente est gardée 
			$this->im= new Jabbex($this->session);
			return $this->im;
			}else{ //on recupére de nouveau les session ID !!
				$session=session_hash();
				if((isset($session))&&$session){
					$this->im=new Jabbex($session);
					return $this->im;
				}else{
					echo "<br> Unable to get session !!!";
					 return null; 
				}
			}
		}
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
		$group_id=$request->get('group_id');
		$group_description=$request->get('group_description');
		$group_Owner_name=$request->get('group_Owner_name');
		$im_object=$this->_get_im_object();
		//var_dump($im_object);
		//echo $unix_group_name.'  ( '.$group_name.') de '.$group_Owner_name ;
		try{
			$im_object->create_muc_room(strtolower($unix_group_name), $group_name, $group_description, $group_Owner_name);
			$this->muc_member_build($group_id);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error: ').$e->getMessage());
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
		$im_object=$this->_get_im_object();
		try{
			$im_object->create_shared_group(strtolower($unix_group_name), $group_name);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error: ').$e->getMessage());
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
		$group_id=$request->get('group_id');
		//var_dump($group_id);
		$group_description=$request->get('group_description');
		$group_Owner_name=$request->get('group_Owner_name');
		$im_object=$this->_get_im_object();
		try{
			$im_object->create_muc_room(strtolower($unix_group_name), $group_name, $group_description, $group_Owner_name);
			$this->muc_member_build($group_id);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error: ').$e->getMessage());
		}
		try{
			$im_object->create_shared_group(strtolower($unix_group_name), $group_name);
			$GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
		}catch(Exception $e){
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error: ').$e->getMessage());
		}
	}
	 
	/**
	 * used by admin tools to diplay views
	 */
	function admin_install_muc_and_grp () {
		session_require(array('group'=>'1','admin_flags'=>'A'));
		$GLOBALS['Language']->loadLanguageMsg('IM', 'IM');
		$action = '';
		$nb_grp=0 ;
		$nb_muc=0;					
		$res_grp = $this->codex_dao->search_group_without_shared_group();//db_query($sqlshowInRoster);
		$res_grp=$res_grp->query;
		$res_muc =$this->codex_dao->search_group_without_muc(); //
		$res_muc=$res_muc->query;
		
		//nomber of shared group to install
		$nb_grp=db_numrows($res_grp);
		
		//nomber of muc room to install
		$nb_muc=db_numrows($res_muc);
		
		$array_grp=array();
		if($nb_grp>0){
			$array_grp=result_column_to_array($res_grp,0);
		}
		
		$array_muc=array();
		if($nb_muc>0){
			$array_muc=result_column_to_array($res_muc,0);
		}
		
		
		$array_muc_and_grp=array_intersect($array_grp,$array_muc);
		
		if(sizeof($array_muc_and_grp)){
			$array_muc_only=array_diff($array_muc,$array_muc_and_grp);
			$array_grp_only=array_diff($array_grp,$array_muc_and_grp);
		}else{
			$array_muc_only=$array_muc;
			$array_grp_only=$array_grp;
		}
		
			echo'<fieldset>';
			            echo'<legend style="font-size:1.3em; font-weight: bold;">Projets à synchroniser </legend>';
		if($nb_grp!=0 ||$nb_muc){
			//************form
			global $PHP_SELF;
			if(sizeof($array_muc_and_grp)){
				foreach($array_muc_and_grp as $key=>$val){
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name=$project->getPublicName();
			        $group_description = $project->getDescription();
			        $grp=new Group($val);
			        $group_id=$grp->getID();//$group_id=$val;
			        //var_dump($group_id);
			        $project_members_ids=$grp->getMembersId();
			        foreach($project_members_ids as $key=>$id){
			        	$group_Owner_object=new User($id);
			        	if($group_Owner_object->isMember($val,'A')){
			        		 $group_Owner_name =$group_Owner_object->getName();
			        	}
			        }
			        
			        //field label
			        $unix_group_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label=$GLOBALS["Language"]->getText('plugin_im_admin','action_label');//plugin_im_admin - unix_group_name_label
			        $action_on=$GLOBALS["Language"]->getText('plugin_im_admin','action_on_muc_and_grp');
			        echo'<fieldset>';
			            echo'<legend style="font-size:1.3em; font-weight: bold;">'.$group_name.'</legend>';
			            echo $unix_group_name_label.$unix_group_name.'<br>';
			            echo $group_description_label.$group_description.'<br>';
			            echo $group_Owner_name_label.$group_Owner_name.'<br>';
			            echo $action_label.$action_on.'<br>';
			            echo '
					        <CENTER>
					        <FORM action="/plugins/IM/?view=codex_im_admin" method="POST">
					        <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_muc_and_grp">
					        <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					         <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					       	 <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					       	 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					        </FORM>
					        </center>
					        ';
			        echo'</fieldset>';
				}	
			}
			
			if(sizeof($array_grp_only)){
				foreach($array_grp_only as $key=>$val){
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name=$project->getPublicName();
			        $group_description = $project->getDescription();
			        $grp=new Group($val);
			        $group_id=$grp->getID();
			        $project_members_ids=$grp->getMembersId();
			        foreach($project_members_ids as $key=>$id){
			        	$group_Owner_object=new User($id);
			        	if($group_Owner_object->isMember($val,'A')){
			        		 $group_Owner_name =$group_Owner_object->getName();
			        	}
			        }
			        
			        //field label
			        $unix_group_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label=$GLOBALS["Language"]->getText('plugin_im_admin','action_label');
			        $action_on=$GLOBALS["Language"]->getText('plugin_im_admin','action_on_grp');
			        echo'<fieldset>';
			            echo'<legend style="font-size:1.3em; font-weight: bold;">'.$group_name.'</legend>';
			            echo $unix_group_name_label.$unix_group_name.'<br>';
			            echo $group_description_label.$group_description.'<br>';
			            echo $group_Owner_name_label.$group_Owner_name.'<br>';
			            echo $action_label.$action_on.'<br>';
			            echo '
					        <CENTER>
					        <FORM action="/plugins/IM/?view=codex_im_admin" method="POST">
					        <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_grp_only">
					        <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					         <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					       	 <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					       	 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					        </FORM>
					        </center>
					        ';
			        echo'</fieldset>';
				}	
			}
			
			
			if(sizeof($array_muc_only)){
				foreach($array_muc_only as $key=>$val){
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name=$project->getPublicName();
			        //$group_id=$val;
			        $group_description = $project->getDescription();
			        $grp=new Group($val);
			        $group_id=$grp->getID();
			        $project_members_ids=$grp->getMembersId();
			        foreach($project_members_ids as $key=>$id){
			        	$group_Owner_object=new User($id);
			        	if($group_Owner_object->isMember($val,'A')){
			        		 $group_Owner_name =$group_Owner_object->getName();
			        	}
			        }
			       //field label
			        $unix_group_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label=$GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label=$GLOBALS["Language"]->getText('plugin_im_admin','action_label');
			        $action_on=$GLOBALS["Language"]->getText('plugin_im_admin','action_on_muc');
			        echo'<fieldset>';
			            echo'<legend style="font-size:1.3em; font-weight: bold;">'.$group_name.'</legend>';
			            echo $unix_group_name_label.$unix_group_name.'<br>';
			            echo $group_description_label.$group_description.'<br>';
			            echo $group_Owner_name_label.$group_Owner_name.'<br>';
			            echo $action_label.$action_on.'<br>';
			            echo '
					        <CENTER>
					        <FORM action="/plugins/IM/?view=codex_im_admin" method="POST">
					        <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_muc_only">
					        <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					         <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					       	 <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					       	 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					        </FORM>
					        </center>
					        ';
			       
			        echo'</fieldset>';
				}	
			}
			 
				 echo '
					 <CENTER>
					 <FORM action="/plugins/IM/?view=codex_im_admin" method="POST">
					 <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_all"> 
					 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_all').'">
					 </FORM>
					 </center>';
		}else{
		echo $GLOBALS["Language"]->getText('plugin_im_admin','no_project_to_synchronized');
			  
		}
				echo'</fieldset>';
			 
		
	}
    
}
?>