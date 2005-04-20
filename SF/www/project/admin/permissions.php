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

// Supported object types and related object_id:
//
// type='PACKAGE_READ'  id='package_id'  table='frs_package'
// type='RELEASE_READ'  id='release_id'  table='frs_release'
// type='DOCUMENT_READ' id='docid"       table='doc_data'
// type='DOCGROUP_READ' id='doc_group'   table='doc_groups'
// type='WIKI_READ'     id='group_id'    table='wiki_page'
// type='WIKIPAGE_READ' id='id'          table='wiki_page'
 

require_once('www/project/admin/ugroup_utils.php');
require_once('www/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('project/project');

/**
 * Return a printable name for a given permission type
 */
function permission_get_name($permission_type) {
  global $Language;
    if ($permission_type=='PACKAGE_READ') {
        return $Language->getText('project_admin_permissions','pack_download');
    } else if ($permission_type=='RELEASE_READ') {
        return $Language->getText('project_admin_permissions','rel_download');
    } else if ($permission_type=='DOCGROUP_READ') {
        return $Language->getText('project_admin_permissions','docgroup_access');
    } else if ($permission_type=='DOCUMENT_READ') {
        return $Language->getText('project_admin_permissions','doc_access');
    } else if ($permission_type=='WIKI_READ') {
        return $Language->getText('project_admin_permissions','wiki_access');
    } else if ($permission_type=='WIKIPAGE_READ') {
        return $Language->getText('project_admin_permissions','wiki_access');
    } else return $permission_type;
}

/**
 * Return a the type of a given object
 */
function permission_get_object_type($permission_type,$object_id) {
    if ($permission_type=='PACKAGE_READ') {
        return 'package';
    } else if ($permission_type=='RELEASE_READ') {
        return 'release';
    } else if ($permission_type=='DOCUMENT_READ') {
        return 'document';
    } else if ($permission_type=='DOCGROUP_READ') {
        return 'docgroup';
    } else if ($permission_type=='WIKI_READ') {
        return "wiki ";
    } else if ($permission_type=='WIKIPAGE_READ') {
        return "wikipage";
    } else return 'object';
}
/**
 * Return a the type of a given object
 */
function permission_get_object_name($permission_type,$object_id) {
    if ($permission_type=='PACKAGE_READ') {
        return file_get_package_name_from_id($object_id);
    } else if ($permission_type=='RELEASE_READ') {
        return file_get_release_name_from_id($object_id);
    } else if ($permission_type=='DOCUMENT_READ') {
        return doc_get_title_from_id($object_id);
    } else if ($permission_type=='DOCGROUP_READ') {
        return doc_get_docgroupname_from_id($object_id);
    } else if ($permission_type=='WIKI_READ') {
        return "$object_id";
    } else if ($permission_type=='WIKIPAGE_READ') {
        return "$object_id";
    } else return "$object_id";
}

/**
 * Return the name for a given object
 */
function permission_get_object_fullname($permission_type,$object_id) {
  global $Language;
  
  $type = permission_get_object_type($permission_type,$object_id);
  $name = permission_get_object_name($permission_type,$object_id);
  return $Language->getText('project_admin_permissions',$type,$name);
}

/**
 * Check if the current user is allowed to change permissions, depending on the permission_type
 */
function permission_user_allowed_to_change($group_id, $permission_type) {
    if ($permission_type=='PACKAGE_READ') {
        return (user_ismember($group_id,'R2'));
    }
    if ($permission_type=='RELEASE_READ') {
        return (user_ismember($group_id,'R2'));
    }
    if ($permission_type=='DOCGROUP_READ') {
        return (user_ismember($group_id,'D2'));
    }
    if ($permission_type=='DOCUMENT_READ') {
        return (user_ismember($group_id,'D2'));
    }
    if ($permission_type=='WIKI_READ') {
        return (user_ismember($group_id,'W2'));
    }
    if ($permission_type=='WIKIPAGE_READ') {
        return (user_ismember($group_id,'W2'));
    }
    return false;
}

/**
 * Return a DB list of ugroup_ids authorized to access the given object
 */
function permission_db_authorized_ugroups($permission_type, $object_id) {
    $sql="SELECT ugroup_id FROM permissions WHERE permission_type='$permission_type' AND object_id='$object_id' ORDER BY ugroup_id";
    // note that 'order by' is needed for comparing ugroup_lists (see permission_equals_to_default)
    return db_query($sql);
}


/**
 * Return a DB list of the default ugroup_ids authorized to access the given permission_type
 */
function permission_db_get_defaults($permission_type) {
    $sql="SELECT ugroup_id FROM permissions_values WHERE permission_type='$permission_type' AND is_default='1' ORDER BY ugroup_id";
    return db_query($sql);
}


/**
 * Check if the given object has some permissions defined
 *
 * @return true if permissions are defined, false otherwise.
 */
function permission_exist($permission_type, $object_id) {
    $res=permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res) < 1) {
        // No group defined => no permissions set
        return false;
    } else return true;
}




/**
 * Check permissions on the given object
 *
 * @param $permission_type defines the type of permission (e.g. "DOCUMENT_READ")
 * @param $object_id is the ID of the object we want to access (e.g. a docid)
 * @param $user_id is the ID of the user that want to access the object
 * @param $group_id is the group_id the object belongs to; useful for project-specific authorized ugroups (e.g. 'project admins')
 * @return true if user is authorized, false otherwise.
 */
function permission_is_authorized($permission_type, $object_id, $user_id, $group_id) {

    // Super-user has all rights...
    if (user_is_super_user()) return true;

    $res=permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res) < 1) {
        // No ugroup defined => no permissions set => get default permissions
        $res=permission_db_get_defaults($permission_type);
    } 
    // permissions set for this object.
    while ($row = db_fetch_array($res)) {
        // should work even for anonymous users
        if (ugroup_user_is_member($user_id, $row['ugroup_id'], $group_id)) {
            return true;
        }
    }
    return false;
}



/**
 * Display permission selection box for the given object.
 * The result of this form should be parsed with permission_process_selection_form()
 *
 * For the list of supported permission_type and id, see above in file header.
 */

function permission_display_selection_form($permission_type, $object_id, $group_id, $post_url) {
  global $Language;
    if (!$post_url) $post_url=$_SERVER['PHP_SELF'];

    // Get ugroups already defined for this permission_type
    $res_ugroups=permission_db_authorized_ugroups($permission_type, $object_id);
    $nb_set=db_numrows($res_ugroups);

    // Now retrieve all possible ugroups for this project, as well as the default values
    $sql="SELECT ugroup_id,is_default FROM permissions_values WHERE permission_type='$permission_type'";
    $res=db_query($sql);
    $predefined_ugroups='';
    $default_values=array();
    if (db_numrows($res)<1) {
        echo "<p><b>".$Language->getText('global','error')."</b>: ".$Language->getText('project_admin_permissions','perm_type_not_def',$permission_type);
        return;
    } else { 
        while ($row = db_fetch_array($res)) {
            if ($predefined_ugroups) { $predefined_ugroups.= ' ,';}
            $predefined_ugroups .= $row['ugroup_id'] ;
            if ($row['is_default']) $default_values[]=$row['ugroup_id'];
        }
    }
    $sql="SELECT * FROM ugroup WHERE group_id=".$group_id." OR ugroup_id IN (".$predefined_ugroups.") ORDER BY ugroup_id";
    $res=db_query($sql);

    // Display form
    echo '<FORM ACTION="'. $post_url .'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_permissions">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="permission_type" VALUE="'.$permission_type.'">
		<INPUT TYPE="HIDDEN" NAME="object_id" VALUE="'.$object_id.'">';
    echo html_build_multiple_select_box($res,"ugroups[]",($nb_set?util_result_column_to_array($res_ugroups):$default_values),8, true, 'nobody', false, '', false, '',false);
    echo '<p><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('project_admin_permissions','submit_perm').'">';
    echo '<INPUT TYPE="SUBMIT" NAME="reset" VALUE="'.$Language->getText('project_admin_permissions','reset_to_def').'">';
    echo '</FORM>';
    echo '<p>'.$Language->getText('project_admin_permissions','admins_create_modify_ug',array("/project/admin/editugroup.php?func=create&group_id=$group_id","/project/admin/ugroup.php?group_id=$group_id"));
}


/**
 * Clear all permissions for the given object
 * Access rights to this function are checked (must be project admin!)
 * @return false if error, true otherwise
*/

function permission_clear_all($group_id, $permission_type, $object_id, $log_permission_history=true) {
    if (!permission_user_allowed_to_change($group_id, $permission_type)) { return false;}
    $sql = "DELETE FROM permissions WHERE permission_type='$permission_type' AND object_id=$object_id";
    $res=db_query($sql);
    if (!$res) { 
        return false;
    } else {
        // Log permission change
        if ($log_permission_history) { permission_add_history($group_id,$permission_type, $object_id);}
        return true;
    }
}


/**
 * Clear all permissions for the given ugroup
 * Access rights to this function are checked (must be project admin!)
 * @return false if error, number of permissions deleted+1 otherwise
 * (why +1? because there might be no permission, but no error either,
 *  so '0' means error, and 1 means no error but no permission)
*/

function permission_clear_ugroup($group_id, $ugroup_id) {
    if (!user_ismember($group_id,'A')) { return false;}
    $sql = "DELETE FROM permissions WHERE ugroup_id='$ugroup_id'";
    $res=db_query($sql);
    if (!$res) { 
        return false;
    } else return (db_affected_rows($res)+1);
}



/**
 * Effectively update permissions for the given object.
 * Access rights to this function are checked.
 */
function permission_add_ugroup($group_id, $permission_type, $object_id, $ugroup_id) {
    if (!permission_user_allowed_to_change($group_id, $permission_type)) { return false;}
    $sql = "INSERT INTO permissions (permission_type, object_id, ugroup_id) VALUES ('$permission_type', $object_id, $ugroup_id)";
    $res=db_query($sql);
    if (!$res) {
        return false;
    } else {
        return true;
    }
}



/**
 * Return true if the permissions set for the given object are the same as the default values
 * Return false if they are different
 */
function permission_equals_to_default($permission_type, $object_id) {
    $res1=permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res1)==0) { 
        // No ugroup defined means default values
        return true; 
    }
    $res2=permission_db_get_defaults($permission_type);
    if (db_numrows($res1)!=db_numrows($res2)) return false;
    while ($row1= db_fetch_array($res1)) {
        $row2 = db_fetch_array($res2);
        if ($row1['ugroup_id']!=$row2['ugroup_id']) { return false; }
    }
    return true;
}


/** 
 * Log permission change in project history
 */
function permission_add_history($group_id, $permission_type, $object_id){
  global $Language;
    $res=permission_db_authorized_ugroups($permission_type, $object_id);
    $type = properties_get_object_type($permission_type, $object_id);
    $name = properties_get_object_name($permission_type, $object_id);

    if (db_numrows($res) < 1) {
        // No ugroup defined => no permissions set 
        group_add_history('perm_reset_for_'.$type, 'default', $group_id, array($name));
        return;
    } 
    $ugroup_list='';
    while ($row = db_fetch_array($res)) {
        if ($ugroup_list) { $ugroup_list.=', ';}
        $ugroup_list.= ugroup_get_name_from_id($row['ugroup_id']);
    }
    group_add_history('perm_granted_for_'.$type, $ugroup_list, $group_id, array($name));
}




/**
 * Updated permissions according to form generated by permission_display_selection_form()
 * 
 * parameter $ugroups contains the list of ugroups to authorize for this object.
 *
 * @return a two elements array:
 *  - First element is 'true' or 'false', depending on whether permissions where changed
 *  - Second element is an optional message to be displayed (warning or error)
 * Exemples: (false,"Cannot combine 'any registered user' with another group)
 *           (true,"Removed 'nobody' from the list")
 **/
 
function permission_process_selection_form($group_id, $permission_type, $object_id, $ugroups) {
  global $Language;
    // Check that we have all parameters
    if (!$object_id) {
        return array(false,$Language->getText('project_admin_permissions','obj_id_missed'));
    }
    if (!$permission_type) {
        return array(false,$Language->getText('project_admin_permissions','perm_type_missed'));
    }
    if (!$group_id) {
        return array(false,$Language->getText('project_admin_permissions','g_id_missed'));
    }
  

    // Check consistency of ugroup list
    $num_ugroups=0;
    while (list(,$selected_ugroup) = each($ugroups)) {
        $num_ugroups++;
        if ($selected_ugroup==$GLOBALS['UGROUP_ANONYMOUS']) { $anon_selected=1; }
        if ($selected_ugroup==$GLOBALS['UGROUP_REGISTERED']) { $any_selected=1; }
    }

    // Reset permissions for this object, before setting the new ones
    permission_clear_all($group_id, $permission_type, $object_id, false);

    // Set new permissions
    $msg='';
    if ($anon_selected) {
        if (permission_add_ugroup($group_id, $permission_type, $object_id, $GLOBALS['UGROUP_ANONYMOUS'])) {
            $msg .= $Language->getText('project_admin_permissions','all_users_added');
        } else {
            return array(false, $Language->getText('project_admin_permissions','cant_add_ug_anonymous',$msg));
        }
        if ($num_ugroups>1) {
            $msg .= $Language->getText('project_admin_permissions','ignore_g');
        }
    } else if ($any_selected) {
        if (permission_add_ugroup($group_id, $permission_type, $object_id, $GLOBALS['UGROUP_REGISTERED'])) {
            $msg .= $Language->getText('project_admin_permissions','all_registered_users_added')." ";
        } else {
            return array(false, $Language->getText('project_admin_permissions','cant_add_ug_reg_users',$msg));
        }
        if ($num_ugroups>1) {
            $msg.=$Language->getText('project_admin_permissions','ignore_g');
        }
    } else {
        reset($ugroups);
        while (list(,$selected_ugroup) = each($ugroups)) {
            if ($selected_ugroup==$GLOBALS['UGROUP_NONE']) {
                if ($num_ugroups>1) {
                    $msg .= $Language->getText('project_admin_permissions','g_nobody_ignored')." ";
                    continue;
                } else $msg .= $Language->getText('project_admin_permissions','nobody_has_no_access')." ";
            }
            if (permission_add_ugroup($group_id, $permission_type, $object_id, $selected_ugroup)) {
                # $msg .= "+g$selected_ugroup ";
            } else {
                return array(false, $Language->getText('project_admin_permissions','cant_add_ug',array($msg,$selected_ugroup)));
            }
        }
    }
    // If selected permission is the same as default, then don't store it!
    if (permission_equals_to_default($permission_type, $object_id)) {
        permission_clear_all($group_id, $permission_type, $object_id, false);
        $msg.=' '.$Language->getText('project_admin_permissions','def_val');
    }
    permission_add_history($group_id,$permission_type, $object_id);
    return array(true, $Language->getText('project_admin_permissions','perm_update_success',$msg));
}

?>
