<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Guerin 2004, Codendi Team, Xerox
//

require_once('common/user/UserHelper.class.php');
require_once('common/project/UGroup.class.php');

//
// Define various functions for user group management
//

// Predefined ugroups. Should be consistent with DB (table 'ugroup')
/** @deprecated **/
$GLOBALS['UGROUP_NONE']               = UGroup::NONE;
$GLOBALS['UGROUP_ANONYMOUS']          = UGroup::ANONYMOUS;
$GLOBALS['UGROUP_REGISTERED']         = UGroup::REGISTERED;
$GLOBALS['UGROUP_PROJECT_MEMBERS']    = UGroup::PROJECT_MEMBERS;
$GLOBALS['UGROUP_PROJECT_ADMIN']      = UGroup::PROJECT_ADMIN;
$GLOBALS['UGROUP_FILE_MANAGER_ADMIN'] = UGroup::FILE_MANAGER_ADMIN;
$GLOBALS['UGROUP_DOCUMENT_TECH']      = UGroup::DOCUMENT_TECH;
$GLOBALS['UGROUP_DOCUMENT_ADMIN']     = UGroup::DOCUMENT_ADMIN;
$GLOBALS['UGROUP_WIKI_ADMIN']         = UGroup::WIKI_ADMIN;
$GLOBALS['UGROUP_TRACKER_ADMIN']      = UGroup::TRACKER_ADMIN;

$GLOBALS['UGROUPS'] = array(
    'UGROUP_NONE'               => $GLOBALS['UGROUP_NONE'],
    'UGROUP_ANONYMOUS'          => $GLOBALS['UGROUP_ANONYMOUS'],
    'UGROUP_REGISTERED'         => $GLOBALS['UGROUP_REGISTERED'],
    'UGROUP_PROJECT_MEMBERS'    => $GLOBALS['UGROUP_PROJECT_MEMBERS'],
    'UGROUP_PROJECT_ADMIN'      => $GLOBALS['UGROUP_PROJECT_ADMIN'],
    'UGROUP_FILE_MANAGER_ADMIN' => $GLOBALS['UGROUP_FILE_MANAGER_ADMIN'],
    'UGROUP_DOCUMENT_TECH'      => $GLOBALS['UGROUP_DOCUMENT_TECH'],
    'UGROUP_DOCUMENT_ADMIN'     => $GLOBALS['UGROUP_DOCUMENT_ADMIN'],
    'UGROUP_WIKI_ADMIN'         => $GLOBALS['UGROUP_WIKI_ADMIN'],
    'UGROUP_TRACKER_ADMIN'      => $GLOBALS['UGROUP_TRACKER_ADMIN'],
);
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
// * $keword is used to filter the users.
function ugroup_db_get_members($ugroup_id, $with_display_preferences = false, $keyword = null) {
    $sqlname="user.user_name AS full_name";
    $sqlorder="user.user_name";
    if ($with_display_preferences) {
        $uh = UserHelper::instance();
        $sqlname=$uh->getDisplayNameSQLQuery();
        $sqlorder=$uh->getDisplayNameSQLOrder();
    }
    $having_keyword = '';
    if ($keyword) {
        $keyword = "'%". db_es((string)$keyword) ."%'";
        $having_keyword = " AND full_name LIKE $keyword ";
    }
    $ugroup_id = (int)$ugroup_id;
    $sql="(SELECT user.user_id, $sqlname, user.user_name
            FROM ugroup_user, user 
            WHERE user.user_id = ugroup_user.user_id 
              AND ugroup_user.ugroup_id = $ugroup_id 
            $having_keyword
            ORDER BY $sqlorder)";
    return $sql;
}

/**
 * Return name and id (as DB result) of all ugroups belonging to a specific project.
 *
 * @param Integer $groupId    Id of the project
 * @param Array   $predefined List of predefined ugroup id
 *
 * @deprecated Use UGroupManager::getExistingUgroups() instead
 *
 * @return DB result set
 */
function ugroup_db_get_existing_ugroups($group_id, $predefined=null) {
    $_extra = '';
    if($predefined !== null && is_array($predefined)) {
        $_extra = ' OR ugroup_id IN ('.implode(',', $predefined).')';
    }
    $sql="SELECT ugroup_id, name FROM ugroup WHERE group_id=$group_id ".$_extra." ORDER BY name";
    return db_query($sql);
}

/**
 * Returns a list of ugroups for the given group, with their associated members
 */
function ugroup_get_ugroups_with_members($group_id) {
    $sql="SELECT ugroup.ugroup_id, ugroup.name, user.user_id, user.user_name FROM ugroup ".
    "NATURAL LEFT JOIN ugroup_user ".
    "NATURAL LEFT JOIN user ".
    "WHERE ugroup.group_id=".db_ei($group_id).
    " ORDER BY ugroup.name";
    
    $return = array();
    
    $res = db_query($sql);
    while ($data = db_fetch_array($res)) {
        $return[] = $data;
    }
    
    return $return;
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
        $user = ugroup_get_user_manager()->getUserById($user);
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
    $um = ugroup_get_user_manager();
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
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);	
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
 * $keword is used to filter the users
 */
function ugroup_db_get_dynamic_members($ugroup_id, $atid, $group_id, $with_display_preferences=false, $keyword = null) {
    $sqlname="user.user_name AS full_name";
    $sqlorder="user.user_name";
    if ($with_display_preferences) {
        $uh = UserHelper::instance();
        $sqlname=$uh->getDisplayNameSQLQuery();
        $sqlorder=$uh->getDisplayNameSQLOrder(); 
    }
    $having_keyword = '';
    if ($keyword) {
        $keyword = "'%". db_es((string)$keyword) ."%'";
        $having_keyword = " HAVING full_name LIKE $keyword ";
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
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user WHERE ( status='A' OR status='R' ) $having_keyword ORDER BY ".$sqlorder." )";
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_FILE_MANAGER_ADMIN']) {
        // File manager admins
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND file_flags = 2 AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_ADMIN']) {
        // Document admin
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND doc_flags IN (2,3) AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_TECH']) {
        // Document tech
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND doc_flags IN (1,2) AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_WIKI_ADMIN']) {
        // Wiki admins
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND wiki_flags = '2' AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_ADMIN']) {
        // Project admins
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND admin_flags = 'A' AND ( user.status='A' OR user.status='R' ) $having_keyword ORDER BY ".$sqlorder.")";
    } else if ($ugroup_id==$GLOBALS['UGROUP_TRACKER_ADMIN']) {
        // Tracker admins
        return "(SELECT user.user_id, ".$sqlname.", user.user_name FROM artifact_perm ap, user WHERE (user.user_id = ap.user_id) and group_artifact_id=$atid AND perm_level in (2,3) AND ( user.status='A' OR user.status='R' ) ORDER BY ".$sqlorder.")";
    } 
}

/**
 * Retrieve all dynamic groups' members except ANONYMOUS, NONE, REGISTERED
 * @param Integer $group_id
 * @param Integer $atid
 * @return Array
 */
function ugroup_get_all_dynamic_members($group_id, $atid=0) {
    $members = array();
    $sql     = array();
    $ugroups = array();
    //retrieve dynamic ugroups id and name
    $rs = db_query("SELECT ugroup_id, name FROM ugroup WHERE ugroup_id IN (".implode(',',$GLOBALS['UGROUPS']).") ");
    while( $row = db_fetch_array($rs) ) {
        $ugroups[ $row['ugroup_id'] ] = $row['name'];
    }
    foreach ( $GLOBALS['UGROUPS'] as $ugroup_id) {
        if ( $ugroup_id == $GLOBALS['UGROUP_ANONYMOUS'] || $ugroup_id == $GLOBALS['UGROUP_REGISTERED'] || $ugroup_id == $GLOBALS['UGROUP_NONE'] ) {
            continue;
        }
        $sql = ugroup_db_get_dynamic_members($ugroup_id, $atid, $group_id);
        $rs  = db_query($sql);
        while( $row = db_fetch_array($rs) ) {
            $members[] = array(
                'ugroup_id' => $ugroup_id,
                'name'      => util_translate_name_ugroup($ugroups[ $ugroup_id ]),
                'user_id'   => $row['user_id'],
                'user_name' => $row['user_name'],
            );
        }
    }
    return $members;
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
function ugroup_update($group_id, $ugroup_id, $ugroup_name, $ugroup_description) {
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
    // Retrieve ugroup old name before updating
    $sql = "SELECT name FROM ugroup WHERE group_id='$group_id' AND ugroup_id ='$ugroup_id'";
    $result=db_query($sql);
    if($result && !db_error($result)) {
        $row = db_fetch_array($result);
        $ugroup_old_name = $row['name'];
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

    // Search for all members of this ugroup
    $pickList = array();
    $sql="SELECT user_id FROM ugroup_user WHERE ugroup_id = ". db_ei($ugroup_id);
    if ($res = db_query($sql)) {
        while($row = db_fetch_array($res)) {
            $pickList[] = $row['user_id'];
        }
    }
    
    // raise an event for ugroup edition
    $em =& EventManager::instance();
    $em->processEvent('project_admin_ugroup_edition', array(
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
        'ugroup_name' => $ugroup_name,
        'ugroup_old_name' => $ugroup_old_name,
        'ugroup_desc' => $ugroup_description,
        'pick_list' => $pickList
    ));
    
    // Now log in project history
    group_add_history('upd_ug','',$group_id,array($ugroup_name));

    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils','ug_upd_success',array($ugroup_name,count($pickList))));
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
        // Search for all members of this ugroup
        $sql="SELECT count(user.user_id)".
             "FROM ugroup_user, user ".
             "WHERE user.user_id = ugroup_user.user_id ".
             "AND user.status IN ('A', 'R') ".
             "AND ugroup_user.ugroup_id=".db_ei($ugroup_id);
        $result = db_query($sql);
        $usersCount = db_result($result, 0,0);
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils','ug_upd_success',array(db_result($res,0,'name'), $usersCount)));
        if ($usersCount == 0) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_admin_ugroup_utils','ug_upd_empty'));
        }
        // Raise event for ugroup modification
        EventManager::instance()->processEvent('project_admin_ugroup_remove_user', array(
                'group_id' => $group_id,
                'ugroup_id' => $ugroup_id,
                'user_id' => $user_id));
    }
}
function ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id) {
    if (!ugroup_user_is_member($user_id, $ugroup_id, $group_id)) {
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
            // Raise event for ugroup modification
            EventManager::instance()->processEvent('project_admin_ugroup_add_user', array(
                'group_id' => $group_id,
                'ugroup_id' => $ugroup_id,
                'user_id' => $user_id));
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'cant_insert_u_in_g', array($user_id, $ugroup_id, $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'user_already_exist'))));
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
    $project        = ProjectManager::instance()->getProject($group_id);
    $ugroup_manager = new UGroupManager();
    $ugroup         = $ugroup_manager->getUGroupWithMembers($project, $ugroup_id);

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
    $em = EventManager::instance();
    $em->processEvent('project_admin_ugroup_deletion', array(
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
        'ugroup'    => $ugroup,
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
    group_add_history('del_ug','',$group_id,array($ugroup->getName()));

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

/**
 * Wrapper for tests
 *
 * @return UserManager
 */
function ugroup_get_user_manager() {
    return UserManager::instance();
}

/**
 * Wrapper for tests
 *
 * @return UGroup
 */
function ugroup_get_ugroup() {
    return new UGroup();
}

/**
 * Calculate the number of project admins and non project admins of the ugroup
 *
 * @param Integer $groupId
 * @param String  $usersSql
 *
 * @return Array
 */
function ugroup_count_project_admins($groupId, $usersSql) {
    $um = ugroup_get_user_manager();
    $admins    = 0;
    $nonAdmins = 0;
    $res = db_query($usersSql);
    while ($row = db_fetch_array($res)) {
        $user = $um->getUserById($row['user_id']);
        if ($user->isMember($groupId, 'A')) {
            $admins ++;
        } else {
            $nonAdmins ++;
        }
    }
    return array('admins' => $admins, 'non_admins' => $nonAdmins);
}

/**
 * Filter static ugroups that contain project admins.
 * Retun value is the number of non project admins
 * in the filtered ugroups.
 *
 * @param Integer $groupId
 * @param Array   $ugroups
 * @param Array   $validUgroups
 *
 * @return Integer
 */
function ugroup_count_non_admin_for_static_ugroups($groupId, $ugroups, &$validUGroups) {
    $containNonAdmin = 0;
    $uGroup = ugroup_get_ugroup();
    foreach ($ugroups as $ugroupId) {
        if ($uGroup->exists($groupId, $ugroupId)) {
            $sql = ugroup_db_get_members($ugroupId);
            $arrayUsers = ugroup_count_project_admins($groupId, $sql);
            $nonAdmin = $arrayUsers['non_admins'];
            $containAdmin = $arrayUsers['admins'];
            if ($containAdmin > 0) {
                $validUGroups[] = $ugroupId;
                $containNonAdmin += $nonAdmin;
            }
        }
    }
    return $containNonAdmin;
}

/**
 * Filter dynamic ugroups that contain project admins.
 * Retun is the number of non project admins
 * in the filtered ugroups.
 *
 * @param Integer $groupId
 * @param Array   $ugroups
 * @param Array   $validUgroups
 *
 * @return Integer
 */
function ugroup_count_non_admin_for_dynamic_ugroups($groupId, $ugroups, &$validUGroups) {
    $containNonAdmin = 0;
    foreach ($ugroups as $ugroupId) {
        $sql = ugroup_db_get_dynamic_members($ugroupId, null, $groupId);
        $arrayUsers = ugroup_count_project_admins($groupId, $sql);
        if ($arrayUsers['admins'] > 0) {
            $validUGroups[] = $ugroupId;
            $containNonAdmin += $arrayUsers['non_admins'];
        }
    }
    return $containNonAdmin;
}

/**
 * Validate the ugroup list containing group admins.
 * Remove ugroups that are empty or contain no project admins.
 * Don't remove ugroups containing both project admins and non project admins
 * just indicate the total number of non project admins.
 *
 * @param Integer $groupId
 * @param Array   $ugroups
 *
 * @return Array
 */
function ugroup_filter_ugroups_by_project_admin($groupId, $ugroups) {
    $validUGroups = array();
    // Check static ugroups
    $nonAdmins = ugroup_count_non_admin_for_static_ugroups($groupId, $ugroups, $validUGroups);
    // Check dynamic ugroups
    $nonAdmins += ugroup_count_non_admin_for_dynamic_ugroups($groupId, $ugroups, $validUGroups);
    return array('non_admins' => $nonAdmins, 'ugroups' => $validUGroups);
}

?>
