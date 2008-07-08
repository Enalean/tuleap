<?php

require_once('common/dao/include/DataAccessObject.class.php');
class IMPluginDao extends DataAccessObject {
	var $im;
    function IMPluginDao(& $da ) {
        DataAccessObject::DataAccessObject($da);
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
    * Searches IMPluginGroup by CodeX GroupId 
    * @return DataAccessResult
    */
    function & searchGroupByGroupId($groupId) {
        $sql = sprintf("SELECT g.*  
                        FROM GROUPE g, CONFIG c
                        WHERE c.CLE = 'cx.trk.grp_id' AND
                              c.VALEUR = %s AND
                              c.id_projet = g.PROJET_VOICE_TESTING_id_projet",
            $this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }
    
     function & searchAll() {
        $sql = "SELECT * FROM groups";
        return $this->retrieve($sql);
    }
    
    /**
     * search groups no synchronized with muc room
     * @DataAccesResult
     */
    function & search_group_without_muc () {
		
		$sql_muc="SELECT cg.group_id,LOWER(cg.unix_group_name) AS unix_group_name, cg.group_name,cg.short_description
							FROM ". $this->da->db_name .".groups AS cg
							LEFT JOIN openfire.mucRoom AS muc
							ON (muc.name = LOWER(cg.unix_group_name)
							AND muc.naturalName = cg.group_name)
							WHERE muc.name IS NULL
							AND cg.status = 'A'
							ORDER BY group_name ASC"; ///////,LOWER(cg.unix_group_name) AS unix_group_name, cg.group_name,cg.short_description,cu.user_name AS user_name
				
		return $this->retrieve($sql_muc);
	}
	
	/**
	 * used for unique ID sequence generation
	 */
	function get_last_rom_id () {
		//the idType of muc room is 23
		$id_type=23;
		$sql=sprintf("SELECT id FROM openfire.jiveID WHERE idType=%s",
						$this->da->quoteSmart($id_type));
		$id_dar=$this->retrieve($sql);
		$row=$id_dar->getRow();
		return $row['id'];
	}
	
	
	/**
	 * get group_id by group_unix_name
	 */
	 function get_rom_id_by_unix_name ($unix_name) {
		$sql=sprintf("SELECT roomID FROM openfire.mucRoom WHERE name=%s",
						$this->da->quoteSmart($unix_name));
		$id_dar=$this->retrieve($sql);
		$row=$id_dar->getRow();
		return $row['roomID'];
	}
	 
	/**
	 * update last roomID
	 */
	 
	 function update_last_room_id () {
		//the idType of muc room is 23
		$id_type=23;
		$last_id=$this->get_last_rom_id ()+1;
		$sql=sprintf("UPDATE openfire.jiveID SET id= %s WHERE idType=%s",
						$this->da->quoteSmart($last_id),
						$this->da->quoteSmart($id_type));
		$updated = $this->update($sql);
	}
	 
	/**
     * search groups no synchronized with muc room
     * @@return DataAccesResult query result
     */
	function & search_group_without_shared_group () {
		
		$sql='SELECT cg.group_id
				FROM '. $this->da->db_name .'.groups AS cg 
				LEFT JOIN openfire.jiveGroupProp AS og
	     				ON (og.groupName = LOWER(cg.unix_group_name)
	          			AND og.name = \'sharedRoster.showInRoster\')
				WHERE og.groupName IS NULL
	  				AND cg.status = \'A\'
	  			ORDER BY group_name ASC';///can be use to make insertion ,LOWER(cg.unix_group_name),cg.group_name,cg.short_description, \'sharedRoster.showInRoster\', \'onlyGroup\'
				
		return $this->retrieve($sql);
	}
	
	/**
	 * synchronize_grp_for_im_display_name
	 * @return true/false
	 */
	function synchronize_grp_for_im_display_name () {
		$sql_displayName='INSERT INTO openfire.jiveGroupProp (groupName, name, propValue)' .
	  								   'SELECT LOWER(cg.unix_group_name), \'sharedRoster.displayName\', cg.group_name
										FROM '. $this->da->db_name .'.groups AS cg LEFT JOIN openfire.jiveGroupProp AS og
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
	function synchronize_grp_for_im_show_in_roster () {
		$sqlshowInRoster='INSERT INTO openfire.jiveGroupProp (groupName, name, propValue)' .
			        		         'SELECT LOWER(cg.unix_group_name), \'sharedRoster.showInRoster\', \'onlyGroup\'
									  FROM '. $this->da->db_name .'.groups AS cg LEFT JOIN openfire.jiveGroupProp AS og
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
	 function add_muc_room_user ($roomID,$jid/*,$nickname='',$firstName='',$lastName='',$url='',$faqentry=''*/) {
		$forma="INSERT INTO openfire.mucMember(roomID,jid)
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
	 function muc_room_affiliation ($roomID,$jid,$affiliation) {
		$forma="INSERT INTO openfire.mucAffiliation(roomID,jid,affiliation)
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
	function synchronize_grp_for_im_muc_room () {
			          $dar=&$this->search_group_without_muc();
			         $result=$dar->query;//$this->retrieve($sql)->query;
			         if(isset($result)&&$result){
			         		//var_dump($result);
			         		///about jabber server
			         		$im_object=$this->_get_im_object();
							$jabberConf=$im_object->get_server_conf();
							$server_dns=$jabberConf['server_dns'];
			         		$admin_server=$jabberConf['username'];
			         		$admin_affiliation=20;
			         		$super_admin_affiliation=10;
			         		
			         		$creation_date=''.round(1000*microtime(true));
			         	 	$creation_date=$this->da->quoteSmart($creation_date);
			         	 	//echo $creation_date;
					  		$modification_date=''.round(1000*microtime(true));
					  		$modification_date=$this->da->quoteSmart($modification_date);
					  		$short_name='';
					  		$public_name='';
					  		$owner='';
					  		$description='';
					  		$locked_date='000000000000000';
					  		$locked_date=$this->da->quoteSmart($locked_date);
					  		$empty_date=''.round(1000*microtime(true));
					  		$empty_date=$this->da->quoteSmart($empty_date);
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
					  		$room_pwd="";
					  		$room_pwd=$this->da->quoteSmart($room_pwd);
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
//					  		$resultID=$this->retrieve("SELECT roomID FROM openfire.mucRoom ORDER BY roomID ASC")->query;
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
								$lastID=$this->get_last_rom_id();
								$id=$this->da->quoteSmart($lastID);
								$short_name=strtolower($row['unix_group_name']);
								$short_name=$this->da->quoteSmart($short_name);
								$public_name=$row['group_name'];
								$public_name=$this->da->quoteSmart($public_name);
								$description=$row['short_description'];
								$description=$this->da->quoteSmart($description);
								
								//echo "<font color=\"red\"><b>Owner :  </b></font> : ".$row['user_name']."  |<font color=\"red\"><b>Nom public : </b></font>".$row['group_name']."         |"."<font color=\"red\"><b>Unix name :  </b></font>".$row['unix_group_name']."  desc :".$row['short_description']."<br>";
								$forma="INSERT INTO openfire.mucRoom
								                    (roomID, creationDate, modificationDate, name, naturalName, description, lockedDate, emptyDate, canChangeSubject, maxUsers, publicRoom, moderated, membersOnly, canInvite, roomPassword, canDiscoverJID, logEnabled, subject, rolesToBroadcast, useReservedNick, canChangeNick, canRegister)
										 VALUES (%s, %s, %s, %s,%s, %s, %s, %s,%s, %s, %s, %s,%s, %s, %s, %s,%s, %s, %s, %s,%s, %s)";
								$sql = sprintf($forma,$id,$creation_date,$modification_date,$short_name,$public_name,$description,0000000000,$empty_date,$change_subject,$max_user,$public_room,$moderated,$members_only,$can_invite,$room_pwd,$can_discover_JID,$log_enabled,$subject,$role_to_broadcast,$use_reserved_NICK,$can_changed_nick,$can_register);
								//echo $sql.'<br>';
								  
								$tamp=$this->update($sql);
								//$tamp=true;//to be delete
								//about muc members
								$group_id=$row['group_id'];
								$grp=new Group($group_id);
						        $project_members_ids=$grp->getMembersId();
						        foreach($project_members_ids as $user_id){
							        $user_object=new User($user_id);
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
	 function synchronize_all_project () {
	  $this->synchronize_grp_for_im_muc_room();
	  $this->synchronize_grp_for_im_show_in_roster();
	  $this->synchronize_grp_for_im_display_name();
	}
}
?>