<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2004. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Nicolas Guérin
//
// This script displays service details


require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');


/* 
 * Display service configuration form
 * @param $group_id   - int - the group ID
 * @param $service_id - int - the service ID
 * @param $service    - array - Contains all service parameters (from DB query)
 * @param $ro         - bool - if true, then display an ALMOST read-only form (e.g. for system-wide 
 *                      services). In fact the 'is_used' and 'rank' values are still configurable.
 *                      Moreover, if the service is 'homepage' the link is also configurable. 
 * @param $su         - bool - true if the current user is a super-user (site admin). In this case
 *                      $ro is automatically set to false, and even system services are editable.
 */
function display_service_configuration_form($group_id, $service_id, $service, $ro, $su) {
    
    // There is a special case for the 'Home Page' service: only the link can be modified (except for superuser)
    $hp=false;
    if ($service['short_name']=="homepage") {
        $hp=true;
    }
    if ($su) { $ro=false; }
    echo '
<h3>Service Configuration</h3>';

    echo '
<form method="post" name="form_update" action="/project/admin/servicebar.php?group_id='.$group_id.'">
<input type="hidden" name="func" VALUE="do_update">
<input type="hidden" name="service_id" VALUE="'.$service_id.'">
<input type="hidden" name="group_id" VALUE="'.$group_id.'">';
    if ($service['short_name']) {
        echo '
<input type="hidden" name="short_name" VALUE="'.$service['short_name'].'">';
    }

    if ($ro) {
        echo '
<input type="hidden" name="label" VALUE="'.$service['label'].'">
<input type="hidden" name="description" VALUE="'.$service['description'].'">
';
    }

    echo '
<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td colspan=2><b>Service Identification and Description</b></td></tr>
<tr><td width="10%"><a href="#" title="Name that is displayed in the Service Bar">Service Label: </a><font color="red">*</font></td>
<td>';
if (!$ro) {
    echo '<input type="text" name="label" size="30" maxlength="40" value="'.$service['label'].'">';
} else {
    echo $service['label'];
}
echo '</td></tr>
<tr><td><a href="#" title="URL pointed by the service">Service Link: </a><font color="red">*</font></td>
<td>';
if ((!$ro)||($hp)) {
    echo '<input type="text" name="link" size="70" maxlength="255" value="'.$service['link'].'">';
} else {
    echo $service['link'];
    echo '<input type="hidden" name="link" VALUE="'.$service['link'].'">';
}
echo '</td></tr>';

if (($su)&&$service['short_name']) {
    // Can't modify a shortname! Too many problems if the admin changes the system shortnames.
     echo '
<tr><td><a href="#" title="Short name for the service">Short name:</a></td>
<td>'.$service['short_name'].'</td></tr>';
}

echo '</td></tr>
<tr><td><a href="#" title="Service Description will be displayed in a tooltip above service label.">Service Description:</a></td>
<td>';
if (!$ro) {
    echo '<input type="text" name="description" size="70" maxlength="255" value="'.$service['description'].'">';
} else {
    echo $service['description'];
}
 echo '</td></tr>';
if (($su)&&($group_id==100)) {
echo '
<tr><td><a href="#" title="Scope of the service: project only or system-wide">Scope:</a></td>
<td><FONT size="-1"><SELECT name="scope">
        <option value="system"'.(($service['scope']=="system")?" selected":"").'>system</option>
        <option value="project"'.(($service['scope']!="system")?" selected":"").'>project</option>
        </SELECT></FONT></td></tr>';
} else {
    echo '<input type="hidden" name="scope" VALUE="'.$service['scope'].'"></td></tr>';
}
echo '
<tr><td colspan=2><b>Display Options</b></td></tr>';
if ($su) {
  echo '
<tr><td><a href="#" title="Is instanciated for new projects?">Available:</a> </td><td><input type="CHECKBOX" NAME="is_active" VALUE="1"'.( $service['is_active'] ? ' CHECKED' : '' ).'></td></tr>';
} else {
    print '<input type="hidden" name="is_active" VALUE="'.$service['is_active'].'">';
}

echo '
<tr><td><a href="#" title="Is displayed in the service bar?">Enabled:</a> </td><td>';
echo '<input type="CHECKBOX" NAME="is_used" VALUE="1"'.( $service['is_used'] ? ' CHECKED' : '' ).'>';

echo '</td></tr>
<tr><td><a href="#" title="Position in service bar">Rank on screen:</a> <font color="red">*</font></td><td>';
echo '<input type="text" name="rank" size="5" maxlength="5" value="'.$service['rank'].'">';
echo '
</td></tr>
</table>

<P><INPUT type="submit" name="Update" value="Update">
</form>		
<p><font color="red">*</font>: fields required</p>';
}



/* 
 * Display blank service form
 * Used for service creation
 */
function display_service_creation_form($group_id,$su) {
    global $sys_default_domain;
    $project=project_get_object($group_id);
 
    echo '
<h3>Service Creation</h3>
<form name="form_create" method="post" action="/project/admin/servicebar.php?group_id='.$group_id.'">
<input type="hidden" name="func" VALUE="do_create">
<input type="hidden" name="group_id" VALUE="'.$group_id.'">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td colspan=2><b>Service Identification and Description</b></td></tr>
<tr><td width="10%"><a href="#" title="Name that is displayed in the Service Bar">Service Label: </a><font color="red">*</font></td>
<td><input type="text" name="label" size="30" maxlength="40"></td></tr>
<tr><td><a href="#" title="URL pointed by the service">Service Link: </a><font color="red">*</font></td>
<td><input type="text" name="link" size="70" maxlength="255"></td></tr>';
if (($group_id==100)&&($su)) {
    echo '
<tr><td><a href="#" title="Short name for the service: mandatory for system-wide services">Short name:</a><font color="red">*</font> </td>
<td><input type="text" name="short_name" size="15" maxlength="40"></td></tr>';
    }
echo '
<tr><td><a href="#" title="Service Description will be displayed in a tooltip above service label.">Service Description:</a></td>
<td><input type="text" name="description" size="70" maxlength="255"></td></tr>';
if (($group_id==100)&&($su)) {
echo '
<tr><td><a href="#" title="Scope of the service: project only or system-wide">Scope:</a></td>
<td><FONT size="-1"><SELECT name="scope">
        <option value="system" selected>system</option>
        <option value="project">project</option>
        </SELECT></FONT></td></tr>';
} else {
    echo '<input type="hidden" name="scope" VALUE="project">';
}
echo '
<tr><td colspan=2><b>Display Options</b></td></tr>';
if (($group_id==100)&&($su)) {
  echo '
<tr><td><a href="#" title="Is instanciated for new projects?">Available:</a> </td>
<td><input type="CHECKBOX" NAME="is_active" VALUE="1" CHECKED></td></tr>';
} else {
    print '<input type="hidden" name="is_active" VALUE="1">';
}
echo '
<tr><td><a href="#" title="Is displayed in the service bar?">Enabled:</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1" CHECKED></td></tr>
<tr><td><a href="#" title="Position in service bar">Rank on screen:</a> <font color="red">*</font></td>
<td><input type="text" name="rank" size="5" maxlength="5">
</td></tr>
</table>

<P><INPUT type="submit" name="Create" value="Create">
</form>
<p><font color="red">*</font>: fields required</p>
';

}


session_require(array('group'=>$group_id,'admin_flags'=>'A'));
$project=project_get_object($group_id);

project_admin_header(array('title'=>'Edit Service','group'=>$group_id,
			   'help' => 'ServiceConfiguration.html'));

// $func is either: 
// 'create' -> blank form that allow service creation (-> do_create)
// '' -> show service and allow modification (-> do_update) 



if ($func=="create") {
    $is_superuser=false;
    if (user_is_super_user()) {
            $is_superuser=true;
    }
    display_service_creation_form($group_id,$is_superuser);
}
else {

    if (!$service_id) {
        exit_error('ERROR','Service Id was not specified ');
    }
    
    $sql = "SELECT * FROM service WHERE group_id=$group_id AND service_id=$service_id";
    
    $result=db_query($sql);
    if (db_numrows($result) < 1) {
        exit_error('ERROR','Service does not exist: '.$service_id);
    }
    $service = db_fetch_array($result);
    $readonly=false;
    $is_superuser=true;
    if (!user_is_super_user()) {
        $is_superuser=false;
        if (!$service['is_active']) {
            exit_error('Forbidden','You cannot access an inactive service');
        }
        if ($service['scope']=="system") {
            // Display service as read-only
            $readonly=true;
        }
    }
    display_service_configuration_form($group_id, $service_id, $service, $readonly, $is_superuser);
}



project_admin_footer(array());





?>

