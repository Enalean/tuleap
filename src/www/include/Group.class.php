<?php   

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//	      
// $Id: Group.class.php 5732 2007-04-05 15:47:30 +0000 (Thu, 05 Apr 2007) nterray $

require_once('common/include/Error.class.php');
//require_once('common/include/Permission.class.php');
require_once('common/include/TemplateSingleton.class.php');

//$Language->loadLanguageMsg('include/include');

/*

	Group object by Tim Perdue, August 28, 2000

	Sets up database results and preferences for a group and abstracts this info

	Foundry.class.php and Project.class.php call this.

	Project.class.php contains all the deprecated API from the old group.php file



	GENERALLY YOU SHOULD NEVER CALL THIS OBJECT
	USE Project and Foundry instead
	DIRECT CALLS TO THIS OBJECT ARE NOT SUPPORTED

*/

/**
 *  group_get_object() - Get the group object.
 *
 *  group_get_object() is useful so you can pool group objects/save database queries
 *  You should always use this instead of instantiating the object directly.
 *
 *  You can now optionally pass in a db result handle. If you do, it re-uses that query
 *  to instantiate the objects.
 *
 *  IMPORTANT! That db result must contain all fields
 *  from groups table or you will have problems
 *
 *  @param		int		Required
 *  @param		int		Result set handle ("SELECT * FROM groups WHERE group_id=xx")
 *  @return a group object or false on failure
 */
function group_get_object($group_id,$res=false,$force_update=false) {
	//create a common set of group objects
	//saves a little wear on the database

	//returns appropriate object
	
	global $GROUP_OBJ;
	if (!isset($GROUP_OBJ["_".$group_id."_"]) || $force_update) {
		if ($res) {
			//the db result handle was passed in
		} else {
			$res=db_query("SELECT * FROM groups WHERE group_id='$group_id'");
		}
		if (!$res || db_numrows($res) < 1) {
			$GROUP_OBJ["_".$group_id."_"]=false;
		} else {
		        $GROUP_OBJ["_".$group_id."_"]= new Group($group_id,$res);
		}
	}
	return $GROUP_OBJ["_".$group_id."_"];
}

function group_get_object_by_name($groupname) {
	$res=db_query("SELECT * FROM groups WHERE unix_group_name='$groupname'");
	return group_get_object(db_result($res,0,'group_id'),$res);
}

function group_getid_by_name($groupname) {
	$res = db_query("SELECT group_id FROM groups WHERE unix_group_name='$groupname'");
	if (db_numrows($res) == 0) return false;
	else return db_result($res,0,'group_id');
}

class Group extends Error {

	//associative array of data from db
	var $data_array;

	var $group_id;

	//database result set handle
	var $db_result;

	//permissions data row from db
	var $perm_data_array;

	//membership data row from db
	var $members_data_array;

	//whether the user is an admin/super user of this project
	var $is_admin;

	function Group($id) {
	global $Language;
		$this->Error();
		$this->group_id=$id;
		$this->db_result=db_query("SELECT * FROM groups WHERE group_id='$id'");
		if (db_numrows($this->db_result) < 1) {
			//function in class we extended
			$this->setError($Language->getText('include_group','g_not_found'));
			$this->data_array=array();
		} else {
			//set up an associative array for use by other functions
			$this->data_array=db_fetch_array($this->db_result);
		}
	}


	/*
		Return database result handle for direct access

		Generall should NOT be used - here for supporting deprecated group.php
	*/
	function getData() {
		return $this->db_result;
	}


	/*
		Simply return the group_id for this object
	*/
	function getGroupId() {
		return $this->group_id;
	}


	/*
		Foundry, project, etc
	*/
	function getType() {
		return $this->data_array['type'];
	}


	function getUnixBox() {
	  return $this->data_array['unix_box'];
	}

	/*
		Statuses include I,H,A,D
	*/
	function getStatus() {
		return $this->data_array['status'];
	}

	/*
		Simple boolean test to see if it's a foundry or not
	*/
	function isFoundry() {
	  //foundries not supported so far
	  return false;

	}

	/*
		Simple boolean test to see if it's a project or not
	*/
	function isProject() {
	  $template =& TemplateSingleton::instance(); 
	  return $template->isProject($this->data_array['type']);
	}


	/*
		Simply returns the is_public flag from the database
	*/
	function isPublic() {
		return $this->data_array['is_public'];
	}

	/*
		Simply returns the hide_members flag from the database
	*/
	function hideMembers() {
		return $this->data_array['hide_members'];
	}


	/*
		Database field status of 'A' returns true
	*/
	function isActive() {
		if ($this->getStatus()=='A') {
			return true;
		} else {
			return false;
		}
	}

	function getUnixName($tolower = true) {
		return $tolower ? strtolower($this->data_array['unix_group_name']) : $this->data_array['unix_group_name'];
	}

	function getPublicName() {
	  return $this->data_array['group_name'];
	}

	//short description as entered on the group admin page
	function getDescription() {
	  return $this->data_array['short_description'];
	}

	//LJ Get the longest description provided during the 
        //LJ registration process.
	function getPurpose() {
		return $this->data_array['register_purpose'];
	}

	//date the group was registered
	function getStartDate() {
		return $this->data_array['register_time'];
	}

	function getHTTPDomain() {
	  return $this->data_array['http_domain'];
	}



	/**
	 *	getID - Simply return the group_id for this object.
	 *
	 *	@return int group_id.
	 */
	function getID() {
		return $this->data_array['group_id'];
	}

	/**
	 *	getMembersId - Return an array of user ids of group members
	 *
	 *	@return int group_id.
	 */
	function getMembersId() {
	    if ($this->members_data_array) {
		//list of members already built
	    } else {
		$res=db_query("SELECT user_id FROM user_group WHERE group_id='". $this->getGroupId() ."'");
		if ($res && db_numrows($res) > 0) {
		    $mb_array = array();
		    while ($row = db_fetch_array($res)) {
			$mb_array[] = $row[0];
		    }
		    $this->members_data_array = $mb_array;
		} else {
		    echo db_error();
		    $this->members_data_array=array();
		}
		db_free_result($res);
	    }
	    return $this->members_data_array;
	}
    
    
	/*

		Basic user permissions that apply to all Groups

	*/


	/*
		Simple test to see if the current user is a member of this project
	*/
	function userIsMember($field='user_id',$value=0) {
	    if ($this->userIsAdmin()) {
		//admins are tested first so that super-users can return true
		//and admins of a project should always have full privileges 
		//on their project
		return true;
	    } else {
		$arr=$this->getPermData();
		if (array_key_exists($field, $arr) && ($arr[$field] > $value)) {
		    return true;
		} else {
		    return false;
		}
	    }
	}

	/*
		User is an admin of the project
		or admin of the entire site
	*/
	function userIsAdmin() {
	    if (isset($this->is_admin)) {
		//have already been through here and set the var
	    } else {
		if (user_isloggedin()) {
		    //check to see if site super-user
		    $res=db_query("SELECT * FROM user_group WHERE user_id='". user_getid() ."' AND group_id='1' AND admin_flags='A'");
		    if ($res && db_numrows($res) > 0) {
			$this->is_admin = true;
		    } else {
			$arr=$this->getPermData();
			if (array_key_exists('admin_flags', $arr) && $arr['admin_flags']=='A') {
			    $this->is_admin = true;
			} else {
			    $this->is_admin = false;
			}
		    }
		    db_free_result($res);
		} else {
		    $this->is_admin = false;
		}
	    }
	    return $this->is_admin;
	}

	/*
		Return an associative array of permissions for this group/user
	*/
	function getPermData(){
	    if ($this->perm_data_array) {
		//have already been through here and set up perms data
	    } else {
		if (user_isloggedin()) {
		    $res=db_query("SELECT * FROM user_group WHERE user_id='".user_getid()."' and group_id='". $this->getGroupId() ."'");
		    if ($res && db_numrows($res) > 0) {
			$this->perm_data_array=db_fetch_array($res);
		    } else {
			echo db_error();
			$this->perm_data_array=array();
		    }
		    db_free_result($res);
		} else {
		    $this->perm_data_array=array();
		}
	    }
	    return $this->perm_data_array;
	}


	/** return true, if this group is a template to create other groups */
	function isTemplate() {
	  $template =& TemplateSingleton::instance(); 
	  return $template->isTemplate($this->data_array['type']);
	}


	/** return the template id from which this group was built */
	function getTemplate() {
	  return $this->data_array['built_from_template'];
	}

	function setType($type) {
	  db_query("UPDATE groups SET type='$type' WHERE group_id='".$this->group_id."'");
	}

}

?>
