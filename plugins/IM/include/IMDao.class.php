<?php

require_once('common/dao/include/DataAccessObject.class.php');
require_once('JabbexFactory.class.php');

class IMDao extends DataAccessObject {
	
    var $openfire_db_name;
    var $codendi_db_name;
    
    const MUC_ROOM_TYPE_ID = 23;
    const OPENFIRE_ADMIN_AFFILIATION = 20;
    const OPENFIRE_SUPER_ADMIN_AFFILIATION = 10;
    
    /**
    * Constructs the IMDao
    * @param $da instance of the DataAccess class
    */
    function __construct($da) {
        parent::__construct($da);
        $this->openfire_db_name = $da->db_name;
        $this->codendi_db_name = $GLOBALS['sys_dbname'];
    }
    
    /**
     * Returns an instance of jabdex
     * @return Jabbex object class for im processing
     */
    function _get_im_object() {
		return JabbexFactory::getJabbexInstance();
	}
	
    /**
     * search groups no synchronized with muc room
     * @DataAccesResult
     */
    function search_group_without_muc() {
		
		$sql_muc="SELECT cg.group_id,LOWER(cg.unix_group_name) AS unix_group_name, cg.group_name,cg.short_description
							FROM ". $this->codendi_db_name .".groups AS cg
							LEFT JOIN ".$this->openfire_db_name.".ofMucRoom AS muc
							ON (muc.name = LOWER(cg.unix_group_name))
							WHERE muc.name IS NULL
							AND cg.status = 'A'
							ORDER BY group_name ASC";
				
		return $this->retrieve($sql_muc);
	}
	
	/**
	 * used for unique ID sequence generation
	 */
	function get_last_room_id() {
		$sql = sprintf("SELECT id FROM ".$this->openfire_db_name.".ofID WHERE idType=%s",
						$this->da->quoteSmart(self::MUC_ROOM_TYPE_ID));
		$id_dar = $this->retrieve($sql);
		$row = $id_dar->getRow();
		return $row['id'];
	}
	
	
	/**
	 * get room_id by group_unix_name
	 */
	 function get_room_id_by_unix_name($unix_name) {
		$sql=sprintf("SELECT roomID FROM ".$this->openfire_db_name.".ofMucRoom WHERE name=%s",
						$this->da->quoteSmart($unix_name));
		$id_dar=$this->retrieve($sql);
		$row=$id_dar->getRow();
		return $row['roomID'];
	}
	 
	/**
	 * update last roomID
	 */
	 
	 function update_last_room_id() {
		$last_id=$this->get_last_room_id ()+1;
		$sql=sprintf("UPDATE ".$this->openfire_db_name.".ofID SET id= %s WHERE idType=%s",
						$this->da->quoteSmart($last_id),
						$this->da->quoteSmart(self::MUC_ROOM_TYPE_ID));
		$updated = $this->update($sql);
	}
	 
	/**
     * search groups no synchronized with muc room
     * @@return DataAccesResult query result
     */
	function search_group_without_shared_group() {
		
		$sql='SELECT cg.group_id
				FROM '. $this->codendi_db_name .'.groups AS cg 
				LEFT JOIN '.$this->openfire_db_name.'.ofGroupProp AS og
	     				ON (og.groupName = LOWER(cg.unix_group_name)
	          			AND og.name = \'sharedRoster.showInRoster\')
				WHERE og.groupName IS NULL
	  				AND cg.status = \'A\'
	  			ORDER BY group_name ASC';

		return $this->retrieve($sql);
	}
	
	/**
	 * synchronize_grp_for_im_display_name
	 * @return true/false
	 */
	function synchronize_grp_for_im_display_name() {
		$sql_displayName='INSERT INTO '.$this->openfire_db_name.'.ofGroupProp (groupName, name, propValue)' .
	  								   'SELECT LOWER(cg.unix_group_name), \'sharedRoster.displayName\', cg.group_name
										FROM '. $this->codendi_db_name .'.groups AS cg LEFT JOIN '.$this->openfire_db_name.'.ofGroupProp AS og
			     						ON (og.groupName = cg.unix_group_name
			          					AND og.name = \'sharedRoster.displayName\')
										WHERE og.groupName IS NULL
			  							AND cg.status = \'A\'';
		return $this->update($sql_displayName);
	}
	
	/**
	 * synchronize_grp_for_im_show_in_roster
	 * @return  true/false
	 */
	function synchronize_grp_for_im_show_in_roster() {
		$sqlshowInRoster='INSERT INTO '.$this->openfire_db_name.'.ofGroupProp (groupName, name, propValue)' .
			        		         'SELECT LOWER(cg.unix_group_name), \'sharedRoster.showInRoster\', \'onlyGroup\'
									  FROM '. $this->codendi_db_name .'.groups AS cg LEFT JOIN '.$this->openfire_db_name.'.ofGroupProp AS og
	     							  ON (og.groupName = cg.unix_group_name
	          						  AND og.name = \'sharedRoster.showInRoster\')
									  WHERE og.groupName IS NULL
	  								  AND cg.status = \'A\'
	  							      ORDER BY group_name ASC';
		return $this->update($sqlshowInRoster);
	}
	
	/**
	 * to set muc members
	 */
	 function add_muc_room_user($roomID,$jid/*,$nickname='',$firstName='',$lastName='',$url='',$faqentry=''*/) {
		$forma="INSERT INTO ".$this->openfire_db_name.".ofMucMember(roomID,jid)
				 VALUES(%s, %s)"; //we can add also , %s, %s,%s, %s, %s--->nickname,firstName,lastName,url,faqentry
		$sql = sprintf($forma,
						$this->da->quoteSmart($roomID),
						$this->da->quoteSmart($jid)/*,
						$this->da->quoteSmart($nickname),
						$this->da->quoteSmart($firstName),
						$this->da->quoteSmart($lastName),
						$this->da->quoteSmart($url),
						$this->da->quoteSmart($faqentry)*/);
		$this->update($sql);
		//echo $sql.'<br>';
	}
	
	/**
	 * muc room affiliation
	 */
	 function muc_room_affiliation($roomID,$jid,$affiliation) {
		$forma="INSERT INTO ".$this->openfire_db_name.".ofMucAffiliation(roomID,jid,affiliation)
				 VALUES (%s, %s, %s);";
		$sql = sprintf($forma,
						$this->da->quoteSmart($roomID),
						$this->da->quoteSmart($jid),
						$this->da->quoteSmart($affiliation));
		$this->update($sql);
		//echo $sql.'<br>';
	}
	 
	/**
	 * synchronize_grp_for_im_display_name
	 * @@return true/false
	 */
	function synchronize_grp_for_im_muc_room() {
        $pm = ProjectManager::instance();
			          $dar=&$this->search_group_without_muc();
			         $result=$dar->getResult();//$this->retrieve($sql)->query;
			         if(isset($result)&&$result){
			         		//var_dump($result);
			         		///about jabber server
			         		$im_object=$this->_get_im_object();
							$jabberConf=$im_object->get_server_conf();
							$server_dns=$jabberConf['server_dns'];
			         		$admin_server=$jabberConf['username'];
			         		$admin_affiliation = self::OPENFIRE_ADMIN_AFFILIATION;
			         		$super_admin_affiliation = self::OPENFIRE_SUPER_ADMIN_AFFILIATION;
			         		
			         		$creation_date=''.round(1000*microtime(true));
			         	 	$creation_date=$this->da->quoteSmart($creation_date, 'force_string');
			         	 	//echo $creation_date;
					  		$modification_date=''.round(1000*microtime(true));
					  		$modification_date=$this->da->quoteSmart($modification_date, 'force_string');
					  		$short_name='';
					  		$public_name='';
					  		$owner='';
					  		$description='';
					  		$locked_date = '000000000000000';
					  		$locked_date = $this->da->quoteSmart($locked_date, 'force_string');
					  		$empty_date=''.round(1000*microtime(true));
					  		$empty_date=$this->da->quoteSmart($empty_date, 'force_string');
					  		$change_subject=1;
					  		$change_subject=$this->da->quoteSmart($change_subject);
					  		$max_user=0;
					  		$max_user=$this->da->quoteSmart($max_user);
					  		$public_room=1;
					  		$public_room=$this->da->quoteSmart($public_room);
					  		$moderated=1;
					  		$moderated=$this->da->quoteSmart($moderated);
					  		$members_only=1;
					  		$members_only=$this->da->quoteSmart($members_only);
					  		$can_invite=1;
					  		$can_invite=$this->da->quoteSmart($can_invite);
					  		$can_discover_JID=1;
					  		$can_discover_JID=$this->da->quoteSmart($can_discover_JID);
					  		$log_enabled=1;
					  		$log_enabled=$this->da->quoteSmart($log_enabled);
					  		$subject="";
					  		$subject=$this->da->quoteSmart($subject);
					  		$role_to_broadcast=7;
					  		$role_to_broadcast=$this->da->quoteSmart($role_to_broadcast);
					  		$use_reserved_NICK=0;
					  		$use_reserved_NICK=$this->da->quoteSmart($use_reserved_NICK);
					  		$can_changed_nick=1;
					  		$can_changed_nick=$this->da->quoteSmart($can_changed_nick);
					  		$can_register=1;
					  		$can_register=$this->da->quoteSmart($can_register);
					  		
//					  		//for last muc Id
//					  		$resultID=$this->retrieve("SELECT roomID FROM ".$this->openfire_db_name.".mucRoom ORDER BY roomID ASC")->query;
//					  		$lastID=0;
//					  		
//					  		while ($donnees = db_fetch_array($resultID) ){
//					  			$lastID=$donnees["roomID"];	
//					  			//echo $lastID.'<br>';
//					  		}
//					  		db_free_result($resultID);
//					  		//var_dump($dar);
							$lastID=1;
					  		$tamp=false;
					  		//synchronize each project
					  		while ($row=$dar->getRow()){
								//$lastID++;
								//echo $lastID.'<br>';
								$lastID=$this->get_last_room_id();
								$id=$this->da->quoteSmart($lastID);
								$short_name=strtolower($row['unix_group_name']);
								$short_name=$this->da->quoteSmart($short_name);
								$public_name=$row['group_name'];
								$public_name=$this->da->quoteSmart($public_name);
								$description=$row['short_description'];
								$description=$this->da->quoteSmart($description);
								
								//echo "<font color=\"red\"><b>Owner :  </b></font> : ".$row['user_name']."  |<font color=\"red\"><b>Nom public : </b></font>".$row['group_name']."         |"."<font color=\"red\"><b>Unix name :  </b></font>".$row['unix_group_name']."  desc :".$row['short_description']."<br>";
								$forma="INSERT INTO ".$this->openfire_db_name.".ofMucRoom
								                    (roomID, creationDate, modificationDate, name, naturalName, description, lockedDate, emptyDate, canChangeSubject, maxUsers, publicRoom, moderated, membersOnly, canInvite, canDiscoverJID, logEnabled, subject, rolesToBroadcast, useReservedNick, canChangeNick, canRegister)
										 VALUES (%s, %s, %s, %s,%s, %s, %s, %s,%s, %s, %s, %s,%s, %s, %s, %s, %s, %s, %s,%s, %s)";
								$sql = sprintf($forma,$id,$creation_date,$modification_date,$short_name,$public_name,$description,$locked_date,$empty_date,$change_subject,$max_user,$public_room,$moderated,$members_only,$can_invite,$can_discover_JID,$log_enabled,$subject,$role_to_broadcast,$use_reserved_NICK,$can_changed_nick,$can_register);
								//echo $sql.'<br>';
								  
								$tamp=$this->update($sql);
								//$tamp=true;//to be delete
								//about muc members
								$group_id=$row['group_id'];
								$grp = $pm->getProject($group_id);
						        $project_members_ids=$grp->getMembersId();
						        foreach($project_members_ids as $user_id){
							        $user_object = UserManager::instance()->getUserById($user_id);
							        $user_name =trim($user_object->getName());
							        $jid_value=trim($user_name.'@'.$server_dns);
							        //$this->add_muc_room_user($id,$jid_value);
							        if($user_object->isMember($group_id,'A')){
							        	$this->muc_room_affiliation ($id,$jid_value,$admin_affiliation);
							        }else{
							        	$this->add_muc_room_user($id,$jid_value);
							        }
						        }
								
								//the owner of the muc
								$this->muc_room_affiliation ($id,trim($admin_server.'@'.$server_dns),$super_admin_affiliation);
								//We can also use the flowing instruction to synchronize cleanly each project with his muc  
								//$this->_get_im_object ()->create_muc_room($donnees['unix_group_name'],$donnees['group_name'],$donnees['short_description'],$donnees['user_name']);
								$this->update_last_room_id();
					 		}
			          if($donnees = db_fetch_array($result)){
			          	$GLOBALS['Response']->addFeedback('error', 'ERROR');
			          	return false;
			          }else{
			          	$GLOBALS['Response']->addFeedback('info', 'synchronize sucessful !!!');
			          	return $tamp;
			          }
			         }
	}
	
	
	/**
	 * synchronize all project with IM concept .
	 */
	function synchronize_all_project() {
	    $this->synchronize_grp_for_im_muc_room();
	    $this->synchronize_grp_for_im_show_in_roster();
	    $this->synchronize_grp_for_im_display_name();
	}
    
    
    
    
    
    /**
	 * add members and affiliate admins and owner room for the group identified by $group_id
	 * @param long $group_id.
	 */
	 function muc_member_build($group_id) {
         $pm = ProjectManager::instance();
		//IM infos
		$im_object = $this->_get_im_object();
		$jabberConf = $im_object->get_server_conf();
		$server_dns = $jabberConf['server_dns'];
		$admin_server = $jabberConf['username'];
		
		//muc affiliation infos
		$admin_affiliation = self::OPENFIRE_ADMIN_AFFILIATION;
		$super_admin_affiliation = self::OPENFIRE_SUPER_ADMIN_AFFILIATION;
		
		//about projet to be synchronize
		$grp = $pm->getProject($group_id);
		$roomID = $this->get_room_id_by_unix_name ($grp->getUnixName());
		$project_members_ids = $grp->getMembersId();
		
		foreach ($project_members_ids as $user_id) {
			$user_object = UserManager::instance()->getUserById($user_id);
			$user_name = trim($user_object->getName());
			$jid_value = trim($user_name.'@'.$server_dns);
			if( ! ($user_object->isMember($group_id,'A')) ) {
				$this->add_muc_room_user($roomID,$jid_value);
			}
		}
	}
   
	/**
	 * synchronize_muc_only :
	 *
     * @throw Exception
	 */
	function synchronize_muc_only($unix_group_name, $group_name, $group_description, $group_Owner_name, $group_id) {
		$im_object = $this->_get_im_object();
        if (isset($im_object) && $im_object) {
            $im_object->create_muc_room(strtolower($unix_group_name), $group_name, $group_description, $group_Owner_name);
            $this->muc_member_build($group_id);
        } else {
            throw new Exception("IM Object not available");
        }
	}
	
	/**
	 * synchronize_grp_only
	 */
	function synchronize_grp_only($unix_group_name, $group_name) {
		$im_object = $this->_get_im_object();
        if (isset($im_object) && $im_object) {
            $im_object->create_shared_group(strtolower($unix_group_name), $group_name);
        } else {
            throw new Exception("IM Object not available");
        }
	}
	
}
?>
