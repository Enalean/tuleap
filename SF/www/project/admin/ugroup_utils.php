<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

//
// Define various functions for user group management
//


// Predefined ugroups. Should be consistent with DB (table 'ugroup')
$UGROUP_NONE=100;
$UGROUP_ANONYMOUS=1;
$UGROUP_REGISTERED=2;
$UGROUP_PROJECT_MEMBERS=3;
$UGROUP_PROJECT_ADMINS=4;
$UGROUP_DOCUMENT_EDITOR=10;
$UGROUP_FILE_MANAGER_ADMIN=11;


// Return members (user_id + user_name) of given user group
function ugroup_db_get_members($ugroup_id) {	
  $sql="SELECT user.user_id, user.user_name ". 
    "FROM ugroup_user, user ".
    "WHERE user.user_id = ugroup_user.user_id ".
    "AND ugroup_user.ugroup_id=".$ugroup_id;
  return db_query($sql);
}

// Return name and id (as DB result) of all ugroups belonging to a specific project.
function ugroup_db_get_existing_ugroups($group_id) {
    $sql="SELECT ugroup_id, name FROM ugroup WHERE group_id=$group_id ORDER BY name";
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

/** Return user group name from ID */
function ugroup_get_name_from_id($ugroup_id) {
    $res=ugroup_db_get_ugroup($ugroup_id);
    return db_result($res,0,'name');
}

/**
 * Check membership of the user to a specified ugroup
 *
 *
 * @return true if user is member of the group, false otherwise.
 */
function ugroup_user_is_member($user_id, $ugroup_id) {
    global $group_id;
    // Special Cases
    if ($ugroup_id==$GLOBALS['UGROUP_NONE']) { 
        // Empty group
        return false;
    } else if ($ugroup_id==$GLOBALS['UGROUP_ANONYMOUS']) { 
        // Anonymous user
        return true;
    } else if ($ugroup_id==$GLOBALS['UGROUP_REGISTERED']) {
        // Registered user
        if (user_isloggedin()) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        if (user_ismember($group_id)) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_FILE_MANAGER_ADMIN']) {
        // File manager admins
        if (user_ismember($group_id,'R2')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_DOCUMENT_EDITOR']) {
        // Document editor
        if (user_ismember($group_id,'D1')) { return true; }
    } else if ($ugroup_id==$GLOBALS['UGROUP_PROJECT_ADMINS']) {
        // Project admins
        if (user_ismember($group_id,'A')) { return true; }
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
    return true;
}



/**
 * Create a new ugroup
 *
 * @return ugroup_id
 */
function ugroup_create($group_id, $ugroup_name, $ugroup_description, $group_templates) {
    global $feedback;

    // Sanity check
    if (!$ugroup_name) { 
        exit_error("ERROR",'The user group name is missing, please complete this information');
    }
    if (!eregi("^[a-zA-Z0-9_\-]+$",$ugroup_name)) {
        exit_error("ERROR","Invalid user group name: $ugroup_name. Please use only alphanumerical characters. Press the \"Back\" button and complete this information");
    }
    // Check that there is no ugroup with the same name in this project
    $sql = "SELECT * FROM ugroup WHERE name='$ugroup_name' AND group_id='$group_id'";
    $result=db_query($sql);
    if (db_numrows($result)>0) {
        exit_error("ERROR","User group '$ugroup_name' already exists in this project. Please choose another name."); 
    }
    
    
    // Create
    $sql = "INSERT INTO ugroup (name,description,group_id) VALUES ('$ugroup_name', '$ugroup_description',$group_id)";
    $result=db_query($sql);

    if (!$result) {
        exit_error("ERROR",'ERROR - Can not create user group: '.db_error());
    } else {
        $feedback .= " Successfully Created User Group ";
    }
    // Now get the corresponding ugroup_id
    $sql="SELECT ugroup_id FROM ugroup WHERE group_id=$group_id AND name='$ugroup_name'";
    $result = db_query($sql);
    if (!$result) {
        exit_error("ERROR",'ERROR - User group created but cannot get ID: '.db_error());
    }
    $ugroup_id = db_result($result,0,0);
    if (!$ugroup_id) {
        exit_error("ERROR",'ERROR - User group created but cannot get ID: '.db_error());
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
        $feedback .= " - no group template selected ";
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
                exit_error("ERROR","ERROR - Can not insert user ".$row['user_id']." in group $ugroup_id:".db_error());
            }
            $countuser++;
        }
        $feedback .= " - $countuser user".($countuser>1?'s':'')." added ";
    }
    return $ugroup_id;
}



/**
 * Update ugroup with list of members
 */
function ugroup_update($group_id, $ugroup_id, $ugroup_name, $ugroup_description, $pickList) {
    global $feedback;

    // Sanity check
    if (!$ugroup_name) { 
        exit_error("ERROR",'The group name is missing, please press the "Back" button and complete this information');
    }
    if (!eregi("^[a-zA-Z0-9_\-]+$",$ugroup_name)) {
        exit_error("ERROR","Invalid group name: $ugroup_name. Please use only alphanumerical characters. Press the \"Back\" button and complete this information");
    }
    if (!$ugroup_id) {
        exit_error("ERROR",'The ugroup ID is missing');
    }

    // Check that there is no ugroup with the same name and a different id in this project
    $sql = "SELECT * FROM ugroup WHERE name='$ugroup_name' AND group_id='$group_id' AND ugroup_id!='$ugroup_id'";
    $result=db_query($sql);
    if (db_numrows($result)>0) {
        exit_error("ERROR","User group '$ugroup_name' already exists in this project. Please choose another name."); 
    }

    // Update
    $sql = "UPDATE ugroup SET name='$ugroup_name', description='$ugroup_description' WHERE ugroup_id=$ugroup_id;";
    $result=db_query($sql);

    if (!$result) {
        exit_error("ERROR",'ERROR - Can not update user group: '.db_error());
    }

    // Reset members of the group
    $sql="DELETE FROM ugroup_user WHERE ugroup_id=$ugroup_id";
    if (!db_query($sql)) {
        exit_error("ERROR","ERROR - Can not reset user group $ugroup_id:".db_error());
    }

    // Then add all selected users
    $user_count=count($pickList);
    
    for ($i=0; $i<$user_count; $i++) {
        $sql="INSERT INTO ugroup_user (ugroup_id,user_id) VALUES ($ugroup_id,".$pickList[$i].")";
        if (!db_query($sql)) {
            exit_error("ERROR","ERROR - Can not insert user ".$pickList[$i]." in group $ugroup_id:".db_error());
        }
    }

    // Now log in project history
    group_add_history("Updated User Group",$ugroup_name,$group_id);

    $feedback .= " Successfully Updated User Group ".$ugroup_name." (".$user_count." members)";
}



/**
 * Delete ugroup 
 *
 * @return false if error
 */
function ugroup_delete($group_id, $ugroup_id) { 
    global $feedback;
    if (!$ugroup_id) {
        $feedback .= ' FAILED: ugroup ID was not specified ';
        return false;
    }
    $ugroup_name=ugroup_get_name_from_id($ugroup_id);
    $sql = "DELETE FROM ugroup WHERE group_id=$group_id AND ugroup_id=$ugroup_id";
        
    $result=db_query($sql);
    if (!$result || db_affected_rows($result) < 1) {
        $feedback .= ' UPDATE FAILED OR NO DATA CHANGED! '.db_error();
         return false;           
    }
    $feedback .= ' group deleted ';
    // Now remove users
    $sql = "DELETE FROM ugroup_user WHERE ugroup_id=$ugroup_id";
    
    $result=db_query($sql);
    if (!$result) {
        $feedback .= ' - Error: cannot remove users! '.db_error();
        return false;
    } 
    $feedback .= '- all users removed from this group ';
    // Last, remove permissions for this group
    $perm_cleared=permission_clear_ugroup($group_id, $ugroup_id); 
    if (!($perm_cleared)) {
        $feedback .= ' - Error: cannot remove permissions! '.db_error();
        return false;
    } else if ($perm_cleared>1) {
        $perm_cleared--;
        $feedback .= '- WARNING: '.$perm_cleared.' associated permissions deleted. <br>Note: If this group was the only one authorized to access an object, permissions are now reset to default for this object.';
    } 
    // Now log in project history
    group_add_history("Deleted User Group",$ugroup_name,$group_id);

    return true;
}
