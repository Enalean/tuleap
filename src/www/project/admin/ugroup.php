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

// Show/manage ugroup list

require_once('pre.php');
require_once('www/project/admin/permissions.php');

$Language->loadLanguageMsg('project/project');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if (!isset($func)) $func="";
if ($func=='delete') {
    ugroup_delete($group_id, $ugroup_id);
}

if ($func=='do_update') {
    ugroup_update($_POST['group_id'], $_POST['ugroup_id'], $_POST['ugroup_name'], $_POST['ugroup_description'], $_POST['PickList']);
}



//
// Now display main page
//


project_admin_header(array('title'=>$Language->getText('project_admin_ugroup','manage_ug'),'group'=>$group_id,
			   'help' => 'UserGroups.html'));
$project=project_get_object($group_id);

print '<P><h2>'.$Language->getText('project_admin_ugroup','manage_ug_for',$project->getPublicName()).'</h2>';
print '
<P>
<H3><a href="/project/admin/editugroup.php?func=create&group_id='.$group_id.'">'.$Language->getText('project_admin_ugroup','create_ug').'</a></H3>
'.$Language->getText('project_admin_ugroup','create_ug_for_p').'
<p>


<H3>'.$Language->getText('project_admin_ugroup','edit_ug').'</H3>
<P>
<HR>';

echo '
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';

$title_arr=array();
$title_arr[]=$Language->getText('project_admin_ugroup','ug_name');
$title_arr[]=$Language->getText('project_admin_editugroup','desc');
$title_arr[]=$Language->getText('project_admin_ugroup','members');
$title_arr[]=$Language->getText('project_admin_servicebar','del?');
echo html_build_list_table_top($title_arr);
$row_num=0;

$result = db_query("SELECT * FROM ugroup WHERE group_id=100 ORDER BY ugroup_id");
while ($row = db_fetch_array($result)) {
    if ($project->usesDocman() || ($row['name'] != 'ugroup_document_tech_name_key' && $row['name'] != 'ugroup_document_admin_name_key')) {
        echo '<TR class="'. util_get_alt_row_color($row_num) .'">
                <TD>'.util_translate_name_ugroup($row['name']).' *</TD>';
        echo '<TD>'.util_translate_desc_ugroup($row['description']).'</TD>
    <TD align="center">-</TD>
    <TD align="center">-</TD>
    </TR>';
        $row_num++;
    }
}



if ($group_id != 100) {
  $result = db_query("SELECT * FROM ugroup WHERE group_id=$group_id ORDER BY name");
  if (db_numrows($result) > 0) {
    
    while ($row = db_fetch_array($result)) {
      echo '<TR class="'. util_get_alt_row_color($row_num) .'">
            <TD>
              <a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=edit">'.util_translate_name_ugroup($row['name']).'</TD>';
      echo '<TD>'.util_translate_desc_ugroup($row['description']).'</TD>';
      $res2=db_query("SELECT count(*) FROM ugroup_user WHERE ugroup_id=".$row['ugroup_id']);
      $nb_members=db_result($res2,0,0);
      if ($nb_members) echo '<TD align="center">'.$nb_members.'</TD>';
      else echo '<TD align="center">0</TD>';
      
      
      echo '<TD align="center"><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=delete" onClick="return confirm(\''.$Language->getText('project_admin_ugroup','del_ug').'\')"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('project_admin_servicebar','del').'"></A></TD>
</TR>';
      $row_num++;
    }
  }
}

echo '</TABLE>';
echo '<P>'.$Language->getText('project_admin_ugroup','predef_g');


project_admin_footer(array());


 

?>

