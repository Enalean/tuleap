<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//	Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//

require_once('www/project/admin/permissions.php');
require_once('common/tracker/ArtifactFieldSetFactory.class.php');

$GLOBALS['Language']->loadLanguageMsg('tracker/tracker');

class ArtifactType extends Error {

	/**
	 * The Group object.
	 *
	 * @var		object	$Group.
	 */
	var $Group; 

	/**
	 * Current user permissions.
	 *
	 * @var		int		$current_user_perm.
	 */
	var $current_user_perm;


	/**
	 * Canned responses resource ID.
	 *
	 * @var		int		$cannecresponses_res.
	 */
	var $cannedresponses_res;

	/**
	 * Array of artifact data.
	 *
	 * @var		array	$data_array.
	 */
	var $data_array;

	/**
	 * number of notification events
	 *
	 * @var		array	
	 */
	var $num_events = 0;

	/**
	 * Array of events
	 *
	 * @var		array	
	 */
	var $arr_events = array();

	/**
	 * number of roles
	 *
	 * @var		array	
	 */
	var $num_roles = 0;

	/**
	 * Array of roles
	 *
	 * @var		array	
	 */
	var $arr_roles = array();

	/**
	 * Technicians db resource ID.
	 *
	 * @var		int		$admins_res.
	 */
   	var $admins_res;

	/**
	 *	ArtifactType - constructor.
	 *
	 *	@param	object	The Group object.
	 *	@param	int		The id # assigned to this artifact type in the db.
	 *  @param	array	The associative array of data.
	 *	@return boolean	success.
	 */
	function ArtifactType(&$Group,$artifact_type_id=false, $arr=false) {
	  global $Language;

		$this->Error();
		if (!$Group || !is_object($Group)) {
			$this->setError($Language->getText('tracker_common_type','invalid'));
			return false;
		}
		if ($Group->isError()) {
			$this->setError('ArtifactType: '.$Group->getErrorMessage());
			return false;
		}
		
		$this->Group =& $Group;
		if ($artifact_type_id) {
			$res_events = $this->getNotificationEvents($artifact_type_id);
			$this->num_events = db_numrows($res_events);
			$i=0;
			while ($arr_events = db_fetch_array($res_events)) {
    				$this->arr_events[$i] = $arr_events; $i++;
			}

			$res_roles = $this->getNotificationRoles($artifact_type_id);
			$this->num_roles = db_numrows($res_roles);
			$i=0;
			while ($arr_roles = db_fetch_array($res_roles)) {
    				$this->arr_roles[$i] = $arr_roles; $i++;
			}

			if (!$arr || !is_array($arr)) {
				if (!$this->fetchData($artifact_type_id)) {
					return false;
				}
			} else {
				$this->data_array = $arr;
				if ($this->data_array['group_id'] != $this->Group->getID()) {
					$this->setError($Language->getText('tracker_common_type','no_match'));
					$this->data_array = null;
					return false;
				}
			}
		}

		unset($this->admins_res);
		unset($this->current_user_perm);
		unset($this->cannedresponses_res);
	}

	/**
	 *	Create user permissions: Tech Only for group members and Tech & Admin for group admin
	 *
	 *	@param	atid: the artfact type id 
	 *
	 *	@return boolean
	 */
	function createUserPerms($atid) {
	  global $Language;

		$sql = "SELECT "
			. "user.user_id AS user_id, "
			. "user_group.admin_flags "
			. "FROM user,user_group WHERE "
			. "user.user_id=user_group.user_id AND user_group.group_id=".$this->Group->getID();
		$res = db_query($sql);
		
		while ($row = db_fetch_array($res)) {
			if ( $row['admin_flags'] == "A" ) {
				// Admin user
				$perm = 3;
					
			} else {
				// Standard user
				$perm = 0;
			}

			if ( !$this->addUser($row['user_id'],$perm) ) {
				$this->setError($Language->getText('tracker_common_type','perm_fail',$this->getErrorMessage()));
				return false;
			}
		}
		
		return true;

	}

	
	

	/**
	 *  fetch the notification roles for this ArtifactType from the database.
	 *
	 *  @param	int		The artifact type ID.
	 *  @return query result.
	 */
	function getNotificationRoles($artifact_type_id) {
	    $sql = 'SELECT * FROM artifact_notification_role WHERE group_artifact_id='.$artifact_type_id.' ORDER BY rank ASC;';
	    //$sql = 'SELECT * FROM artifact_notification_role_default ORDER BY rank ASC;';
	    //echo $sql.'<br>';
	    return db_query($sql);
	}

	/**
	 *  fetch the notification events for this ArtifactType from the database.
	 *
	 *  @param	int		The artifact type ID.
	 *  @return query result.
	 */
	function getNotificationEvents($artifact_type_id) {
	    $sql = 'SELECT * FROM artifact_notification_event WHERE group_artifact_id='.$artifact_type_id.' ORDER BY rank ASC;';
	    //$sql = 'SELECT * FROM artifact_notification_event_default ORDER BY rank ASC;';
	    //echo $sql.'<br>';
		return db_query($sql);
	}

	/**
	 *  fetchData - re-fetch the data for this ArtifactType from the database.
	 *
	 *  @param	int		The artifact type ID.
	 *  @return boolean	success.
	 */
	function fetchData($artifact_type_id) {
	  global $Language;

		$sql = "SELECT * FROM artifact_group_list
			WHERE group_artifact_id='$artifact_type_id' 
			AND group_id='". $this->Group->getID() ."'";
		$res=db_query($sql);
		if (!$res || db_numrows($res) < 1) {
			$this->setError('ArtifactType: '.$Language->getText('tracker_common_type','invalid_at'));
			return false;
		}
		$this->data_array = db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 *	  getGroup - get the Group object this ArtifactType is associated with.
	 *
	 *	  @return	Object	The Group object.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 *	  getID - get this ArtifactTypeID.
	 *
	 *	  @return	int	The group_artifact_id #.
	 */
	function getID() {
		return $this->data_array['group_artifact_id'];
	}

	/**
	 *	  getID - get this Artifact Group ID.
	 *
	 *	  @return	int	The group_id #.
	 */
	function getGroupID() {
		return $this->data_array['group_id'];
	}

	/**
	 *	  getOpenCount - get the count of open tracker items in this tracker type.
	 *
	 *	  @return	int	The count.
	 */
	function getOpenCount() {
            $count=$this->data_array['open_count'];
            return ($count?$count:0);
	}

	/**
	 *	  getTotalCount - get the total number of tracker items in this tracker type.
	 *
	 *	  @return	int	The total count.
	 */
	function getTotalCount() {
            $count=$this->data_array['count'];
            return ($count?$count:0);
	}

	/**
	 *	  isInstantiatedForNewProjects
	 *
	 *	  @return	boolean - true if the tracker is instantiated for new projects (tracker templates).
	 */
	function isInstantiatedForNewProjects() {
		return $this->data_array['instantiate_for_new_projects'];
	}

	/**
	 *	  allowsAnon - determine if non-logged-in users can post.
	 *
	 *	  @return	boolean allow_anonymous_submissions.
	 */
	function allowsAnon() {
            if (! isset($this->data_array['allow_anon'])) {
                // First, check that anonymous users can access the tracker
                if ($this->userCanView(100)) {
                    // Then check if they can submit a field
                    $this->data_array['allow_anon']=$this->userCanSubmit(100);
                } else $this->data_array['allow_anon']=false;

            }
            return $this->data_array['allow_anon'];
	}

	/**
	 *	  allowsCopy - determine if artifacts can be copied using a copy button
	 *
	 *	  @return	boolean	allow_copy.
	 */
	function allowsCopy() {
		return $this->data_array['allow_copy'];
	}

	/**
	 *	  getSubmitInstructions - get the free-form string strings.
	 *
	 *	  @return	string	instructions.
	 */
	function getSubmitInstructions() {
		return $this->data_array['submit_instructions'];
	}

	/**
	 *	  getBrowseInstructions - get the free-form string strings.
	 *
	 *	  @return string instructions.
	 */
	function getBrowseInstructions() {
		return $this->data_array['browse_instructions'];
	}

	/**
	 *	  getName - the name of this ArtifactType.
	 *
	 *	  @return	string	name.
	 */
	function getName() {
		return $this->data_array['name'];
	}

	/**
	 *	  getItemName - the item name of this ArtifactType.
	 *
	 *	  @return	string	name.
	 */
	function getItemName() {
		return $this->data_array['item_name'];
	}

	/**
	 *	  getCapsItemName - the item name of this ArtifactType with the first letter in caps.
	 *
	 *	  @return	string	name.
	 */
	function getCapsItemName() {
		return strtoupper(substr($this->data_array['item_name'],0,1)).substr($this->data_array['item_name'],1);
	}

	/**
	 *	  getDescription - the description of this ArtifactType.
	 *
	 *	  @return	string	description.
	 */
	function getDescription() {
		return $this->data_array['description'];
	}

	/**
	 *	  this tracker is not deleted
	 *
	 *	  @return boolean.
	 */
	function isValid() {
		return ($this->data_array['status'] == 'A');
	}



	/**
	 *	getCannedResponses - returns a result set of canned responses.
	 *
	 *	@return database result set.
	 */
	function getCannedResponses() {
		if (!isset($this->cannedresponses_res)) {
			$sql="SELECT artifact_canned_id,title,body
				FROM artifact_canned_responses 
				WHERE group_artifact_id='". $this->getID() ."'";
			//echo $sql;
			$this->cannedresponses_res = db_query($sql);
		}
		return $this->cannedresponses_res;
	}

	/**
	 *	addUser - add a user to this ArtifactType - depends on UNIQUE INDEX preventing duplicates.
	 *
	 *	@param	int		user_id of the new user.
	 *  @param  value: the value permission
	 *
	 *	@return boolean	success.
	 */
	function addUser($id,$value) {
	  global $Language;

		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		if (!$id) {
			$this->setError($Language->getText('tracker_common_canned','missing_param'));
			return false;
		}
		$sql="INSERT INTO artifact_perm (group_artifact_id,user_id,perm_level) 
			VALUES ('".$this->getID()."','$id',$value)";
		$result=db_query($sql);
		if ($result && db_affected_rows($result) > 0) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	existUser - check if a user is already in the project permissions
	 *
	 *	@param	int		user_id of the new user.
	 *	@return boolean	success.
	 */
	function existUser($id) {
	 global $Language;
 
		if (!$id) {
			$this->setError($Language->getText('tracker_common_canned','missing_param'));
			return false;
		}
		$sql="SELECT * FROM artifact_perm WHERE user_id=$id AND group_artifact_id=".$this->getID();
		$result=db_query($sql);
		if (db_numrows($result) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *	updateUser - update a user's permissions.
	 *
	 *	@param	int		user_id of the user to update.
	 *	@param	int		(1) tech only, (2) admin & tech (3) admin only.
	 *	@return boolean	success.
	 */
	function updateUser($id,$perm_level) {
	  global $Language;

		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		if (!$id) {
			$this->setError($Language->getText('tracker_common_canned','missing_param').': '.$id.'|'.$perm_level);
			return false;
		}
		$sql="UPDATE artifact_perm SET perm_level='$perm_level'
			WHERE user_id='$id' AND group_artifact_id='".$this->getID()."'";
		$result=db_query($sql);
		if ($result) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	deleteUser - delete a user's permissions.
	 *
	 *	@param	int		user_id of the user who's permissions to delete.
	 *	@return boolean	success.
	 */
	function deleteUser($id) {
	  global $Language;

		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		if (!$id) {
			$this->setError($Language->getText('tracker_common_canned','missing_param'));
			return false;
		}
		$sql="DELETE FROM artifact_perm
			WHERE user_id='$id' AND group_artifact_id='".$this->getID()."'";
		$result=db_query($sql);
		if ($result) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	preDelete - Mark this for deletion.
	 *
	 *	@return boolean	success.
	 */
	function preDelete() {
	  global $Language;

		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		$date = (time() + 1000000); // 12 days of delay
		$sql="update artifact_group_list SET status='D', deletion_date='$date'
			WHERE group_artifact_id='".$this->getID()."'";
		$result=db_query($sql);
		if ($result) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	delay - change date for deletion.
	 *
	 *	@return boolean	success.
	 */
	function delay($date) {
		global $sys_datefmt,$Language;
		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;		
		}
		$keywords = preg_split("/-/", $date);
		$ts = mktime("23", "59", "59", $keywords[1], $keywords[2], $keywords[0]);
		if (time() > $ts) {
		 	$this->setError($Language->getText('tracker_common_type','invalid_date'));
			return false;
		}
		$sql="update artifact_group_list SET deletion_date='$ts'
			WHERE group_artifact_id='".$this->getID()."'";
		$result=db_query($sql);
		if ($result) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	restore - Unmark this for deletion.
	 *
	 *	@return boolean	success.
	 */
	function restore() {
	  global $Language;

		if (!$this->userIsAdmin()) {
			$this->setError($Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		$sql="update artifact_group_list SET status='A'
			WHERE group_artifact_id='".$this->getID()."'";
		$result=db_query($sql);
		if ($result) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}

	/**
	 *	updateUsers - update the user's permissions.
	 *
	 *  @param atid: the group artifact id
	 *	@param array: the array which contains the user permissions.
	 *	@return boolean	success.
	 */
	function updateUsers($atid,$user_name) {
	  global $Language;

	    $result=$this->getUsersPerm($this->getID());
	    $rows=db_numrows($result);
	
	    if ( ($rows > 0)&&(is_array($user_name)) ) {

			$update_error = "";
				
			for ($i=0; $i < $rows; $i++) {
				$user_id = db_result($result, $i, 'user_id');
				$sql = "update artifact_perm set perm_level = ".$user_name[$i]." where ";
				$sql .= "group_artifact_id = ".$atid." and user_id = ".$user_id;
				//echo $sql."<br>";
				$result2=db_query($sql);
				if (!$result2) {
					$update_error .= " ".$Language->getText('tracker_common_type','perm_err',array($user_id,db_error()));
				}
				
			}
			
			if ($update_error) {
				$this->setError($update_error);
				return false;
			} else {
				return true;
			}
		}
		
		return false;
	}

	/*

		USER PERMISSION FUNCTIONS

	*/

	/**
	 *	  userCanView - determine if the user can view this artifact type.
         *        Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
	 *
	 *	  @param $my_user_id	if not specified, use the current user id..
	 *	  @return boolean	user_can_view.
	 */
	function userCanView($my_user_id=0) {
            if (!$my_user_id) {
                // Super-user has all rights...
                if (user_is_super_user()) return true;
                $my_user_id=user_getid();
            } else {
                $u = new User($my_user_id);
                if ($u->isSuperUser()) return true;
            }
            
            if ($this->userIsAdmin($my_user_id)) {
                return true;
            } else {
            
                $sql="SELECT ugroup_id FROM permissions WHERE permission_type LIKE 'TRACKER_ACCESS%' AND object_id='".$this->getID()."' ORDER BY ugroup_id";
                $res=db_query($sql);

                if (db_numrows($res) > 0) {
                    while ($row = db_fetch_array($res)) {
                        // should work even for anonymous users
                        if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }


	/**
	 *	  userHasFullAccess - A bit more restrictive than userCanView: determine if the user has
         *        the 'TRACKER_ACCESS_FULL' permission on the tracker.
	 *
	 *	  @param $my_user_id	if not specified, use the current user id..
	 *	  @return boolean
	 */
	function userHasFullAccess($my_user_id=0) {
            if (!$my_user_id) {
                // Super-user has all rights...
                if (user_is_super_user()) return true;
                $my_user_id=user_getid();
            } else {
                $u = new User($my_user_id);
                if ($u->isSuperUser()) return true;
            }
            
            $sql="SELECT ugroup_id FROM permissions WHERE permission_type='TRACKER_ACCESS_FULL' AND object_id='".$this->getID()."' ORDER BY ugroup_id";
            $res=db_query($sql);

            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    // should work even for anonymous users
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                        return true;
                    }
                }
            }
            
            return false;
        }

	/**
	 *	userIsAdmin - see if the logged-in user's perms are >= 2 or project admin.
	 *
	 *	@return boolean
	 */
	function userIsAdmin() { 

		if ( ($this->getCurrentUserPerm() >= 2) || (user_ismember($this->Group->getID(),'A')) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 *	  userCanSubmit - determine if the user can submit an artifact (if he can submit a field).
         *        Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
	 *
	 *	  @param $my_user_id	if not specified, use the current user id..
	 *	  @return boolean	user_can_submit.
	 */
        function userCanSubmit($my_user_id=0) {

            if (!$my_user_id) {
                // Super-user has all rights...
                if (user_is_super_user()) return true;
                $my_user_id=user_getid();
            } else {
                $u = new User($my_user_id);
                if ($u->isSuperUser()) return true;
            }

            // Select submit permissions for all fields
            $sql="SELECT ugroup_id FROM permissions WHERE permission_type='TRACKER_FIELD_SUBMIT' AND object_id LIKE '".$this->getID()."#%' GROUP BY ugroup_id";
            $res=db_query($sql);
            
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    // should work even for anonymous users
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->Group->getID(), $this->getID())) {
                        return true;
                    }
                }
            }
            
            return false;
        }

	/**
	 *	getCurrentUserPerm - get the logged-in user's perms from artifact_perm.
	 *
	 *	@return int perm level for the logged-in user.
	 */
	function getCurrentUserPerm() {
		if (!user_isloggedin()) {
			return 0;
		} else {
			if (!isset($this->current_user_perm)) {
				$sql="select perm_level
				FROM artifact_perm
				WHERE group_artifact_id='". $this->getID() ."'
				AND user_id='".user_getid()."'";
				//echo $sql;
				$this->current_user_perm=db_result(db_query($sql),0,0);
			}
			return $this->current_user_perm;
		}
	}

	/**
	 *	getUserPerm - get a user's perms from artifact_perm.
	 *
	 *	@return int perm level for a user.
	 */
	function getUserPerm($user_id) {
		$sql="select perm_level
		FROM artifact_perm
		WHERE group_artifact_id='". $this->getID() ."'
		AND user_id='".$user_id."'";
		//echo $sql."<br>";
		return db_result(db_query($sql),0,0);
	}

	/**
	 * Get permissions for all fields based on the ugroups the user is part of
	 *
	 */
	function getFieldPermissions($ugroups) {
	  $art_field_fact = new ArtifactFieldFactory($this);
	  $used_fields = $art_field_fact->getAllUsedFields();
	  $field_perm = array();

	  reset($used_fields);
	  foreach ($used_fields as $field) {
	    $perm = $field->getPermissionForUgroups($ugroups,$this->getID());
	    if ($perm && !empty($perm)) {
	      $field_perm[$field->getName()] = $perm;
	    }
	  }
	  return $field_perm;
	}

	/**
	 *  update - use this to update this ArtifactType in the database.
	 *
	 *  @param	string	The item name.
	 *  @param	string	The item description.
	 *  @param	int		Days before this item is considered overdue.
	 *  @param	int		Days before stale items time out.
	 *  @param	bool	(1) true (0) false - whether the resolution box should be shown.
	 *  @param	string	Free-form string that project admins can place on the submit page.
	 *  @param	string	Free-form string that project admins can place on the browse page.
	 *  @param	bool	instantiate_for_new_projects (1) true (0) false - instantiate this tracker template for new projects
	 *  @return true on success, false on failure.
	 */
	function update($name,$description,$itemname,$allow_copy,
		            $submit_instructions,$browse_instructions,$instantiate_for_new_projects) {
	  global $Language;

		if ( !$this->userIsAdmin() ) {
			$this->setError('ArtifactType: '.$Language->getText('tracker_common_canned','perm_denied'));
			return false;
		}
		
		if (!$name || !$description || !$itemname || trim($name) == "" || trim($description) == "" || trim($itemname) == ""  ) {
			$this->setError('ArtifactType: '.$Language->getText('tracker_common_type','name_requ'));
			return false;
		}
		
		$allow_copy = ((!$allow_copy) ? 0 : $allow_copy);
                $instantiate_for_new_projects = ((!$instantiate_for_new_projects) ? 0 : $instantiate_for_new_projects); 

		$sql="UPDATE artifact_group_list SET 
			name='$name',
			description='$description',
			item_name='$itemname',
                        allow_copy='$allow_copy',
			submit_instructions='$submit_instructions',
			browse_instructions='$browse_instructions',
                        instantiate_for_new_projects='$instantiate_for_new_projects'
			WHERE 
			group_artifact_id='". $this->getID() ."' 
			AND group_id='". $this->Group->getID() ."'";

		//echo $sql;
		
		$res=db_query($sql);
		if (!$res) {
			$this->setError('ArtifactType::Update(): '.db_error());
			return false;
		} else {
			$this->fetchData($this->getID());
			return true;
		}
	}


	/**
	 *  updateNotificationSettings - use this to update this ArtifactType in the database.
	 *
	 *  @param	int	uid the user to set watches on
	 *  @param	string	the list of users to watch
	 *  @param	string	the list of watching users
	 *  @return true on success, false on failure.
	 */
	function updateNotificationSettings($user_id, $watchees, &$feedback) {
	    $this->setWatchees($user_id, $watchees);
        $this->fetchData($this->getID());
        return true;
	}
	
	/**
	 *  updateDateFieldReminderSettings - use this to update the date-fields reminder settings in the database.
	 *
	 *  @param	$field_id	The date field concerned by the notification.
	 *  @param	$group_artifact_id	The tracker id
	 *  @param	$start	When will the notification start taking effect, with regards to date occurence (in days)
	 *  @param	$type	What is the type of the notification (after date occurence, before date occurence)
	 *  @param	$frequency	At which frequency (in days) the notification wil occur
	 *  @param	$recurse	How many times the notification mail will be sent
	 *  @param	$submitter	Is submitter notified ?
	 *  @param	$assignee	Is assignee notified ?
	 *  @param	$cc	Is cc notified ?
	 *  @param	$commenter	Is commetner notified ?
	 * 
	 *  @return true on success, false on failure.
	 */	
	function updateDateFieldReminderSettings($field_id,$group_artifact_id,$start,$notif_type,$frequency,$recurse,$people_notified) {
	       
	    $notified_users = implode(",",$people_notified);
	  
	    //update reminder settings
	    $update = sprintf('UPDATE artifact_date_reminder_settings'
			     .' SET notification_start=%d'
			     .' , notification_type=%d'
			     .' , frequency=%d'
			     .' , recurse=%d'
			     .' , notified_people="%s"'
			     .' WHERE group_artifact_id=%d'
			     .' AND field_id=%d',
			     $start,$notif_type,$frequency,$recurse,$notified_users,$group_artifact_id,$field_id);		
	    $result = db_query($update);	    
	    
	    return $result;	  
	    
	}
	
	/**
	* Add artifact to artifact_date_reminder_processing table
	* 
	*  @param field_id: the field id
	*  @param artifact_id: the artifact id
	*  @param group_artifact_id: the tracker id
	* 
	* @return nothing
	*/
	function addArtifactToDateReminderProcessing($field_id,$artifact_id,$group_artifact_id) {
	
	    $art_field_fact = new ArtifactFieldFactory($this);
	    
	    if ($field_id <> 0) {
		$sql = sprintf('SELECT * FROM artifact_date_reminder_settings'			       
			       .' WHERE group_artifact_id=%d'
			       .' AND field_id=%d',
			       $group_artifact_id,$field_id);
	    } else {
	  	$sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			       .' WHERE group_artifact_id=%d',
			       $group_artifact_id);
	    }
	    $res = db_query($sql);
	    if (db_numrows($res) > 0) {
	        while ($rows = db_fetch_array($res)) {
		    $reminder_id = $rows['reminder_id'];
		    $fid = $rows['field_id'];
		    $field = $art_field_fact->getFieldFromId($fid);  		    
		    
		    $sql1 = sprintf('SELECT * FROM artifact_field_value'
			           .' WHERE artifact_id=%d'
			           .' AND field_id=%d',
				   $artifact_id,$fid);
		    $res1 = db_query($sql1);

		    
		    
		    if (! $field->isStandardField()) {
		        if (db_numrows($res1) > 0) {
			    $valueDate = db_result($res1,0,'valueDate');
	                    if ($valueDate <> 0 && $valueDate <> NULL) {    
			        //the date field is not special (value is stored in 'artifact_field_value' table)
	                        $ins = sprintf('INSERT INTO artifact_date_reminder_processing'
						.' (reminder_id,artifact_id,field_id,group_artifact_id,notification_sent)'
						.' VALUES(%d,%d,%d,%d,%d)',
						$reminder_id,$artifact_id,$fid,$group_artifact_id,0);
			        $result = db_query($ins);
			    }
			}
		    } else {
		        //End Date
		        $sql2 = sprintf('SELECT * FROM artifact'
					.' WHERE artifact_id=%d'
					.' AND group_artifact_id=%d',
					$artifact_id,$group_artifact_id);
		        $res2 = db_query($sql2);
			if (db_numrows($res2) > 0) {
		            $close_date = db_result($res2,0,'close_date');
			    if ($close_date <> 0 && $close_date <> NULL) {
			        $ins = sprintf('INSERT INTO artifact_date_reminder_processing'
						.' (reminder_id,artifact_id,field_id,group_artifact_id,notification_sent)'
						.' VALUES(%d,%d,%d,%d,%d)',
						$reminder_id,$artifact_id,$fid,$group_artifact_id,0);
			        $result = db_query($ins);
			    }
			}
		    }
		}
	    }

	}
	
	/**
	* Delete artifact from artifact_date_reminder_processing table
	* 
	*  @param field_id: the field id
	*  @param artifact_id: the artifact id
	*  @param group_artifact_id: the tracker id
	* 
	* @return nothing
	*/	
	function deleteArtifactFromDateReminderProcessing($field_id,$artifact_id,$group_artifact_id) {
	    
	    if ($field_id == 0) {  
	        $del = sprintf('DELETE FROM artifact_date_reminder_processing'
			       .' WHERE artifact_id=%d'
			       .' AND group_artifact_id=%d',
			       $artifact_id,$group_artifact_id);
	    } else {
	        $del = sprintf('DELETE FROM artifact_date_reminder_processing'
				.' WHERE artifact_id=%d'
				.' AND field_id=%d'
				.' AND group_artifact_id=%d',
				$artifact_id,$field_id,$group_artifact_id);
	    }
	    $result = db_query($del);	    
	    
	}

	function deleteWatchees($user_id) {

    		$sql = "DELETE FROM artifact_watcher WHERE user_id='$user_id' AND artifact_group_id='".$this->getID()."'";
		//echo $sql."<br>";
    		return db_query($sql);
	}
	
	function getWatchees($user_id) {
	    $sql = "SELECT watchee_id FROM artifact_watcher WHERE user_id='$user_id' AND artifact_group_id=".$this->getID();
		//echo $sql."<br>";
	    return db_query($sql);    
	}
	
	function setWatchees($user_id, $watchees) {
	    global $Language;
		//echo "setWatchees($user_id, $watchees)<br>";
		if ($watchees) {
			//echo "watchees";
   			$res_watch = true;
            $arr_user_names = split('[,;]', $watchees);
			$arr_user_ids = array();
			while (list(,$user_name) = each($arr_user_names)) {
			    $user_ident = util_user_finder($user_name, true);
                $res = user_get_result_set_from_unix($user_ident);
			    if (!$res || (db_numrows($res) <= 0)) {
				// user doesn;t exist  so abort this step and give feedback
				$this->setError(" - ".$Language->getText('tracker_common_type','invalid_name',$user_name));
				$res_watch = false;
				continue;
			    } else {
				// store in a hash to eliminate duplicates. skip user itself
				if (db_result($res,0,'user_id') != $user_id)
				    $arr_user_ids[db_result($res,0,'user_id')] = 1;
			    }
			}
			
			if ($res_watch) {
			    $this->deleteWatchees($user_id); 
			    $arr_watchees = array_keys($arr_user_ids);
			    $sql = 'INSERT INTO artifact_watcher (artifact_group_id, user_id,watchee_id) VALUES ';
    			    $num_watchees = count($arr_watchees);
    			    for ($i=0; $i<$num_watchees; $i++) {
				$sql .= "('".$this->getID()."','$user_id','".$arr_watchees[$i]."'),";
    			    } 
    			$sql = substr($sql,0,-1); // remove extra comma at the end 
			//echo $sql."<br>";
    			return db_query($sql);

			}   
		} else 
			$this->deleteWatchees($user_id);	
	}
	
	function getWatchers($user_id) {
	    $sql = "SELECT user_id FROM artifact_watcher WHERE watchee_id='$user_id' AND artifact_group_id=".$this->getID();
	    return db_query($sql);    
	}
	
	function deleteNotification($user_id) {
	    $sql = "DELETE FROM artifact_notification WHERE user_id='$user_id' AND group_artifact_id='".$this->getID()."'";
	    //echo $sql."<br>";
	    return db_query($sql);
	}

	function setNotification($user_id,$arr_notification) {
	    $sql = 'INSERT INTO artifact_notification (group_artifact_id, user_id,role_id,event_id,notify) VALUES ';
	
	    for ($i=0; $i<$this->num_roles; $i++) {
			$role_id = $this->arr_roles[$i]['role_id'];
			for ($j=0; $j<$this->num_events; $j++) { 
			$event_id = $this->arr_events[$j]['event_id'];
			$sql .= "('".$this->getID()."','$user_id','$role_id','$event_id','".$arr_notification[$role_id][$event_id]."'),"; 
			} 
		} 
		$sql = substr($sql,0,-1); // remove extra comma at the end 
		//echo $sql."<br>";
		return db_query($sql); 
	}

	//
	// People who are project members
	function getGroupMembers () {
		$group_id = $this->Group->getID();
		$sql="(SELECT user.user_id,user.user_name ".
			"FROM user,user_group ".
			"WHERE (user.user_id=user_group.user_id ".
			"AND user_group.group_id='$group_id') ".
			"ORDER BY user.user_name)";
		return $sql;
	}
	
	// People who have once submitted a bug
	function getSubmitters () {
		$group_artifact_id = $this->getID();
		$sql="(SELECT DISTINCT user.user_id,user.user_name ".
			"FROM user,artifact ".
			"WHERE (user.user_id=artifact.submitted_by ".
			"AND artifact.group_artifact_id='$group_artifact_id') ".
			"ORDER BY user.user_name)";
		return $sql;
	}
		
	//
	// People who are project admins
	function getGroupAdmins () {
		$group_id = $this->Group->getID();
		$sql="(SELECT DISTINCT user.user_id,user.user_name ".
			"FROM user,user_group ".
			"WHERE (user.user_id=user_group.user_id ".
			"AND user_group.group_id='$group_id') ". 
		        "AND user_group.admin_flags = 'A' ".			
			"ORDER BY user.user_name)";
		return $sql;
	}


	/**
	 *	getTrackerAdmins - returns a result set of this trackers administrators. 
	 *
	 *	@return database result set.
	 */
	function getTrackerAdmins() {
	  global $display_debug;
          
	  if (!isset($this->admins_res)) {
	    $sql="(SELECT user.user_id, user.user_name ". 
	      "FROM artifact_perm ap, user ".
	      "WHERE (user.user_id = ap.user_id) and ".
	      "group_artifact_id=". $this->getID()." ".
	      "AND perm_level in (2,3))";
	    //echo "sql=$sql<br>";
	    $this->admins_res = db_query($sql);
            
	    if ( $display_debug ) {
	      $rows = db_numrows($this->admins_res);
	      echo "<DBG:ArtifactType.getTrackerAdmins>sql=".$sql."<br>";
	      for($i=0;$i<$rows;$i++) {
		echo db_result($this->admins_res, $i, 'user_name')."<br>";
	      }
	    }
	  }
	  return $sql;
	}

	function getUsersPerm($group_artifact_id) {
		$sql="SELECT u.user_id,u.user_name,au.perm_level ".
			"FROM user u,artifact_perm au ".
			"WHERE u.user_id=au.user_id AND au.group_artifact_id=".$group_artifact_id." ".
			"ORDER BY u.user_name";
		//echo $sql;
		return db_query($sql);
	}

	/**
	 * Copy notification event from default
	 *
	 * @param group_artifact_id: the destination artifact type id
	 *
	 * @return boolean
	 */
	function copyNotificationEvent($group_artifact_id) {
	    global $Language;
		$sql = "insert into artifact_notification_event ".
			   "select event_id,".$group_artifact_id.",event_label,rank,short_description_msg,description_msg ".
			   "from artifact_notification_event_default";
			   
		$res_insert = db_query($sql);
		
		if (!$res_insert || db_affected_rows($res_insert) <= 0) {
			$this->setError($Language->getText('tracker_common_type','copy_fail'));
			return false;
		}
		
		return true;
	}

	/**
	 * Copy notification role from default
	 *
	 * @param group_artifact_id: the destination artifact type id
	 *
	 * @return boolean
	 */
	function copyNotificationRole($group_artifact_id) {
	    global $Language;
		$sql = "insert into artifact_notification_role ".
			   "select role_id,".$group_artifact_id.",role_label ,rank, short_description_msg,description_msg ".
			   "from artifact_notification_role_default";
			   
		$res_insert = db_query($sql);
		
		if (!$res_insert || db_affected_rows($res_insert) <= 0) {
			$this->setError($Language->getText('tracker_common_type','notif_fail'));
			return false;
		}
		
		return true;
	}
 
	/**
	 *
	 * Get artifacts by age
	 *
	 * @return boolean
	 */
	function getOpenArtifactsByAge() {
		$time_now=time();
		//			echo $time_now."<P>";
		
		for ($counter=1; $counter<=8; $counter++) {
		
			$start=($time_now-($counter*604800));
			$end=($time_now-(($counter-1)*604800));
			
			$sql="SELECT count(*) FROM artifact WHERE open_date >= $start AND open_date <= $end AND status_id = '1' AND group_artifact_id='".$this->getID()."'";
			
			$result = db_query($sql);
			
			$names[$counter-1]=format_date("m/d/y",($start))." to ".format_date("m/d/y",($end));
			if (db_numrows($result) > 0) {
				$values[$counter-1]=db_result($result, 0,0);
			} else {
				$values[$counter-1]='0';
			}
		}
		
		$results['names'] = $names;
		$results['values'] = $values;
		
		return $results;
	
	}
	
	/**
	 *
	 * Get artifacts by age
	 *
	 * @return boolean
	 */
	function getArtifactsByAge() {
		$time_now=time();
		
		for ($counter=1; $counter<=8; $counter++) {
		
			$start=($time_now-($counter*604800));
			$end=($time_now-(($counter-1)*604800));
			
			$sql="SELECT avg((close_date-open_date)/86400) FROM artifact WHERE close_date > 0 AND (open_date >= $start AND open_date <= $end) AND status_id <> '1' AND group_artifact_id='".$this->getID()."'";
			
			$result = db_query($sql);
			$names[$counter-1]=format_date("m/d/y",($start))." to ".format_date("m/d/y",($end));		
			if (db_numrows($result) > 0) {
				$values[$counter-1]=db_result($result, 0,0);
			} else {
				$values[$counter-1]='0';
			}
		}
		
		$results['names'] = $names;
		$results['values'] = $values;
		
		return $results;
		
	}
	
	/**
	 *
	 * Get artifacts grouped by standard field
	 *
	 * @return boolean
	 */
	function getArtifactsBy($field) {
	
		$sql="SELECT ".$field->getName().", count(*) AS Count FROM artifact ".
		" WHERE  artifact.group_artifact_id=".$this->getID().
		" GROUP BY ".$field->getName();
		
		$result=db_query($sql);
		if ($result && db_numrows($result) > 0) {
			for ($j=0; $j<db_numrows($result); $j++) {
				if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
					$labelValue = $field->getLabelValues($this->getID(), array(db_result($result, $j, 0)));
					$names[$j] = $labelValue[0];
				} else {
					$names[$j] = db_result($result, $j, 0);
				}
				$values[$j]= db_result($result, $j, 1);
			}
		}
		
		$results['names'] = $names;
		$results['values'] = $values;
		
		return $results;
	}
	
	/**
	 *
	 * Get open artifacts grouped by standard field
	 *
	 * @return boolean
	 */
	function getOpenArtifactsBy($field) {
	
		$sql="SELECT ".$field->getName().", count(*) AS Count FROM artifact ".
		" WHERE artifact.group_artifact_id='".$this->getID()."' ".
		" AND artifact.status_id=1".
		" GROUP BY ".$field->getName();
				
		$result = db_query($sql);
		if ($result && db_numrows($result) > 0) {
			for ($j=0; $j<db_numrows($result); $j++) {
				if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
					$labelValue = $field->getLabelValues($this->getID(), array(db_result($result, $j, 0)));
					$names[$j] = $labelValue[0];
				} else {
					$names[$j] = db_result($result, $j, 0);
				}
				$values[$j]= db_result($result, $j, 1);
			}
		}
		
		$results['names'] = $names;
		$results['values'] = $values;
		
		return $results;
	}
	
	/**
	 *
	 * Get artifacts grouped by field
	 *
	 * @return boolean
	 */
	function getArtifactsByField($field) {
	
		$sql="SELECT ".$field->getValueFieldName().", count(*) AS Count FROM artifact_field_value, artifact ".
		    " WHERE  artifact.group_artifact_id='".$this->getID()."' ".
		    " AND artifact_field_value.artifact_id=artifact.artifact_id".
		    " AND artifact_field_value.field_id=".$field->getID().
		    " GROUP BY ".$field->getValueFieldName();
		
		$result = db_query($sql);
		if ($result && db_numrows($result) > 0) {
			for ($j=0; $j<db_numrows($result); $j++) {
				if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
					$labelValue = $field->getLabelValues($this->getID(), array(db_result($result, $j, 0)));
					$names[$j] = $labelValue[0];
				} else {
					$names[$j] = db_result($result, $j, 0);
				}
				
				$values[$j]= db_result($result, $j, 1);
			}
			$results['names'] = $names;
			$results['values'] = $values;

		}
		return $results;
	}
	
	/**
	 *
	 * Get open artifacts grouped by field
	 *
	 * @return boolean
	 */
	function getOpenArtifactsByField($field) {
	
		$sql="SELECT ".$field->getValueFieldName().", count(*) AS Count FROM artifact_field_value, artifact ".
		" WHERE  artifact.group_artifact_id='".$this->getID()."' ".
		" AND artifact_field_value.artifact_id=artifact.artifact_id".
		" AND artifact_field_value.field_id=".$field->getID().
		" AND artifact.status_id=1".  
		" GROUP BY ".$field->getValueFieldName();
		
		$result = db_query($sql);
		if ($result && db_numrows($result) > 0) {
			for ($j=0; $j<db_numrows($result); $j++) {
				if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
					$labelValue = $field->getLabelValues($this->getID(), array(db_result($result, $j, 0)));
					$names[$j] = $labelValue[0];
				} else {
					$names[$j] = db_result($result, $j, 0);
				}
				
				$values[$j]= db_result($result, $j, 1);
			}
			$results['names'] = $names;
			$results['values'] = $values;
		
		}
		return $results;
	}
	


	/**
	 * Check if for a user and for role, there is a change
	 *
	 * @param user_id: the user id
	 * @param role: the role
	 * @param changes: array of changes
	 *
	 * @return boolean
	 */
	function checkNotification($user_id, $role, $changes=false) {
	
	    $send = false;
	    $arr_notif = $this->buildNotificationMatrix($user_id);
	    if (!$arr_notif) { return true; }
	
	    // echo "==== DBG Checking Notif. for $user_id (role=$role)<br>";
	    $user_name = user_getname($user_id);
	
	    //----------------------------------------------------------
	    // If it's a new bug only (changes is false) check the NEW_BUG event and
	    // ignore all other events
	    if ($changes==false) {
			if ($arr_notif[$role]['NEW_ARTIFACT']) {
			    // echo "DBG NEW_ARTIFACT notified<br>";
			    return true;
			} else {
			    // echo "DBG No notification<br>";
			    return false;
			}
	    }
	
	    //----------------------------------------------------------
	    //Check: I_MADE_IT  (I am the author of the change )
	    // Check this one first because if the user said no she doesn't want to be 
	    // aware of any of her change in this role and we can return immediately.
	    if (($user_id == user_getid()) && !$arr_notif[$role]['I_MADE_IT']) {
			// echo "DBG Dont want to receive my own changes<br>";
			return false;
	    }
	    
	    //----------------------------------------------------------
	    // Check :  NEW_COMMENT  A new followup comment is added 
	    if ($arr_notif[$role]['NEW_COMMENT'] && isset($changes['comment'])) {
			// echo "DBG NEW_COMMENT notified<br>";
			return true;
	    }
	
	    //----------------------------------------------------------
	    //Check: NEW_FILE  (A new file attachment is added)
	    if ($arr_notif[$role]['NEW_FILE'] && isset($changes['attach'])) {
			// echo "DBG NEW_FILE notified<br>";
			return true;
	    }
	  
	    //----------------------------------------------------------
	    //Check: CLOSED  (The bug is closed)
	    // Rk: this one has precedence over PSS_CHANGE. So notify even if PSS_CHANGE
	    // says no.
	    if ($arr_notif[$role]['CLOSED'] && ($changes['status_id']['add'] == 'Closed')) {
			// echo "DBG CLOSED bug notified<br>";
			return true;
	    }
	
	    //----------------------------------------------------------
	    //Check: PSS_CHANGE  (Priority,Status,Severity changes)
	    if ($arr_notif[$role]['PSS_CHANGE'] && 
		(isset($changes['priority']) || isset($changes['status_id']) || isset($changes['severity'])) ) {
			// echo "DBG PSS_CHANGE notified<br>";
			return true;
	    }
	
	
	    //----------------------------------------------------------
	    // Check :  ROLE_CHANGE (I'm added to or removed from this role)
	    // Rk: This event is meanningless for Commenters. It also is for submitter but may be
	    // one day the submitter will be changeable by the project admin so test it.
	    // Rk #2: check this one at the end because it is the most CPU intensive and this
	    // event seldomly happens
	    if ($arr_notif['SUBMITTER']['ROLE_CHANGE'] &&
		(($changes['submitted_by']['add'] == $user_name) || ($changes['submitted_by']['del'] == $user_name)) &&
		($role == 'SUBMITTER') ) {
			// echo "DBG ROLE_CHANGE for submitter notified<br>";
			return true;
	    }
	
	    if ($arr_notif['ASSIGNEE']['ROLE_CHANGE'] &&
		(($changes['assigned_to']['add'] == $user_name) || ($changes['assigned_to']['del'] == $user_name)) &&
		($role == 'ASSIGNEE') ) {
			// echo "DBG ROLE_CHANGE for role assignee notified<br>";
			return true;
	    }
	
	    $arr_cc_changes = array();
	    if (isset($changes['CC']['add']))
			$arr_cc_changes = split('[,;]',$changes['CC']['add']);
	    $arr_cc_changes[] = $changes['CC']['del'];
	    $is_user_in_cc_changes = in_array($user_name,$arr_cc_changes);    
	    $are_anyother_user_in_cc_changes =
		(!$is_user_in_cc_changes || count($arr_cc_changes)>1);    
	
	    if ($arr_notif['CC']['ROLE_CHANGE'] && ($role == 'CC')) {
			if ($is_user_in_cc_changes) {
			    // echo "DBG ROLE_CHANGE for cc notified<br>";
			    return true;
			}
	    }
	    
	    //----------------------------------------------------------
	    //Check: CC_CHANGE  (CC_CHANGE is added or removed)
	    // check this right after because  role cahange for cc can contradict
	    // thee cc_change notification. If the role change on cc says no notification
	    // then it has precedence over a cc_change
	    if ($arr_notif[$role]['CC_CHANGE'] && isset($changes['CC'])) {
			// it's enough to test role against 'CC' because if we are at that point
			// it means that the role_change for CC was false or that role is not CC
			// So if role is 'CC' and we are here it means that the user asked to not be
			// notified on role_change as CC, unless other users are listed in the cc changes
			if (($role != 'CC') || (($role == 'CC') && $are_anyother_user_in_cc_changes)) {
			    // echo "DBG CC_CHANGE notified<br>";
			    return true; 
			}
	    }
	
	
	    //----------------------------------------------------------
	    //Check: CHANGE_OTHER  (Any changes not mentioned above)
	    // *** THIS ONE MUST ALWAYS BE TESTED LAST
	    
	    // Delete all tested fields from the $changes array. If any remains then it
	    // means a notification must be sent
	    unset($changes['comment']);
	    unset($changes['attach']);
	    unset($changes['priority']);
	    unset($changes['severity']);
	    unset($changes['status_id']);
	    unset($changes['CC']);
	    unset($changes['assigned_to']);
	    unset($changes['submitted_by']);
	    if ($arr_notif[$role]['ANY_OTHER_CHANGE'] && count($changes)) {
			// echo "DBG ANY_OTHER_CHANGE notified<br>";
			return true;
	    }
	
	    // Sorry, no notification...
	    // echo "DBG No notification!!<br>";
	    return false;
	}

	/**
	 * Build the matrix role/event=notify
	 *
	 * @param user_id: the user id
	 *
	 * @return array
	 */
	function buildNotificationMatrix($user_id) {
	
	    // Build the notif matrix indexed with roles and events labels (not id)
	    $res_notif = $this->getNotificationWithLabels($user_id);
	    while ($arr = db_fetch_array($res_notif)) {
			//echo "<br>".$arr['role_label']." ".$arr['event_label']." ".$arr['notify'];
			$arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
	    }
	    return $arr_notif;
	}

	/**
	 * Retrieve the matrix role/event=notify from the db
	 *
	 * @param user_id: the user id
	 *
	 * @return array
	 */
	function getNotificationWithLabels($user_id) {
		
		$group = $this->getGroup();
		$group_artifact_id = $this->getID();
		
	    $sql = "SELECT role_label,event_label,notify FROM artifact_notification_role r, artifact_notification_event e,artifact_notification n ".
		"WHERE n.group_artifact_id=$group_artifact_id AND n.user_id=$user_id AND ".
		"n.role_id=r.role_id AND r.group_artifact_id=$group_artifact_id AND ".
		"n.event_id=e.event_id AND e.group_artifact_id=$group_artifact_id";

/*
	$sql = "SELECT role_label,event_label,notify FROM artifact_notification_role_default r, artifact_notification_event_default e,artifact_notification n ".
		"WHERE n.user_id=$user_id AND ".
		"n.role_id=r.role_id AND ".
		"n.event_id=e.event_id";
*/
	    //echo $sql."<br>";
	    return db_query($sql);
	    
	    
	}

	/**
	 * Retrieve the next free field id (computed by max(id)+1)
	 *
	 * @return int
	 */
	function getNextFieldID() {
		$sql = "SELECT max(field_id)+1 FROM artifact_field WHERE group_artifact_id=".$this->getID();
			   
		$result = db_query($sql);
	    if ($result && db_numrows($result) > 0) {
	    	return db_result($result, 0, 0);
	    } else {
	    	return -1;
	    }
	}

	/**
	 * Return a field name built using an id
	 *
	 * @param id: the id used to build the field name
	 *
	 * @return array
	 */
	function buildFieldName($id) {
		return "field_".$id;
	}
	
	/**
	 * Return the different elements for building the export query
	 *
	 * @param fields: the field list
	 * @param select: the select value
	 * @param from: the from value
	 * @param where: the where value
	 * @param count: the number of 
	 *
	 * @return void
	 */
	function getExportQueryElements($fields,&$select,&$from,&$where,&$count_user_fields) {
		
		//
		// NOTICE
		//
		// Use left join because of the performance
		// So the restriction to this: all fields used in the query must have a value.
		// That involves artifact creation or artifact admin (add a field) must create
		// empty records with default values for fields which haven't a value (from the user).
		//
		/* The query must be something like this :
			SELECT a.artifact_id,u.user_name,v1.valueInt,v2.valueText,u3.user_name
			FROM artifact a 
                             LEFT JOIN artifact_field_value v1 ON (v1.artifact_id=a.artifact_id)
                             LEFT JOIN artifact_field_value v2 ON (v2.artifact_id=a.artifact_id)
                             LEFT JOIN artifact_field_value v3 ON (v2.artifact_id=a.artifact_id)
                             LEFT JOIN user u3 ON (v3.valueInt = u3.user_id)
                             LEFT JOIN user u
			WHERE a.group_artifact_id = 100 and 
			v1.field_id=101 and
			v2.field_id=103 and
			v3.field_id=104 and
			a.submitted_by = u.user_id
			group by a.artifact_id
			order by v3.valueText,v1.valueInt
		*/


		$count = 1;
		$count_user_fields = 0;
		reset($fields);
		
		$select = "SELECT ";
		$from = "FROM artifact a";
		$where = "WHERE a.group_artifact_id = ".$this->getID(); 
				
		$select_count = 0;
		
		if ( count($fields) == 0 )
			return;

		while (list($key,$field) = each($fields) ) {
			
		  //echo $field->getName()."-".$field->getID()."<br>";
			
			// If the field is a standard field ie the value is stored directly into the artifact table (severity, artifact_id, ...)
			if ( $field->isStandardField() ) {
				if ( $select_count != 0 ) {
					$select .= ",";
					$select_count ++;
				} else {
					$select_count = 1;
				}

				// Special case for fields which are user name
				if ( ($field->isUsername())&&(!$field->isSelectBox())&&(!$field->isMultiSelectBox()) ) {
					$select .= " u.user_name as ".$field->getName();
					$from .= " LEFT JOIN user u ON (u.user_id=a.".$field->getName().")";
					$count_user_fields++;
				} else {
					$select .= " a.".$field->getName();
				}
				
				
			} else {
				
				// Special case for comment_type_id field - No data stored in artifact_field_value
				if ( $field->getName() != "comment_type_id" ) {
					// The field value is stored into the artifact_field_value table
					// So we need to add a new join
					if ( $select_count != 0 ) {
						$select .= ",";
						$select_count ++;
					} else {
						$select_count = 1;
					}
	
					// Special case for fields which are user name
					$from .= " LEFT JOIN artifact_field_value v".$count." ON (v".$count.".artifact_id=a.artifact_id".
					  " and v".$count.".field_id=".$field->getID().")";
					//$where .= " and v".$count.".field_id=".$field->getID();
					if ( ($field->isUsername())&&(!$field->isSelectBox())&&(!$field->isMultiSelectBox()) ) {
						$select .= " u".$count.".user_name as ".$field->getName();
						$from .= " LEFT JOIN user u".$count." ON (v".$count.".".$field->getValueFieldName()." = u".$count.".user_id)";
						$count_user_fields++;
					} else {
						$select .= " v".$count.".".$field->getValueFieldName()." as ".$field->getName();
					}


					$count ++;
				}
			}

		}
		
	}
	
	
	/**
	 * Return the query string, for export
	 *
	 * @param fields (OUT): the field list 
	 * @param col_list (OUT): the field name list
	 * @param lbl_list (OUT): the field label list
	 * @param dsc_list (OUT): the field description list
	 * @param select (OUT):
	 * @param from (OUT):
	 * @param where (OUT):
	 * @param multiple_queries (OUT):
	 * @param all_queries (OUT):
	 *
	 * @return string: the sql query
	 */
	function buildExportQuery(&$fields,&$col_list,&$lbl_list,&$dsc_list,&$select,&$from,&$where,&$multiple_queries,&$all_queries,$constraint=false) {
	  global $art_field_fact,$art_fieldset_fact;
	  $sql = null;
	  $all_queries = array();
	  // this array will be filled with the fields to export, ordered by fieldset and rank,
      // and send as an output argument of the function
      $fields = array();
	  $fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
      // fetch the fieldsets
      foreach ($fieldsets as $fieldset) {
          $fields_in_fieldset = $fieldset->getAllUsedFields();
          // for each fieldset, fetch the used fields inside
          while (list(,$field) = each($fields_in_fieldset) ) {
            if ( $field->getName() != "comment_type_id" ) {
                $fields[$field->getName()] = $field;
                $col_list[$field->getName()] = $field->getName();
                $lbl_list[$field->getName()] = $field->getLabel();
                $dsc_list[$field->getName()] = $field->getDescription();
            }
          }
      }
	  
	  //it gets a bit more complicated if we have more fields than SQL wants to treat in one single query
	  if (count($fields) > $GLOBALS['sys_server_join']) {
	    $multiple_queries = true;
	    $chunked_fields = array_chunk($fields,$GLOBALS['sys_server_join']-3,true);
	    $this->cutExportQuery($chunked_fields,$select,$from,$where,$all_queries,$constraint);
	      
	      
	  } else {
	    $multiple_queries = false;
	    $this->getExportQueryElements($fields,$select,$from,$where,$count_user_fields);
	    
	    if ($count_user_fields > $GLOBALS['sys_server_join'] - count($fields)) {
	      $multiple_queries = true;
	      $chunked_fields = array_chunk($fields,count($fields)/2,true);
	      $this->cutExportQuery($chunked_fields,$select,$from,$where,$count_user_fields,$all_queries,$constraint);
	    } else {
	      $sql = $select." ".$from." ".$where." ".($constraint ? $constraint : "")." group by a.artifact_id";
	    }
	  }
	  return $sql;
	}
	
	function cutExportQuery($chunks,&$select,&$from,&$where,&$all_queries,$constraint=false) {
	  foreach ($chunks as $chunk) {
	    $this->getExportQueryElements($chunk,$select,$from,$where,$count_user_fields);
	    if ($count_user_fields > $GLOBALS['sys_server_join'] - count($chunk)) {
	      //for each user field we join another user table
	      $chunked_fields = array_chunk($chunk,count($chunk)/2,true);
	      $this->cutExportQuery($chunked_fields,$select,$from,$where,$count_user_fields,$all_queries,$constraint);
	    } else {
	      $sql = $select." ".$from." ".$where." ".($constraint ? $constraint : "")." group by a.artifact_id";
	      $all_queries[] = $sql;
	    }
	  }
	}


	/**
	 * Return the artifact data with all fields set to default values. (for export)
	 *
	 * @return array: the sql query
	 */
	function buildDefaultRecord() {
		global $art_field_fact;
		
		$fields = $art_field_fact->getAllUsedFields();

		reset($fields);
		while (list(,$field) = each($fields) ) {
		  $record[$field->getName()] = $field->getDefaultValue();
		}
		
		return $record;
	}


	/** retrieves all the cc addresses with their artifact_cc_ids
	 * for a list of artifact_ids
	 * @param change_ids: the list of artifact_ids for which we search the emails
	 */ 
	function getCC($change_ids) {
		$sql = "select email,artifact_cc_id from artifact_cc where artifact_id in (".implode(",",$change_ids).") order by email";
		$result = db_query($sql);
		return $result;
	}

	/**
     	* Delete an email address in the CC list
     	*
     	* @param artifact_cc_id: cc list id
     	* @param changes (OUT): list of changes
     	*
     	* @return boolean
     	*/
    	function deleteCC($delete_cc) {
        	
		$ok=true;
		while (list(,$artifact_ccs) = each($delete_cc)) {
			$artifact_cc_ids = explode(",",$artifact_ccs);
			$i = 0;
			while (list(,$artifact_cc_id) = each($artifact_cc_ids)) {
	        		$sql = "SELECT artifact_id from artifact_cc WHERE artifact_cc_id=$artifact_cc_id";
        			$res = db_query($sql);
        			if (db_numrows($res) > 0) {
					$i++;
					$aid = db_result($res, 0, 'artifact_id');
					$ah = new ArtifactHtml($this,$aid);
					$ok &= $ah->deleteCC($artifact_cc_id,$changes,true);
				}
        		}
		}
		return $ok;
    	}


	/**
	 * retrieves all the attached files with their size and id
	 * for a list of artifact_ids
	 * @param change_ids: the list of artifact_ids for which we search the attached files
	*/
	function getAttachedFiles($change_ids) {
		$sql = "select filename,filesize,id from artifact_file where artifact_id in (".implode(",",$change_ids).") order by filename,filesize";
		return db_query($sql);
	}


	/**
	* Delete the files with specified id from $ids
	* @return bool
	*/
	function deleteAttachedFiles($delete_attached) {
		$ok=true;
		$i = 0;
		while (list(,$id_list) = each($delete_attached)) {
			$ids = explode(",",$id_list);
			while (list(,$id) = each($ids)) {
				$sql = "SELECT artifact_id FROM artifact_file WHERE id = $id";
				$res = db_query($sql);
        			if (db_numrows($res) > 0) {
					$aid = db_result($res, 0, 'artifact_id');
					$ah = new ArtifactHtml($this,$aid);
					$afh=new ArtifactFileHtml($ah,$id);
                        		if (!$afh || !is_object($afh)) {
                               	 		$GLOBALS['Response']->addFeedback('error', 'Could Not Create File Object::'.$afh->getName());
                        		} elseif ($afh->isError()) {
                                		$GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage().'::'.$afh->getName());
                        		} else {
						$i++;
                                		$okthis = $afh->delete();
						if (!$okthis) $GLOBALS['Response']->addFeedback('error', '<br>File Delete: '.$afh->getErrorMessage());
						$ok &= $okthis;
                        		}	
				}
			}
		}
		return $ok;
	}

	/**
	 * retrieves all artifacts
	 * for a list of artifact_ids
	 * @param change_ids: the list of artifact_ids for which we search the attached files
	*/
	function getDependencies($change_ids) {
		$sql = "select d.artifact_depend_id,d.is_dependent_on_artifact_id,a.summary,ag.name,g.group_name ".
			"from artifact_dependencies as d, artifact_group_list ag, groups g, artifact a ".
			"where d.artifact_id in (".implode(",",$change_ids).") AND ".
			"d.is_dependent_on_artifact_id = a.artifact_id AND ".
            		"a.group_artifact_id = ag.group_artifact_id AND ".
            		"ag.group_id = g.group_id ".
			"order by is_dependent_on_artifact_id";
		return db_query($sql);
	}


	/** delete all the dependencies specified in delete_dependend */
	function deleteDependencies($delete_depend) {
	    global $Language;
		$changed = true;
		while (list(,$depend) = each($delete_depend)) {
			$sql = "DELETE FROM artifact_dependencies WHERE artifact_depend_id IN ($depend)";
        		$res = db_query($sql);
        		if (!$res) {
            			$GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_type','del_err',array($dependent,db_error($res))));
				$changed = false;
        		}
		}
		if ($changed) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','depend_removed'));
		return $changed;
        }	
	

	/**
	 * @param group_id: the group id of the new tracker
	 * @param group_id_template: the template group id (used for the copy)
	 * @param atid_template: the template artfact type id 
	 */
	function copyArtifacts($from_atid) {
	  $result = db_query("SELECT artifact_id FROM artifact WHERE group_artifact_id='$from_atid'");
	  while ($row = db_fetch_array($result)) {
	    if (!$this->copyArtifact($from_atid,$row['artifact_id']) ) {return false;}
	  }
	  return true;
	}
	
	
	function copyArtifact($from_atid,$from_aid) {
	  $aid = 0;
	  $res = true;

	   // copy common artifact fields
	   $result = db_query("INSERT INTO artifact (group_artifact_id,status_id,submitted_by,open_date,close_date,summary,details,severity) ".
	   "SELECT ".$this->getID().",status_id,submitted_by,".time().",close_date,summary,details,severity ".
	   "FROM artifact ".
	   "WHERE artifact_id='$from_aid' ".
	   "AND group_artifact_id='$from_atid'");
	   if ($result && db_affected_rows($result) > 0) {
	     $aid=db_insertid($result);
	   } else {
	     $this->setError(db_error());
	     return false;
	   }
	   
	   
	   // copy specific artifact fields
	   $result = db_query("INSERT INTO artifact_field_value (field_id,artifact_id,valueInt,valueText,valueFloat,valueDate) ".
	   "SELECT field_id,$aid,valueInt,valueText,valueFloat,valueDate ".
	   "FROM artifact_field_value ".
	   "WHERE artifact_id = '$from_aid'");
	   if (!$result || db_affected_rows($result) <= 0) {
	     $this->setError(db_error());
	     $res = false;
	   }

	   //copy cc addresses
	   $result = db_query("INSERT INTO artifact_cc (artifact_id,email,added_by,comment,date) ".
           "SELECT $aid,email,added_by,comment,date ".
           "FROM artifact_cc ".
           "WHERE artifact_id='$from_aid'");
	   if (!$result || db_affected_rows($result) <= 0) {
	     $this->setError(db_error());
	     $res = false;
	   }

	   //copy artifact files
	   db_query("INSERT INTO artifact_file (artifact_id,description,bin_data,filename,filesize,filetype,adddate,submitted_by) ".
	   "SELECT $aid,description,bin_data,filename,filesize,filetype,adddate,submitted_by ".
	   "FROM artifact_file ".
	   "WHERE artifact_id='$from_aid'");
	   if (!$result || db_affected_rows($result) <= 0) {
	     $this->setError(db_error());
	     $res = false;
	   }

	   return $res;
	}
	
	function getDateFieldReminderSettings($group_id,$group_artifact_id,$field_id) {
	    
	    $sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			    .' WHERE group_artifact_id=%d'
			    .' AND field_id=%d',
			    $group_artifact_id,$field_id);	    
	    $result = db_query($sql);
	    return $result;
	    
	}	
}

?>
