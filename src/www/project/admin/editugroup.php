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
require_once('common/html/HTML_Element_Pane.class.php');
require_once('common/project/UGroupBindingViewer.class.php');

$hp      = Codendi_HTMLPurifier::instance();
$request = HTTPRequest::instance();

function _breadCrumbs($project, $ugroupId = NULL, $ugroupName = NULL) {
    $hp = Codendi_HTMLPurifier::instance();
    $breadcrumbs['/projects/'. $project->getUnixName(true)] = $project->getPublicName();
    $breadcrumbs['/project/admin/?group_id='. (int)$project->getId()]= 'Admin';
    $breadcrumbs['/project/admin/ugroup.php?group_id='.(int)$project->getId()] = 'Users groups';
    if (isset($ugroupId) && ($ugroupId)) {
        $breadcrumbs['/project/admin/editugroup.php?func=edit&group_id='.(int)$project->getId().'&ugroup_id='.(int)$ugroupId] = $ugroupName;
    }

    $content = '<ul class="breadcrumb" >';
    $firstItem = true;
    foreach($breadcrumbs as $url => $title) {
        $content .= '<li>';
        if (!$firstItem) {
            $content .= '<span class="breadcrumb-sep">»</span>';
        }
        $firstItem = false;
        $content .= '<a href="'. get_server_url() .$url.'" />'.$hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML).'</a>';
        $content .= '</li>';
    }
    $content .= '</ul>';
    return $content;
}

function display_name_and_desc_form($ugroup_name, $ugroup_description) {
    global $Language;

    return ' <table width="100%" border="0" cellpadding="5">
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

    function get_ugroup_binding() {
        $ugroupUserDao = new UGroupUserDao();
        $ugroupManager = new UGroupManager(new UGroupDao());
        return new UGroupBinding($ugroupUserDao, $ugroupManager);
    }

$group_id = $request->getValidated('group_id', 'GroupId', 0);
session_require(array('group' => $group_id, 'admin_flags' => 'A'));
$pm = ProjectManager::instance();
$project=$pm->getProject($group_id);

$vFunc = new Valid_WhiteList('func', array('create', 'do_create', 'edit'));
$vFunc->required();
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
    echo _breadCrumbs($project);
    //print '<P><h2>'.$Language->getText('project_admin_editugroup', 'create_ug_for', $project->getPublicName()).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup', 'fill_ug_desc').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
    <input type="hidden" name="func" value="do_create">
    <input type="hidden" name="group_id" value="'.$group_id.'">';
    echo display_name_and_desc_form(isset($ugroup_name)?$ugroup_name:'', isset($ugroup_description)?$ugroup_description:'');
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

    $uGroupMgr = new UGroupManager();

    $vPane = new Valid_WhiteList('pane', array('settings', 'members', 'bind', 'permissions', 'usage', 'ugroup_binding'));
    $vPane->required();
    $pane  = $request->getValidated('pane', $vPane, 'settings');

    $panes = array(array('name'  => 'settings',
                         'link'  => '/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=settings',
                         'title' =>  $Language->getText('global', 'settings')),
                   array('name'  => 'members',
                         'link'  => '/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=members',
                         'title' =>  $Language->getText('admin_grouplist', 'members')),
                   array('name'  => 'permissions',
                         'link'  => '/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=permissions',
                         'title' =>  $Language->getText('project_admin_utils', 'event_permission')),
                   array('name'  => 'usage',
                         'link'  => '/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=usage',
                         'title' =>  $Language->getText('global', 'usage')));

    $content = '';
    $activePane = $pane;
    switch ($pane) {
    case 'settings':
        $content .= '<p>'.$Language->getText('project_admin_editugroup', 'upd_ug_name').'</p>';
        $content .= '<form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.$group_id.'" onSubmit="return selIt();">
        <input type="hidden" name="func" value="do_update">
        <input type="hidden" name="group_id" value="'.$group_id.'">
        <input type="hidden" name="ugroup_id" value="'.$ugroup_id.'">';
        $content .= display_name_and_desc_form($ugroup_name, $ugroup_description);
        $content .= '<tr><td></td><td><input type="submit" value="'.$Language->getText('global', 'btn_submit').'" /></td></tr>';
        $content .= '</table>';
        $content .= '</form>';
    break;
    case 'members':
        $uGroupMgr->processEditMembersAction($group_id, $ugroup_id, $request);
        $content .= $uGroupMgr->displayUgroupMembers($group_id, $ugroup_id, $request);
    break;
    case 'bind':
        $activePane = 'members';
        $urlAdd     = '/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=ugroup_binding';
        $linkAdd    = '<br/><a href="'.$urlAdd.'">- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding_title').'</a><br/>';
        if ($ldapBinding = $uGroupMgr->displayUgroupBinding($group_id, $ugroup_id)) {
            $content .= $ldapBinding;
        } else {
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit&pane=ugroup_binding');
        }
        $content .= $linkAdd;
    break;
    case 'permissions':
        // Display associated permissions
        $pm = PermissionsManager::instance();
        $dar = $pm->searchByUgroupId($ugroup_id);
        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            $content .= '<p><b>'.$Language->getText('project_admin_editugroup', 'ug_perms').'</b><p>';

            $title_arr = array();
            $title_arr[] = $Language->getText('project_admin_editugroup', 'permission');
            $title_arr[] = $Language->getText('project_admin_editugroup', 'resource_name');
            $content .= html_build_list_table_top($title_arr, false, false, false);
            $row_num = 0;

            foreach ($dar as $row) {
                if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $atid = permission_extract_atid($row['object_id']);
                    if (isset($tracker_field_displayed[$atid])) {
                        continue;
                    }
                    $objname = permission_get_object_name('TRACKER_ACCESS_FULL', $atid);
                } else {
                    $objname = permission_get_object_name($row['permission_type'], $row['object_id']);
                }
                $content .= '<TR class="'. util_get_alt_row_color($row_num) .'">';
                $content .= '<TD>'.permission_get_name($row['permission_type']).'</TD>';
                if ($row['permission_type'] == 'PACKAGE_READ') {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'package')
                        .' <a href="/file/admin/editpackagepermissions.php?package_id='
                        .$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'RELEASE_READ') {
                    $package_id=file_get_package_id_from_release_id($row['object_id']);
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'release')
                        .' <a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$group_id.'&package_id='.$package_id.'">'
                        .file_get_release_name_from_id($row['object_id']).'</a> ('
                        .$Language->getText('project_admin_editugroup', 'from_package')
                        .' <a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCUMENT_READ') {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'document')
                        .' <a href="/docman/admin/editdocpermissions.php?docid='.$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCGROUP_READ') {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'document_group')
                        .' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$row['object_id'].'&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKI_READ') {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'wiki')
                        .' <a href="/wiki/admin/index.php?view=wikiPerms&group_id='.$group_id.'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'wiki_page')
                        .' <a href="/wiki/admin/index.php?group_id='.$group_id.'&view=pagePerms&id='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'tracker') 
                        .' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id='.$group_id.'&atid='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $tracker_field_displayed[$atid]=1;
                    $atid =permission_extract_atid($row['object_id']);
                    $content .= '<TD>'.$Language->getText('project_admin_editugroup', 'tracker_field')
                        .' <a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'&func=permissions&perm_type=fields&group_first=1&selected_id='.$ugroup_id.'">' 
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'TRACKER_ARTIFACT_ACCESS') {
                    $content .= '<td>'. $hp->purify($objname, CODENDI_PURIFIER_BASIC) .'</td>';
                } else {
                    $results = false;
                    $em->processEvent('permissions_for_ugroup', array(
                        'permission_type' => $row['permission_type'],
                        'object_id'       => $row['object_id'],
                        'objname'         => $objname,
                        'group_id'        => $group_id,
                        'ugroup_id'       => $ugroup_id,
                        'results'         => &$results
                    ));
                    if ($results) {
                        $content .= '<TD>'.$results.'</TD>';
                    } else {
                        $content .= '<TD>'.$row['object_id'].'</TD>';
                    }
                }

                $content .= '</TR>';
                $row_num++;
            }
            $content .= '</table><p>';
        } else {
            $content .= '<p>'.$Language->getText('project_admin_editugroup', 'no_perms').'.</p>';
        }
    break;
    case 'usage':
        $ugroupBinding = get_ugroup_binding();
        $bindingiewer  = new UGroupBindingViewer($ugroupBinding, ProjectManager::instance());
        $content .= $bindingiewer->getUsagePaneContent($group_id, $ugroup_id);
    break;
    case 'ugroup_binding':
        $activePane    = 'members';
        $groupId       = $request->getValidated('group_id', 'GroupId', 0);
        $ugroupId      = $request->getValidated('ugroup_id', 'uint', 0);
        $sourceProject = $request->getValidated('source_project', 'GroupId', 0);
        $ugroupBinding = get_ugroup_binding();
        $ugroupBinding->processRequest($ugroupId, $request);

        $bindingiewer = new UGroupBindingViewer($ugroupBinding, ProjectManager::instance());
        $content     .= $bindingiewer->getUgtoupBindingPaneContent($groupId, $ugroupId, $sourceProject);
    break;
    }

    project_admin_header(array('title' => $Language->getText('project_admin_editugroup', 'edit_ug'), 'group' => $group_id, 'help' => 'UserGroups.html#UGroupCreation'));
    //print '<P><h2>'.$Language->getText('project_admin_editugroup', 'ug_admin', $ugroup_name).'</h2>';
    echo _breadCrumbs($project, $ugroup_id, $ugroup_name);

    $HTMLPane = new HTML_Element_Pane($panes, $activePane, $content);
    echo $HTMLPane->renderValue();

}

$HTML->footer(array());

?>