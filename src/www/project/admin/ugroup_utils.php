<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

require_once('common/user/UserHelper.class.php');

//
// Define various functions for user group management
//
$GLOBALS['Language']->loadLanguageMsg('project/project');

// Predefined ugroups. Should be consistent with DB (table 'ugroup')
$GLOBALS['UGROUP_NONE']               =100;
$GLOBALS['UGROUP_ANONYMOUS']          =1;
$GLOBALS['UGROUP_REGISTERED']         =2;
$GLOBALS['UGROUP_PROJECT_MEMBERS']    =3;
$GLOBALS['UGROUP_PROJECT_ADMIN']      =4;
$GLOBALS['UGROUP_FILE_MANAGER_ADMIN'] =11;
$GLOBALS['UGROUP_DOCUMENT_TECH']      =12;
$GLOBALS['UGROUP_DOCUMENT_ADMIN']     =13;
$GLOBALS['UGROUP_WIKI_ADMIN']         =14;
$GLOBALS['UGROUP_TRACKER_ADMIN']      =15;

/*
*      anonymous
*          ^
*          |
*      registered
*          ^
*          |
*     +----+-----+
*     |          |
*  statics    members
*                ^
*                |
*         +------+-----+- - - -   -   -
*         |            |
*    tracker_tech   doc_admin
*/
function ugroup_get_parent($ugroup_id) {
    if ($ugroup_id == $GLOBALS['UGROUP_NONE'] || $ugroup_id == $GLOBALS['UGROUP_ANONYMOUS']) {
        $parent_id = false;
    } else if ($ugroup_id == $GLOBALS['UGROUP_REGISTERED']) {
        $parent_id = $GLOBALS['UGROUP_ANONYMOUS'];
    } else if ($ugroup_id == $GLOBALS['UGROUP_PROJECT_MEMBERS'] || $ugroup_id > 100) {
        $parent_id = $GLOBALS['UGROUP_REGISTERED'];
    } else {
        $parent_id = $GLOBALS['UGROUP_PROJECT_MEMBERS'];
    }
    return $parent_id;
}

// Return members (user_id + user_name according to user preferences) of given user group
function ugroup_db_get_members($ugroup_id, $with_display_preferences=false) {
    $sqlname="user.user_name";
    if ($with_display_preferences) {
        $uh = new UserHelper();
        $sqlname=$uh->getDisplayNameSQLQuery();
    }
	  $sql="(SELECT user.user_id, ".$sqlname." ". 
    "FROM ugroup_user, user ".
    "WHERE user.user_id = ugroup_user.user_id ".
    "AND ugroup_user.ugroup_id=".$ugroup_id.")";
  return $sql;
}


// Return name and id (as DB result) of all ugroups belonging to a specific project.
function ugroup_db_get_existing_ugroups($group_id, $predefined=null) {
    $_extra = '';
    if($predefined !== null && is_array($predefined)) {
        $_extra = ' OR ugroup_id IN ('.implode(',', $predefined).')';
    }
    $sql="SELECT ugroup_id, name FROM ugroup WHERE group_id=$group_id ".$_extra." ORDER BY name";
    return db_query($sql);
}

// Return DB ugroup from ugroup_id 
function ugroup_db_get_ugroup($ugroup_id) {
    $sql="SELECT * FROM ugroup WHERE ugroup_id=$ugroup_id";
    return db_query($sql);
}


function ugroup_db_list_all_ugroups_for_user($group_id,$user_id) {
    $sql="SELECT ugroup.ugroup_id AS ugroup_id,ugroup.name AS name FROM ugroup, ugroup_user WHERE ugroup_user.user_id=$user_id AND ugroup.group_id=$group_id AND ugroup_user.ugroup_id=ugroup.ugroup_id";
    return db_query($sql);
}


/** Return array of ugroup_id for all user-defined ugoups that user is part of 
 * and having tracker-related permissions on the $group_artifact_id tracker */
function ugroup_db_list_tracker_ugroups_for_user($group_id,$group_artifact_id,$user_id) {
    $sql="SELECT distinct ug.ugroup_id FROM ugroup ug, ugroup_user ugu, permissions p ".
      "WHERE ugu.user_id=$user_id ".
      "AND ug.group_id=$group_id ".
      "AND ugu.ugroup_id=ug.ugroup_id ".
      "AND p.ugroup_id = ugu.ugroup_id ".
      "AND p.object_id LIKE '$group_artifact_id%' ".
      "AND p.permission_type LIKE 'TRACKER%'";

    return util_result_column_to_array(db_query($sql));
}

/** Return array of ugroup_id for all dynamic ugoups like 
 * (anonymous_user, registered_user, project_member,
 * project_admins, tracker_admins) that user is part of */
function ugroup_db_list_dynamic_ugroups_for_user($group_id,$instances,$user) {
    
    if (!is_a($user, 'User')) {
        $user = new User($user);
    }
  
  if ($user->isAnonymous()) return array($GLOBALS['UGROUP_ANONYMOUS']);

  $res = array($GLOBALS['UGROUP_ANONYMOUS'],$GLOBALS['UGROUP_REGISTERED']);

  if ($user->isMember($group_id))  $res[] = $GLOBALS['UGROUP_PROJECT_MEMBERS']; 
  if ($user->isMember($group_id,'A'))  $res[] = $GLOBALS['UGROUP_PROJECT_ADMIN'];
  if ($user->isMember($group_id,'D2'))  $res[] = $GLOBALS['UGROUP_DOCUMENT_ADMIN'];
  if ($user->isMember($group_id,'R2'))  $res[] = $GLOBALS['UGROUP_FILE_MANAGER_ADMIN'];
  if ($user->isMember($group_id,'W2'))  $res[] = $GLOBALS['UGROUP_WIKI_ADMIN'];
  if (is_int($instances)) {
      if ($user->isTrackerAdmin($group_id,$instances))  $res[] = $GLOBALS['UGROUP_TRACKER_ADMIN'];
  } else if (is_array($instances)) {
      if (isset($instances['artifact_type'])) {
          if ($user->isTrackerAdmin($group_id,$instances['artifact_type']))  $res[] = $GLOBALS['UGROUP_TRACKER_ADMIN'];
      }
  }

  return $res;
}

/** Return user group name from ID */
function ugroup_get_name_from_id($ugroup_id) {
    $res=ugroup_db_get_ugroup($ugroup_id);
    return db_result($res,0,'name');
}

/**
 * Check membership of the user to a specified ugroup
 * $group_id is necessary for automatic project groups like project member, release admin, etc.
 * $atid is necessary for trackers since the tracker admin role is different for each tracker.
 * @return true if user is member of the ugroup, false otherwise.
 */
function ugroup_user_is_member($user_id, $ugroup_id, $group_id, $atid=0) {
    $um =& UserManager::instance();
    $user =& $um->getUserById($user_id);
    // Special Cases
    if ($ugroup_id==$GLOBALS['UGROUP_NONE']) { 
        // Empty group
        return false;
    } else if ($ugroup_id==$GLOBALS['UGROUP_ANONYMOUS']) { 
        // Anonymous user
        return true;
    } else if ($ugroup_id==$GLOBALS['UGROUP_REGISTERED']) {
        // Registered user
        return $user_id != 0;
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        if ($user->isMember($group_id)) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_FILE_MANAGER_ADMIN']) {
        // File manager admins
        if ($user->isMember($group_id,'R2')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_ADMIN']) {
        // Document admin
        if ($user->isMember($group_id,'D2')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_TECH']) {
        // Document tech
        if ($user->isMember($group_id,'D1')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_WIKI_ADMIN']) {
        // Wiki admins
        if ($user->isMember($group_id,'W2')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_ADMIN']) {
        // Project admins
        if ($user->isMember($group_id,'A')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_TRACKER_ADMIN']) {
        // Tracker admins
        $group = group_get_object($group_id);	
        $at = new ArtifactType($group, $atid);
        return $at->userIsAdmin($user_id);
    } else { 
        // Normal ugroup
        $sql="SELECT * from ugroup_user where ugroup_id='$ugroup_id' and user_id='$user_id'";
        $res=db_query($sql);
        if (db_numrows($res) > 0) {
            return true;
        }
    }
    return false;
}


/**
 * Check membership of the user to a specified ugroup
 * $group_id is necessary for automatic project groups like project member, release admin, etc.
 * $atid is necessary for trackers since the tracker admin role is different for each tracker.
 * @return true if user is member of the ugroup, false otherwise.
 */
function ugroup_db_get_dynamic_members($ugroup_id, $atid, $group_id, $with_display_preferences=false) {
    $sqlname="user.user_name";
    if ($with_display_preferences) {
        $uh = new UserHelper();
        $sqlname=$uh->getDisplayNameSQLQuery();
    }
	// Special Cases
    if ($ugroup_id==$GLOBALS['UGROUP_NONE']) { 
        // Empty group
        return;
    } else if ($ugroup_id==$GLOBALS['UGROUP_ANONYMOUS']) { 
        // Anonymous user
        return;
    } else if ($ugroup_id==$GLOBALS['UGROUP_REGISTERED']) {
        // Registered user
        return "(SELECT user.user_id, ".$sqlname." FROM user WHERE ( status='A' OR status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_FILE_MANAGER_ADMIN']) {
        // File manager admins
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND file_flags = 2 AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_ADMIN']) {
        // Document admin
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND doc_flags IN (2,3) AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_TECH']) {
        // Document tech
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND doc_flags IN (1,2) AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_WIKI_ADMIN']) {
        // Wiki admins
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND wiki_flags = '2' AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_ADMIN']) {
        // Project admins
        return "(SELECT user.user_id, ".$sqlname." FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND admin_flags = 'A' AND ( user.status='A' OR user.status='R' ))";
    } else if ($ugroup_id==$GLOBALS['UGROUP_TRACKER_ADMIN']) {
        // Tracker admins
        return "(SELECT user.user_id, ".$sqlname." FROM artifact_perm ap, user WHERE (user.user_id = ap.user_id) and group_artifact_id=$atid AND perm_level in (2,3) AND ( user.status='A' OR user.status='R' ))";
    } 
}


/**
 * Remove user from all ugroups
 *
 * @return false if access rights are insufficient (need to be site admin)
 */
function ugroup_delete_user_from_all_ugroups($user_id) {
    if (!user_is_super_user()) return false;
    db_query("DELETE FROM ugroup_user WHERE user_id=$user_id");
    return true;
}


/**
 * Remove user from all ugroups attached to the given project
 *
 * @return true
 */
function ugroup_delete_user_from_project_ugroups($group_id,$user_id) {

    // First, retrieve all possible ugroups for this project
    $sql="SELECT ugroup_id FROM ugroup WHERE group_id='$group_id'";
    $res=db_query($sql);
    $ugroups_list='';
    if (db_numrows($res)<1) {
        return true;
    } else { 
        while ($row = db_fetch_array($res)) {
            if ($ugroups_list) { $ugroups_list.= ' ,';}
            $ugroups_list .= $row['ugroup_id'] ;
        }
    }
    // Then delete membership
    db_query("DELETE FROM ugroup_user WHERE user_id=$user_id AND ugroup_id IN (".$ugroups_list.")");
    
    // Raise an event
    $em =& EventManager::instance();
    $em->processEvent('project_admin_remove_user_from_project_ugroups', array(
        'group_id' => $group_id,
        'user_id' => $user_id,
        'ugroups' => explode(' ,', $ugroups_list)
    ));
    
    return true;
}



/**
 * Create a new ugroup
 *
 * @return ugroup_id
 */
function ugroup_create($group_id, $ugroup_name, $ugroup_description, $group_templates) {
    global $Language;

    // Sanity check
    if (!$ugroup_name) { 
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug_name_missed'));
    }
    if (!eregi("^[a-zA-Z0-9_\-]+$",$ugroup_name)) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','invalid_ug_name',$ugroup_name));
    }
    // Check that there is no ugroup with the same name in this project
    $sql = "SELECT * FROM ugroup WHERE name='$ugroup_name' AND group_id='$group_id'";
    $result=db_query($sql);
    if (db_numrows($result)>0) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug__exist',$ugroup_name)); 
    }
    
    
    // Create
    $sql = "INSERT INTO ugroup (name,description,group_id) VALUES ('$ugroup_name', '$ugroup_description',$group_id)";
    $result=db_query($sql);

    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','cant_create_ug',db_error()));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','ug_create_success'));
    }
    // Now get the corresponding ugroup_id
    $sql="SELECT ugroup_id FROM ugroup WHERE group_id=$group_id AND name='$ugroup_name'";
    $result = db_query($sql);
    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug_created_but_no_id',db_error()));
    }
    $ugroup_id = db_result($result,0,0);
    if (!$ugroup_id) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug_created_but_no_id',db_error()));
    }

    //
    // Now populate new group if a 'template' was selected
    //
    $query=0;

    if ($group_templates == "cx_empty") {
        // Do nothing, the group should be empty
        $query='';
    } else if ($group_templates == "cx_empty2") {
        // The user selected '----'
        $query='';
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','no_g_template'));
    } else if ($group_templates == "cx_members") {
        // Get members from predefined groups
        $query="SELECT user_id FROM user_group WHERE group_id=$group_id";
    } else if ($group_templates == "cx_admins") {
        $query="SELECT user_id FROM user_group WHERE group_id=$group_id AND admin_flags='A'";
    } else {
        // $group_templates should contain the ID of an exiting group
        // Copy members from an existing group
        $query="SELECT user_id FROM ugroup_user WHERE ugroup_id=$group_templates";
    }

    // Copy user IDs in new group
    if ($query) {
        $res = db_query($query);
        $countuser=0;
        while ($row = db_fetch_array($res)) {
            $sql="INSERT INTO ugroup_user (ugroup_id,user_id) VALUES ($ugroup_id,".$row['user_id'].")";
            if (!db_query($sql)) {
                exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','cant_insert_u_in_g',array($row['user_id'],$ugroup_id,db_error())));
            }
            $countuser++;
        }
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','u_added',$countuser));
    }
    
    // raise an event for ugroup creation
    $em =& EventManager::instance();
    $em->processEvent('project_admin_ugroup_creation', array(
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id
    ));
    
    return $ugroup_id;
}



/**
 * Update ugroup with list of members
 */
function ugroup_update($group_id, $ugroup_id, $ugroup_name, $ugroup_description, $pickList) {
    global $Language;

    // Sanity check
    if (!$ugroup_name) { 
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug_name_missed'));
    }
    if (!eregi("^[a-zA-Z0-9_\-]+$",$ugroup_name)) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','invalid_ug_name',$ugroup_name));
    }
    if (!$ugroup_id) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','ug_id_missed'));
    }

    // Check that there is no ugroup with the same name and a different id in this project
    $sql = "SELECT * FROM ugroup WHERE name='$ugroup_name' AND group_id='$group_id' AND ugroup_id!='$ugroup_id'";
    $result=db_query($sql);
    if (db_numrows($result)>0) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','ug__exist',$ugroup_name)); 
    }

    // Update
    $sql = "UPDATE ugroup SET name='$ugroup_name', description='$ugroup_description' WHERE ugroup_id=$ugroup_id;";
    $result=db_query($sql);

    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','cant_update_ug',db_error()));
    }

    // Reset members of the group
    $sql="DELETE FROM ugroup_user WHERE ugroup_id=$ugroup_id";
    if (!db_query($sql)) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','cant_reset_ug',array($ugroup_id,db_error())));
    }

    // Then add all selected users
    $user_count=count($pickList);
    
    for ($i=0; $i<$user_count; $i++) {
        $sql="INSERT INTO ugroup_user (ugroup_id,user_id) VALUES ($ugroup_id,".$pickList[$i].")";
        if (!db_query($sql)) {
            exit_error($Language->getText('global','error'),$Language->getText('project_admin_ugroup_utils','cant_insert_u_in_g',array($pickList[$i],db_error())));
        }
    }
    
    // raise an event for ugroup edition
    $em =& EventManager::instance();
    $em->processEvent('project_admin_ugroup_edition', array(
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
        'ugroup_name' => $ugroup_name,
        'ugroup_desc' => $ugroup_description,
        'pick_list' => $pickList
    ));
    
    // Now log in project history
    group_add_history('upd_ug','',$group_id,array($ugroup_name));

    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','ug_upd_success',array($ugroup_name,$user_count)));
}

function ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id) {
    $sql = "DELETE FROM ugroup_user 
    WHERE ugroup_id = ". db_ei($ugroup_id) ."
      AND user_id = ". db_ei($user_id);
    $res = db_query($sql);
    if (!$res) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_ugroup_utils','cant_update_ug',db_error()));
    }
    if ($rows = db_affected_rows($res)) {
        // Now log in project history
        $res = ugroup_db_get_ugroup($ugroup_id);
        group_add_history('upd_ug','',$group_id,array(db_result($res,0,'name')));
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils','ug_upd_success',array(db_result($res,0,'name'), 1)));
    }
}
function ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id) {
    $sql = "INSERT INTO ugroup_user (ugroup_id, user_id) VALUES(". db_ei($ugroup_id) .", ". db_ei($user_id) .")";
    $res = db_query($sql);
    if (!$res) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_ugroup_utils','cant_update_ug',db_error()));
    }
    if ($rows = db_affected_rows($res)) {
        // Now log in project history
        $res = ugroup_db_get_ugroup($ugroup_id);
        group_add_history('upd_ug','',$group_id,array(db_result($res,0,'name')));
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils','ug_upd_success',array(db_result($res,0,'name'), 1)));
    }
}

/**
 * Delete ugroup 
 *
 * @return false if error
 */
function ugroup_delete($group_id, $ugroup_id) { 
    global $Language;
    if (!$ugroup_id) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils','ug_not_given'));
        return false;
    }
    $ugroup_name=ugroup_get_name_from_id($ugroup_id);
    $sql = "DELETE FROM ugroup WHERE group_id=$group_id AND ugroup_id=$ugroup_id";
        
    $result=db_query($sql);
    if (!$result || db_affected_rows($result) < 1) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' )));
         return false;           
    }
    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','g_del'));
    // Now remove users
    $sql = "DELETE FROM ugroup_user WHERE ugroup_id=$ugroup_id";
    
    $result=db_query($sql);
    if (!$result) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils','cant_remove_u',db_error()));
        return false;
    }
    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','all_u_removed'));
    
    // raise an event for ugroup deletion
    $em =& EventManager::instance();
    $em->processEvent('project_admin_ugroup_deletion', array(
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id
    ));
    
    // Last, remove permissions for this group
    $perm_cleared=permission_clear_ugroup($group_id, $ugroup_id); 
    if (!($perm_cleared)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils','cant_remove_perm',db_error()));
        return false;
    } else if ($perm_cleared>1) {
        $perm_cleared--;
        $GLOBALS['Response']->addFeedback('warning', $Language->getText('project_admin_ugroup_utils','perm_warning',$perm_cleared));
    } 
    // Now log in project history
    group_add_history('del_ug','',$group_id,array($ugroup_name));

    return true;
}

/** copy all ugroups from group $from_group to 
 *  group to_group. Do not copy system-wide ugroups
 *  return mapping from_ugroup_id to_ugroup_id
 */
function ugroup_copy_ugroups($from_group,$to_group,&$mapping) {
  $mapping = array();
  $result = db_query("SELECT ugroup_id from ugroup where group_id = '$from_group' AND ugroup_id > 100");
  while ($row = db_fetch_array($result)) {
    $err = ugroup_copy_ugroup($row['ugroup_id'],$to_group,$ugid);
    if (isset($err) && $err !== false) {return $err;}
    $mapping[$row['ugroup_id']] = $ugid;
  }
  return true;
}

/** copy ugoup ugroup_id with corresponding users to belong 
 *  to $to_group 
*/
function ugroup_copy_ugroup($ugroup_id,$to_group,&$ugid) {
  $ugid = 0;
  $err = false;

  $result = db_query("INSERT INTO ugroup (name,description,group_id) ".
		     "SELECT name,description,$to_group ".
		     "FROM ugroup ".
		     "WHERE ugroup_id='$ugroup_id'");
  if ($result && db_affected_rows($result) > 0) {
    $ugid=db_insertid($result);
  } else {
    return db_error();
  }

  $result = db_query("INSERT INTO ugroup_user (ugroup_id,user_id) ".
		     "SELECT $ugid,user_id ".
		     "FROM ugroup_user ".
		     "WHERE ugroup_id='$ugroup_id'");
  if (!$result) {
    return db_error();
  }

  $sql = sprintf('INSERT INTO ugroup_mapping (to_group_id, src_ugroup_id, dst_ugroup_id)'.
                 ' VALUES (%d, %d, %d)',
                 $to_group,
                 $ugroup_id,
                 $ugid);
  $result = db_query($sql);
  if (!$result || db_affected_rows($result) <= 0) {
    return db_error();
  }

  return $err;

}

?>
