<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//
//
//  Written for Codendi by Nicolas Guerin
//
// This script displays service details


require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');



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
  global $Language;
  
  // There is a special case for the 'Home Page' service: only the link can be modified (except for superuser)
  $hp=false;
  if ($service['short_name']=="homepage") {
    $hp=true;
  }
  // There is a special case for the 'Legacy docman' service: project admins can modify the label and description (but not the link)
  $is_legacy_docman=false;
  if ($service['short_name']=="doc") {
      $is_legacy_docman=true;
      if (user_ismember($group_id,'A')) {
          $ro = false;
      }
  }
  if ($su) { $ro=false; }
  echo '
<h3>'.$Language->getText('project_admin_editservice','s_conf').'</h3>';
  
  $hp =& Codendi_HTMLPurifier::instance();

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
<tr><td colspan=2><b>'.$Language->getText('project_admin_editservice','s_ident_desc').'</b></td></tr>
<tr><td width="10%"><a href="#" title="'.$Language->getText('project_admin_editservice','s_name_in_bar').'">'.$Language->getText('project_admin_editservice','s_label').': </a><font color="red">*</font></td>
<td>';
  if (!$ro) {
    echo '<input type="text" name="label" size="30" maxlength="40" value="'.$hp->purify($service['label']).'">';
  } else {
    if ($service['label'] == "service_".$service['short_name']."_lbl_key") {
      echo $Language->getText('project_admin_editservice',$service['label']);
    } else {
      echo $hp->purify($service['label']);
    }
  }
  echo '</td></tr>
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','url').'">'.$Language->getText('project_admin_editservice','s_link').':&nbsp;</a><font color="red">*</font></td>
<td>';
  if (((!$ro)||($hp)) && (!$is_legacy_docman || $su)) {
      $link_expected_title = _('Please, enter a http:// or https:// link');
    echo '<input type="text" name="link" size="70" maxlength="255" pattern="(https?://|#|/|\?).+" title="' . $link_expected_title . '" value="'.$hp->purify($service['link']).'">';
  } else {
    echo $service['link'];
    echo '<input type="hidden" name="link" VALUE="'.$service['link'].'">';
  }
  echo '</td></tr>';
  
  if (($su)&&$service['short_name']) {
    // Can't modify a shortname! Too many problems if the admin changes the system shortnames.
    echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_short_name').'">'.$Language->getText('project_admin_editservice','short_name').'</a>:&nbsp;</td>
<td>'.$service['short_name'].'</td></tr>';
  }
  
  echo '</td></tr>
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_desc_in_tooltip').'">'.$Language->getText('project_admin_editservice','s_desc').'</a>:&nbsp;</td>
<td>';
  if (!$ro) {
    echo '<input type="text" name="description" size="70" maxlength="255" value="'.$hp->purify($service['description']).'">';
  } else {
    if ($service['description'] == "service_".$service['short_name']."_desc_key") {
      echo $Language->getText('project_admin_editservice',$service['description']);
    } else {
      echo $hp->purify($service['description']);
    }
  }
  echo '</td></tr>';
  if (($su)&&($group_id==100)) {
    echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_scope').'">'.$Language->getText('project_admin_editservice','scope').':&nbsp;</a></td>
<td><FONT size="-1"><SELECT name="scope">
        <option value="system"'.(($service['scope']=="system")?" selected":"").'>'.$Language->getText('project_admin_editservice','system').'</option>
        <option value="project"'.(($service['scope']!="system")?" selected":"").'>'.$Language->getText('project_admin_editservice','project').'</option>
        </SELECT></FONT></td></tr>';
  } else {
    echo '<input type="hidden" name="scope" VALUE="'.$service['scope'].'"></td></tr>';
  }
  echo '
<tr><td colspan=2><b>'.$Language->getText('project_admin_editservice','display_options').'</b></td></tr>';
  if ($su) {
    echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','instanciated_for_new_p').'">'.$Language->getText('project_admin_editservice','available').':</a> </td><td><input type="CHECKBOX" NAME="is_active" VALUE="1"'.( $service['is_active'] ? ' CHECKED' : '' ).'></td></tr>';
  } else {
    print '<input type="hidden" name="is_active" VALUE="'.$service['is_active'].'">';
  }

  echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','display_in_s_bar').'">'.$Language->getText('project_admin_editservice','enabled').':</a> </td><td>';
  echo '<input type="CHECKBOX" NAME="is_used" VALUE="1"'.( $service['is_used'] ? ' CHECKED' : '' ) .'>';

echo '</td></tr>';
if ($service['scope'] == 'project') {
    echo '<tr><td>';
    echo '<a href="#" title="'. 'Display in iframe' .'">'. 'Display in iframe' .':</a> ';
    echo '</td><td>';
    echo '<input type="checkbox" name="is_in_iframe" value="1" '.( $service['is_in_iframe'] ? 'checked="checked"' : '' ).' />';
    echo '</td></tr>';
}

echo '<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','pos_in_s_bar').'">'.$Language->getText('project_admin_editservice','screen_rank').':&nbsp;</a><font color="red">*</font></td><td>';
if ($service['short_name']=='summary'){
    echo '<input type="text" name="rank" size="5" maxlength="5" value="'.$service['rank'].'" readonly>';
}else{
    echo '<input type="text" name="rank" size="5" maxlength="5" value="'.$service['rank'].'">';
}
echo '</td></tr>';

echo '</table>

<P><INPUT class="btn btn-primary" type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
</form>		
<p><font color="red">*</font>: '.$Language->getText('project_admin_editservice','fields_required').'</p>';
}



/* 
 * Display blank service form
 * Used for service creation
 */
function display_service_creation_form($group_id,$su) {
    global $sys_default_domain,$Language;
    $pm = ProjectManager::instance();
    $project=$pm->getProject($group_id);

    $link_expected_title = _('Please, enter a http:// or https:// link');
    echo '
<h3>'.$Language->getText('project_admin_editservice','s_creation').'</h3>
<form name="form_create" method="post" action="/project/admin/servicebar.php?group_id='.$group_id.'">
<input type="hidden" name="func" VALUE="do_create">
<input type="hidden" name="group_id" VALUE="'.$group_id.'">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td colspan=2><b>'.$Language->getText('project_admin_editservice','s_ident_desc').'</b></td></tr>
<tr><td width="10%"><a href="#" title="'.$Language->getText('project_admin_editservice','s_name_in_bar').'">'.$Language->getText('project_admin_editservice','s_label').':</a>&nbsp;<font color="red">*</font></td>
<td><input type="text" name="label" size="30" maxlength="40"></td></tr>
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','url').'">'.$Language->getText('project_admin_editservice','s_link').'</a>:&nbsp;<font color="red">*</font></td>
<td><input type="text" name="link" size="70" maxlength="255" pattern="(https?://|#|/|\?).+" title="' . $link_expected_title . '"></td></tr>';
if (($group_id==100)&&($su)) {
    echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_short_name').$Language->getText('project_admin_editservice','mandatory').'">'.$Language->getText('project_admin_editservice','short_name').'</a>:&nbsp;<font color="red">*</font> </td>
<td><input type="text" name="short_name" size="15" maxlength="40"></td></tr>';
    }
echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_desc_in_tooltip').'">'.$Language->getText('project_admin_editservice','s_desc').'</a>:&nbsp;</td>
<td><input type="text" name="description" size="70" maxlength="255"></td></tr>';
if (($group_id==100)&&($su)) {
echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','s_scope').'">'.$Language->getText('project_admin_editservice','scope').':</a></td>
<td><FONT size="-1"><SELECT name="scope">
        <option value="system" selected>'.$Language->getText('project_admin_editservice','system').'</option>
        <option value="project">'.$Language->getText('project_admin_editservice','project').'</option>
        </SELECT></FONT></td></tr>';
} else {
    echo '<input type="hidden" name="scope" VALUE="project">';
}
echo '
<tr><td colspan=2><b>'.$Language->getText('project_admin_editservice','display_options').'</b></td></tr>';
if (($group_id==100)&&($su)) {
  echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','instanciated_for_new_p').'">'.$Language->getText('project_admin_editservice','available').':</a> </td>
<td><input type="CHECKBOX" NAME="is_active" VALUE="1" CHECKED></td></tr>';
} else {
    print '<input type="hidden" name="is_active" VALUE="1">';
}
echo '
<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','display_in_s_bar').'">'.$Language->getText('project_admin_editservice','enabled').':</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1" CHECKED></td></tr>';

echo '<tr><td>';
echo '<a href="#" title="'. 'Display in iframe' .'">'. 'Display in iframe' .':</a> ';
echo '</td><td>';
echo '<input type="hidden" name="is_in_iframe" value="0" />';
echo '<input type="checkbox" name="is_in_iframe" value="1" />';
echo '</td></tr>';

echo '<tr><td><a href="#" title="'.$Language->getText('project_admin_editservice','pos_in_s_bar').'">'.$Language->getText('project_admin_editservice','screen_rank').'</a>:&nbsp;<font color="red">*</font></td>
<td><input type="text" name="rank" size="5" maxlength="5">
</td></tr>
</table>

<P><INPUT class="btn btn-primary" type="submit" name="Create" value="'.$Language->getText('global','btn_create').'">
</form>
<p><font color="red">*</font>: '.$Language->getText('project_admin_editservice','fields_required').'</p>
';

}


session_require(array('group'=>$group_id,'admin_flags'=>'A'));
$pm = ProjectManager::instance();
$project=$pm->getProject($group_id);

project_admin_header(array('title'=>$Language->getText('project_admin_editservice','edit_s'),'group'=>$group_id,
			   'help' => 'project-admin.html#service-configuration'));

// $func is either: 
// 'create' -> blank form that allow service creation (-> do_create)
// '' -> show service and allow modification (-> do_update) 


$func = $request->get('func');

if (isset($func) && $func=="create") {
    $is_superuser=false;
    if (user_is_super_user()) {
            $is_superuser=true;
    }
    display_service_creation_form($group_id,$is_superuser);
}
else {
    $service_id = $request->getValidated('service_id', 'uint', 0);
    if (!$service_id) {
        exit_error('ERROR','Service Id was not specified ');
    }
    
    $sql = "SELECT * FROM service WHERE group_id=$group_id AND service_id=$service_id";
    
    $result=db_query($sql);
    if (db_numrows($result) < 1) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_editservice','s_not_exist',$service_id));
    }
    $service = db_fetch_array($result);
    $readonly=false;
    $is_superuser=true;
    if (!user_is_super_user()) {
        $is_superuser=false;
        if (!$service['is_active']) {
            exit_error($Language->getText('project_admin_editservice','forbidden'),$Language->getText('project_admin_editservice','no_access_inactive_s'));
        }
        if ($service['scope']=="system") {
            // Display service as read-only
            $readonly=true;
        }
    }

    if (! ServiceManager::instance()->isServiceAllowedForProject($project, $service_id)) {
        exit_error('ERROR', $GLOBALS['Language']->getText('project_admin_servicebar', 'not_allowed'));
    }

    display_service_configuration_form($group_id, $service_id, $service, $readonly, $is_superuser);
}



project_admin_footer(array());


?>
