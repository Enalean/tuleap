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
require_once 'common/project/UGroupManager.class.php';
require_once 'common/project/Admin/ProjectUGroup/UGroupRouter.class.php';

$request = HTTPRequest::instance();

function get_name_and_desc_form_content($ugroup_name, $ugroup_description) {
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();

    return ' <table width="100%" border="0" cellpadding="5">
    <tr>
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'name').'</b>:</td>
      <td width="79%"> 
        <input type="text" name="ugroup_name" value="'.$purifier->purify($ugroup_name).'">
      </td>
    </tr>
        <tr><td colspan=2><i>'.$Language->getText('project_admin_editugroup', 'avoid_special_ch').'</td></tr>
    <tr> 
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'desc').'</b>:</td>
      <td width="79%"> 
      <textarea name="ugroup_description" rows="3" cols="50">'.$purifier->purify($ugroup_description).'</textarea>
      </td>
    </tr>';
}

$group_id = $request->getValidated('group_id', 'GroupId', 0);
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$vFunc = new Valid_WhiteList('func', array('create', 'do_create', 'edit'));
$vFunc->required();
$func = $request->getValidated('func', $vFunc, 'create');

$name = $request->getValidated('ugroup_name', 'String', '');
$desc = $request->getValidated('ugroup_description', 'String', '');
if ($request->isPost() && $func == 'do_create') {
    $tmpl = $request->getValidated('group_templates', 'String', '');
    $ugroup_id = ugroup_create($group_id, $name, $desc, $tmpl);
    $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$ugroup_id.'&func=edit');
}

if ($func=='create') {
    project_admin_header(array('title' => $Language->getText('project_admin_editugroup', 'create_ug'), 'group' => $group_id, 'help' => 'project-admin.html#creating-a-user-group'));
    echo '<p>'.$Language->getText('project_admin_editugroup', 'fill_ug_desc').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
    <input type="hidden" name="func" value="do_create">
    <input type="hidden" name="group_id" value="'.$group_id.'">';
    echo get_name_and_desc_form_content($name, $desc);
    echo '<tr> 
      <td width="21%"><b>'.$Language->getText('project_admin_editugroup', 'create_from').'</b>:</td>
      <td width="79%">';
    $group_arr = array(
        $Language->getText('project_admin_editugroup', 'empty_g'),
        '-------------------',
        $Language->getText('project_admin_editugroup', 'proj_members'),
        $Language->getText('project_admin_editugroup', 'proj_admins'),
        '-------------------',
    );
    $group_arr_value = array(
        'cx_empty',
        'cx_empty2',
        'cx_members',
        'cx_admins',
        'cx_empty2',
    );
    $res = ugroup_db_get_existing_ugroups($group_id);
    while ($row = db_fetch_array($res)) {
        $group_arr[]       = $row['name'];
        $group_arr_value[] = $row['ugroup_id'];
    }
    echo html_build_select_box_from_arrays($group_arr_value, $group_arr, "group_templates", 'cx_empty', false);
     
    echo '</td>
            </tr><tr><td><input type="submit" value="'.$Language->getText('project_admin_editugroup', 'create_ug').'"></tr></td>
        </table>
      </form>';
    $HTML->footer(array());
}

if (($func=='edit')||($func=='do_create')) {
    $router = new Project_Admin_UGroup_UGroupRouter();
    $router->process($request);
}

?>