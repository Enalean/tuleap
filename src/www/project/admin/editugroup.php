<?php
/**
 * Codendi
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * http://www.codendi.com
 *
 * Originally written by Nicolas Guerin 2004, Codendi Team, Xerox
 */

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/file/file_utils.php');
require_once('www/docman/doc_utils.php');
require_once 'common/project/UGroupManager.class.php';

$hp      = Codendi_HTMLPurifier::instance();
$request = HTTPRequest::instance();

function display_name_and_desc_form($ugroup_name, $ugroup_description) {
    global $Language;

    echo '  <table width="100%" border="0" cellpadding="5">
    <tr>
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'name').'</b>:</td>
      <td width="79%"> 
        <input type="text" name="ugroup_name" value="'.$ugroup_name.'">
      </td>
    </tr>
        <tr><td colspan=2><i>'.$Language->getText('project_admin_editugroup', 'avoid_special_ch').'</td></tr>
    <tr> 
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'desc').'</b>:</td>
      <td width="79%"> 
      <textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
      </td>
    </tr>';
}



$group_id = $request->getValidated('group_id', 'GroupId', 0);
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$vFunc = new Valid_WhiteList('func', array('create', 'do_create', 'edit'));
$func = $request->getValidated('func', $vFunc, 'create');

if ($request->isPost() && $func == 'do_create') {
    $name = $request->getValidated('ugroup_name', 'String', '');
    $desc = $request->getValidated('ugroup_description', 'String', '');
    $tmpl = $request->getValidated('group_templates', 'String', '');
    $ugroup_id = ugroup_create($group_id, $name, $desc, $tmpl);
    $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit');
}

if ($func=='create') {
    project_admin_header(array('title' => $Language->getText('project_admin_editugroup', 'create_ug'), 'group' => $group_id, 'help' => 'UserGroups.html#UGroupCreation'));
    $pm = ProjectManager::instance();
    $project=$pm->getProject($group_id);

    print '<P><h2>'.$Language->getText('project_admin_editugroup', 'create_ug_for', $project->getPublicName()).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup', 'fill_ug_desc').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
    <input type="hidden" name="func" value="do_create">
    <input type="hidden" name="group_id" value="'.$group_id.'">';
    display_name_and_desc_form(isset($ugroup_name)?$ugroup_name:'', isset($ugroup_description)?$ugroup_description:'');
    echo '<tr> 
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'create_from').'</b>:</td>
      <td width="79%">';
    //<textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
    $group_arr         = array();
    $group_arr[]       = $Language->getText('project_admin_editugroup', 'empty_g');
    $group_arr_value[] = 'cx_empty';
    $group_arr[]       = '-------------------';
    $group_arr_value[] = 'cx_empty2';
    $group_arr[]       = $Language->getText('project_admin_editugroup', 'proj_members');
    $group_arr_value[] = 'cx_members';
    $group_arr[]       = $Language->getText('project_admin_editugroup', 'proj_admins');
    $group_arr_value[] = 'cx_admins';
    $group_arr[]       = '-------------------';
    $group_arr_value[] = 'cx_empty2';
    $res               = ugroup_db_get_existing_ugroups($group_id);
    while ($row = db_fetch_array($res)) {
        $group_arr[]       = $row['name'];
        $group_arr_value[] = $row['ugroup_id'];
    }
    echo html_build_select_box_from_arrays($group_arr_value, $group_arr, "group_templates", 'cx_empty', false);
     
    echo '</td>
            </tr><tr><td><input type="submit" value="'.$Language->getText('project_admin_editugroup', 'create_ug').'"></tr></td>
        </table>
      </form>';
}


if (($func=='edit')||($func=='do_create')) {
    // Sanity check
    if (!$ugroup_id) { 
        exit_error($Language->getText('global', 'error'), 'The ugroup ID is missing');
    }
    $res=ugroup_db_get_ugroup($ugroup_id);
    if (!$res) {
        exit_error($Language->getText('global', 'error'), $Language->getText('project_admin_editugroup', 'ug_not_found', array($ugroup_id, db_error())));
    }
    if (!isset($ugroup_name) || !$ugroup_name) {
        $ugroup_name = db_result($res, 0, 'name');
    }
    if (!isset($ugroup_description) || !$ugroup_description) {
        $ugroup_description = db_result($res, 0, 'description');
    } else {
        $ugroup_description = stripslashes($ugroup_description);
    }

    project_admin_header(array('title' => $Language->getText('project_admin_editugroup', 'edit_ug'), 'group' => $group_id, 'help' => 'UserGroups.html#UGroupCreation'));
    print '<P><h2>'.$Language->getText('project_admin_editugroup', 'ug_admin', $ugroup_name).'</h2>';

    $vPane = new Valid_WhiteList('pane', array('settings', 'members', 'permissions', 'usage'));
    $vPane->required();
    $pane  = $request->getValidated('pane', $vPane, 'settings');

    echo '<div class="tabbable tabs-left">';
    echo '<ul class="nav nav-tabs">';
    echo '<li class="'. ($pane == 'settings' ? 'active' : '') .'">';
    echo '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=settings">'.$Language->getText('global', 'settings').'</a></li>';
    echo '<li class="'. ($pane == 'members' ? 'active' : '') .'">';
    echo '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=members">'.$Language->getText('admin_grouplist', 'members').'</a></li>';
    echo '<li class="'. ($pane == 'permissions' ? 'active' : '') .'">';
    echo '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=permissions">'.$Language->getText('project_admin_utils', 'event_permission').'</a></li>';
    echo '<li class="'. ($pane == 'usage' ? 'active' : '') .'">';
    echo '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=usage">'.$Language->getText('global', 'usage').'</a></li>';
    echo '</ul>';
    echo '<div class="tab-content">';
    echo '<div class="tab-pane active">';

    switch ($pane) {
    case 'settings':
        echo '<p>'.$Language->getText('project_admin_editugroup', 'upd_ug_name').'</p>';
        echo '<form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.$group_id.'" onSubmit="return selIt();">
        <input type="hidden" name="func" value="do_update">
        <input type="hidden" name="group_id" value="'.$group_id.'">
        <input type="hidden" name="ugroup_id" value="'.$ugroup_id.'">';
        display_name_and_desc_form($ugroup_name, $ugroup_description);
        echo '<tr><td></td><td><input type="submit" value="'.$Language->getText('global', 'btn_submit').'" /></td></tr>';
        echo '</table>';
        echo '</form>';
    break;
    case 'members':
        $uGroupMgr                = new UGroupManager();
        $uGroup                   = new UGroup(array('ugroup_id' => $ugroup_id));
        $ugroupUpdateUsersAllowed = !$uGroup->isBound();
        $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroup_id, 'allowed' => &$ugroupUpdateUsersAllowed));

        echo '<p><b>'.$Language->getText('project_admin_editugroup', 'group_members').'</b></p>';
        echo '<div style="padding-left:10px">';

        // Get existing members from group
        $uGroup  = $uGroupMgr->getById($request->get('ugroup_id'));
        $members = $uGroup->getMembers();
        if (count($members) > 0) {
            echo '<form action="ugroup_remove_user.php" method="POST">';
            echo '<input type="hidden" name="group_id" value="'.$group_id.'">';
            echo '<input type="hidden" name="ugroup_id" value="'.$ugroup_id.'">';
            echo '<table>';
            $i = 0;
            $userHelper = UserHelper::instance();
            foreach ($members as $user) {
                echo '<tr class="'. html_get_alt_row_color(++$i) .'">';
                echo '<td>'. $hp->purify($userHelper->getDisplayNameFromUser($user)) .'</td>';
                if ($ugroupUpdateUsersAllowed) {
                    echo '<td>';
                    project_admin_display_bullet_user($user->getId(), 'remove', 'ugroup_remove_user.php?group_id='. $group_id. '&ugroup_id='. $ugroup_id .'&user_id='. $user->getId());
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
            echo '</form>';
        } else {
            echo $Language->getText('project_admin_editugroup', 'nobody_yet');
        }

        if ($ugroupUpdateUsersAllowed) {
            echo '<p><a href="ugroup_add_users.php?group_id='. $group_id .'&amp;ugroup_id='. $ugroup_id .'">'. $GLOBALS['HTML']->getimage('/ic/add.png') .$Language->getText('project_admin_editugroup', 'add_user').'</a></p>';
            echo '</div>';
        }

        echo '<p><a href="/project/admin/ugroup.php?group_id='. $group_id .'">&laquo; '.$Language->getText('project_admin_editugroup', 'go_back').'</a></p>';
    break;
    case 'permissions':
        // Display associated permissions
        $sql = "SELECT * FROM permissions WHERE ugroup_id=".db_ei($ugroup_id)." ORDER BY permission_type";
        $res = db_query($sql);
        if (db_numrows($res)>0) {
            echo '<p><b>'.$Language->getText('project_admin_editugroup', 'ug_perms').'</b><p>';

            $title_arr = array();
            $title_arr[] = $Language->getText('project_admin_editugroup', 'permission');
            $title_arr[] = $Language->getText('project_admin_editugroup', 'resource_name');
            echo html_build_list_table_top($title_arr, false, false, false);
            $row_num = 0;

            while ($row = db_fetch_array($res)) {
                if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $atid = permission_extract_atid($row['object_id']);
                    if (isset($tracker_field_displayed[$atid])) {
                        continue;
                    }
                    $objname = permission_get_object_name('TRACKER_ACCESS_FULL', $atid);
                } else {
                    $objname = permission_get_object_name($row['permission_type'], $row['object_id']);
                }
                echo '<TR class="'. util_get_alt_row_color($row_num) .'">';
                echo '<TD>'.permission_get_name($row['permission_type']).'</TD>';
                if ($row['permission_type'] == 'PACKAGE_READ') {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'package')
                        .' <a href="/file/admin/editpackagepermissions.php?package_id='
                        .$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'RELEASE_READ') {
                    $package_id=file_get_package_id_from_release_id($row['object_id']);
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'release')
                        .' <a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$group_id.'&package_id='.$package_id.'">'
                        .file_get_release_name_from_id($row['object_id']).'</a> ('
                        .$Language->getText('project_admin_editugroup', 'from_package')
                        .' <a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCUMENT_READ') {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'document')
                        .' <a href="/docman/admin/editdocpermissions.php?docid='.$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCGROUP_READ') {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'document_group')
                        .' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKI_READ') {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'wiki')
                        .' <a href="/wiki/admin/index.php?view=wikiPerms&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'wiki_page')
                        .' <a href="/wiki/admin/index.php?group_id='.$group_id.'&view=pagePerms&id='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'tracker') 
                        .' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id='.$group_id.'&atid='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $tracker_field_displayed[$atid]=1;
                    $atid =permission_extract_atid($row['object_id']);
                    echo '<TD>'.$Language->getText('project_admin_editugroup', 'tracker_field')
                        .' <a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'&func=permissions&perm_type=fields&group_first=1&selected_id='.$ugroup_id.'">' 
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'TRACKER_ARTIFACT_ACCESS') {
                    echo '<td>'. $hp->purify($objname, CODENDI_PURIFIER_BASIC) .'</td>';
                } else {
                    $results = false;
                    $em =& EventManager::instance();
                    $em->processEvent('permissions_for_ugroup', array(
                        'permission_type' => $row['permission_type'],
                        'object_id'       => $row['object_id'],
                        'objname'         => $objname,
                        'group_id'        => $group_id,
                        'ugroup_id'       => $ugroup_id,
                        'results'         => &$results
                    ));
                    if ($results) {
                        echo '<TD>'.$results.'</TD>';
                    } else {
                        echo '<TD>'.$row['object_id'].'</TD>';
                    }
                }

                echo '</TR>';
                $row_num++;
            }
            echo '</table><p>';
        }
    break;
    case 'usage':
        
    break;
    }

    echo '</div>';
    echo '</div>';

}

$HTML->footer(array());

?>