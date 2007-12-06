<?php   

//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
// Originally written by Marie-Luise Schneider 2005, CodeX Team, Xerox

//require_once('common/include/Error.class.php');
//require_once('common/include/Permission.class.php');

//$Language->loadLanguageMsg('include/include');

/*

	User object

	Sets up database results and preferences for a user and abstracts this info
*/


class User {

	var $id;

	//associative array of data from db
	var $data_array;

	//group data row from db. 
	//For each group_id (the user is part of) one array from the user_group table
	var $group_data;

	//tracker permission data
	//for each group_artifact_id (the user is part of) one array from the artifact-perm table
	var $tracker_data;

    // Keep super user info
    var $isSuperUser;

	function User($id) {
	  global $ALL_USERS_DATA, $ALL_USERS_GROUPS, $ALL_USERS_TRACKERS;

      $this->isSuperUser = null;
	  $this->id = $id;
	  if (isset($ALL_USERS_DATA["user_$id"])) {
	    $this->data_array = $ALL_USERS_DATA["user_$id"];
	    $this->group_data = $ALL_USERS_GROUPS["user_$id"];
	    $this->tracker_data = $ALL_USERS_TRACKERS["user_$id"];
	    return;
	  }
        
        if (!$this->fetchData($id)) { //Passage en anonymous
            $this->id           = 0;
            $this->data_array   = array('user_id' => 0);
            $this->group_data   = array();
            $this->tracker_data = array();
            return false;
        }
	}


	/*
		Return database result handle for direct access

		Generall should NOT be used - here for supporting deprecated group.php
	*/
	function fetchData($id) {
	  global $ALL_USERS_DATA, $ALL_USERS_GROUPS, $ALL_USERS_TRACKERS;


	  $sql = "SELECT * FROM user WHERE user_id = $id";
	  $db_res = db_query($sql);
	  if (db_numrows($db_res) != 1) {
	    return false;
	  }
	  $this->data_array=db_fetch_array($db_res);
	  $ALL_USERS_DATA["user_$id"] = $this->data_array;
	  

	  $this->group_data=array();
	  $sql = "SELECT * FROM user_group WHERE user_id = $id";
	  $db_res = db_query($sql);
	  if (db_numrows($db_res) > 0) {
	    while ($row = db_fetch_array($db_res)) {
	      $this->group_data[$row['group_id']] = $row;
	    }
	  }
	  $ALL_USERS_GROUPS["user_$id"] = $this->group_data;
	  
	  $this->tracker_data = array();
	  $sql = "SELECT group_artifact_id, perm_level FROM artifact_perm WHERE user_id = $id";
	  $db_res = db_query($sql);
	  if (db_numrows($db_res) > 0) {
	    while ($row = db_fetch_array($db_res)) {
	      $this->tracker_data[$row['group_artifact_id']] = $row;
	    }
	  }
	  $ALL_USERS_TRACKERS["user_$id"] = $this->tracker_data;
      return true;
	} 


	/**
	 * is this user member of group $group_id ??
	 */
	function isMember($group_id,$type=0) {
        /*
            CodeX admins always return true
        */
        if ($this->isSuperUser()) {
            return true;
        }
        
	  $is_member = array_key_exists($group_id,$this->group_data);
	  if (!$is_member) return false;

	  if ($type === 0) return true;

	  $group_perm = $this->group_data[$group_id];

	  $type=strtoupper($type);

	  switch ($type) {
	    /*
	     list the supported permission types
	    */
	  case 'B1' : {
	    //bug tech
	    return ($group_perm['bug_flags'] == 1 || $group_perm['bug_flags'] == 2);
	    break;
	  }
	  case 'B2' : {
	    //bug admin
	    return ($group_perm['bug_flags'] == 2 || $group_perm['bug_flags'] == 3);
	    break;
	  }
	  case 'P1' : {
	    //pm tech
	    return ($group_perm['project_flags'] == 1 || $group_perm['project_flags'] == 2);
	    break;
	  }
	  case 'P2' : {
	    //pm admin
	    return ($group_perm['project_flags'] == 2 || $group_perm['project_flags'] == 3);
	    break;
	  }
	  case 'C1' : {
	    //patch tech
	    return ($group_perm['patch_flags'] == 1 || $group_perm['patch_flags'] == 2);
	    break;
	  }
	  case 'C2' : {
	    //patch admin
	    return ($group_perm['patch_flags'] == 2 || $group_perm['patch_flags'] == 3);
	    break;
	  }
	  case 'F2' : {
	    //forum admin
	    return ($group_perm['forum_flags'] == 2);
	    break;
	  }
	  case 'S1' : {
	    //support tech
	    return ($group_perm['support_flags'] == 1 || $group_perm['support_flags'] == 2);
	    break;
	  }
	  case 'S2' : {
	    //support admin
	    return ($group_perm['support_flags'] == 2 || $group_perm['support_flags'] == 3);
	    break;
	  }
	  case 'A' : {
	    //admin for this group
	    return ($group_perm['admin_flags'] && $group_perm['admin_flags'] === 'A');
	    break;
	  }
	  case 'D1' : {
	    //document tech
	    return ($group_perm['doc_flags'] == 1 || $group_perm['doc_flags'] == 2);
	    break;
	  }
	  case 'D2' : {
	    //document admin
	    return ($group_perm['doc_flags'] == 2 || $group_perm['doc_flags'] == 3);
	    break;
	  }
	  case 'R2' : {
	    //file release admin
	    return ($group_perm['file_flags'] == 2);
	    break;
	  }
	  case 'W2': {
	    //wiki release admin
	    return ($group_perm['wiki_flags'] == 2);
	    break;
	  }
      case 'SVN_ADMIN': {
        //svn admin
        return ($group_perm['svn_flags'] == 2);
        break;
      }
      case 'N1': {
        //news write
        return ($group_perm['news_flags'] == 1);
        break;
      }
      case 'N2': {
        //news admin
        return ($group_perm['news_flags'] == 2);
        break;
      }
	  default : {
	    //fubar request
	    return false;
	  }
	  }
	}

    function isAnonymous() {
        return $this->id == 0;
    }
    
	function isValid() {
	  return is_array($this->tracker_data);
	}

	/** is this user admin of the tracker group_artifact_id */
	function isTrackerAdmin($group_id,$group_artifact_id) {
	  return ($this->getTrackerPerm($group_artifact_id) >= 2 || $this->isMember($group_id,'A'));
	}


	function getTrackerPerm($group_artifact_id) {
	  $has_perm = array_key_exists($group_artifact_id,$this->tracker_data);
	  if (!$has_perm) return 0;

	  $perm = $this->tracker_data[$group_artifact_id];

	  return $perm['perm_level'];
	}


	function isSuperUser() {
        if($this->isSuperUser === null) {
            $sql="SELECT * FROM user_group WHERE user_id='". $this->data_array['user_id'] ."' AND group_id='1' AND admin_flags='A'";
            $result=db_query($sql);
            if ($result && db_numrows($result) > 0) {
                $this->isSuperUser = true;
            } else {
                $this->isSuperUser = false;
            }
        }
        return $this->isSuperUser;
	}
    
    var $_ugroups;
    function &getUgroups($group_id, $instances) {
        $hash = md5(serialize($instances));
        if (!isset($this->_ugroups)) {
            $this->_ugroups = array();
        }
        if (!isset($this->_ugroups[$hash])) {
            $this->_ugroups[$hash] = array_merge($this->getDynamicUgroups($group_id, $instances), $this->getStaticUgroups($group_id));
        }
        return $this->_ugroups[$hash];
    }
    
    var $_static_ugroups;
    function getStaticUgroups($group_id) {
        if (!isset($this->_static_ugroups)) {
            $this->_static_ugroups = array();
            if (!$this->isSuperUser()) {
                $res = ugroup_db_list_all_ugroups_for_user($group_id, $this->id);
                while ($row = db_fetch_array($res)) {
                    $this->_static_ugroups[] = $row['ugroup_id'];
                }
            }
        }
        return $this->_static_ugroups;
    }
    
    var $_dynamics_ugroups;
    function getDynamicUgroups($group_id, $instances) {
        $hash = md5(serialize($instances));
        if (!isset($this->_dynamics_ugroups)) {
            $this->_dynamics_ugroups = array();
        }
        if (!isset($this->_dynamics_ugroups[$hash])) {
            $this->_dynamics_ugroups[$hash] = ugroup_db_list_dynamic_ugroups_for_user($group_id, $instances, $this->id);
        }
        return $this->_dynamics_ugroups[$hash];
    }
    
    //
    // Getter
    //
    
    /**
     * @return int the ID of the user
     */
    function getID() {
        return $this->id;
    }
    /**
     * @return string the name of the user (aka login)
     */
    function getName() {
        return $this->data_array['user_name'];
    }
    /**
     * @return string the real name of the user
     */
    function getRealName() {
        return $this->data_array['realname'];
    }
    /**
     * @return string the email adress of the user
     */
    function getEmail() {
        return $this->data_array['email'];
    }
    /**
     * @return string the Status of the user
     * 'A' = Active
     * 'R' = Restricted
     * 'D' = Deleted
     * 'S' = Suspended
     */
    function getStatus() {
        return $this->data_array['status'];
    }
    /**
     * @return string the registration date of the user (timestamp format)
     */
    function getAddDate() {
        return $this->data_array['add_date'];
    }
    /**
     * @return string the timezone of the user (GMT, Europe/Paris, etc ...)
     */
    function getTimezone() {
        return $this->data_array['timezone'];
    }
    /**
     * @return int 1 if the user accept to receive site mail updates, 0 if he does'nt
     */
    function getMailSiteUpdates() {
        return $this->data_array['mail_siteupdates'];
    }
    /**
     * @return int 1 if the user accept to receive additional mails from the community, 0 if he does'nt
     */
    function getMailVA() {
        return $this->data_array['mail_va'];
    }
    /**
     * @return int 0 or 1
     */
    function getStickyLogin() {
        return $this->data_array['sticky_login'];
    }
    /**
     * @return int font size preference of the user
     */
    function getFontSize() {
        return $this->data_array['fontsize'];
    }
    /**
     * @return string theme set in user's preferences
     */
    function getTheme() {
        return $this->data_array['theme'];
    }
    /**
     * @return string the Status of the user
     * '0' = (number zero) special value for the site admin
     * 'N' = No Unix Account
     * 'A' = Active
     * 'S' = Suspended
     * 'D' = Deleted
     */
    function getUnixStatus() {
        return $this->data_array['unix_status'];
    }
    /**
     * @return string unix box of the user
     */
    function getUnixBox() {
        return $this->data_array['unix_box'];
    }
    /**
     * @return string authorized keys of the user
     */
    function getAuthorizedKeys() {
        return $this->data_array['authorized_keys'];
    }
    /**
     * @return string resume of the user
     */
    function getPeopleResume() {
        return $this->data_array['people_resume'];
    }
    /**
     * @return int 1 if the user skills are public, 0 otherwise
     */
    function getPeopleViewSkills() {
        return $this->data_array['people_view_skills'];
    }
    /**
     * @return int ID of the language of the user
     */
    function getLanguageID() {
        return $this->data_array['language_id'];
    }
    /**
     * @return int Timestamp of the last authentication success.
     */
    function getLastAuthSuccess() {
        return $this->data_array['last_auth_success'];
    }
    /**
     * Return the previous authentication success (the one before last auth
     * success).
     * @return int Timestamp of the previous authentication success.
     */
    function getPreviousAuthSuccess() {
        return $this->data_array['prev_auth_success'];
    }
    /**
     * @return int Timestamp of the last unsuccessful authencation attempt
     */
    function getLastAuthFailure() {
        return $this->data_array['last_auth_failure'];
    }
    /**
     * @return int Number of authentication failure since the last success.
     */
    function getNbAuthFailure() {
        return $this->data_array['nb_auth_failure'];
    }
    /**
     * isActive - test if the user is active or not
     * 
     * @return boolean true if the user is active, false otherwise
     */
    function isActive() {
        return ($this->getStatus() == 'A');
    }

    /**
     * isRestricted - test if the user is restricted or not
     * 
     * @return boolean true if the user is restricted, false otherwise
     */
    function isRestricted() {
        return ($this->getStatus() == 'R');
    }

    /**
     * isDeleted - test if the user is deleted or not
     * 
     * @return boolean true if the user is deleted, false otherwise
     */
    function isDeleted() {
        return ($this->getStatus() == 'D');
    }

    /**
     * isSuspended - test if the user is suspended or not
     * 
     * @return boolean true if the user is suspended, false otherwise
     */
    function isSuspended() {
        return ($this->getStatus() == 'S');
    }

    /**
     * hasActiveUnixAccount - test if the unix account of the user is active or not
     * 
     * @return boolean true if the unix account of the user is active, false otherwise
     */
    function hasActiveUnixAccount() {
        return ($this->getUnixStatus() == 'A');
    }

    /**
     * hasSuspendedUnixAccount - test if the unix account of the user is suspended or not
     * 
     * @return boolean true if the unix account of the user is suspended, false otherwise
     */
    function hasSuspendedUnixAccount() {
        return ($this->getUnixStatus() == 'S');
    }

    /**
     * hasDeletedUnixAccount - test if the unix account of the user is deleted or not
     * 
     * @return boolean true if the unix account of the user is deleted, false otherwise
     */
    function hasDeletedUnixAccount() {
        return ($this->getUnixStatus() == 'D');
    }

    /**
     * hasNoUnixAccount - test if the user doesn't have a unix account
     * 
     * @return boolean true if the user doesn't have a unix account, false otherwise
     */
    function hasNoUnixAccount() {
        return ($this->getUnixStatus() == 'N');
    }
}

?>
