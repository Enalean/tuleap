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

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/file/file_utils.php');
require_once('www/docman/doc_utils.php');

$Language->loadLanguageMsg('project/project');

function display_name_and_desc_form($ugroup_name,$ugroup_description) {
  global $Language;

    echo '	<table width="100%" border="0" cellpadding="5">
	<tr> 
	  <td width="21%"><b>'.$Language->getText('project_admin_editugroup','name').'</b>:</td>
	  <td width="79%"> 
	    <input type="text" name="ugroup_name" value="'.$ugroup_name.'">
	  </td>
	</tr>
        <tr><td colspan=2><i>'.$Language->getText('project_admin_editugroup','avoid_special_ch').'</td></tr>
	<tr> 
	  <td width="21%"><b>'.$Language->getText('project_admin_editugroup','desc').'</b>:</td>
	  <td width="79%"> 
	  <textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
	  </td>
	</tr>';
}




session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if (browser_is_netscape4()) {
    exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','browser_not_accepted'));
    return;
}

if (!$func) $func='create';


if ($func=='do_create') {
    $ugroup_id=ugroup_create($_POST['group_id'], $_POST['ugroup_name'], $_POST['ugroup_description'], $_POST['group_templates']);
}



if ($func=='create') {
    project_admin_header(array('title'=>$Language->getText('project_admin_editugroup','create_ug'),'group'=>$group_id,
			   'help' => 'UserGroups.html#UGroupCreation'));
    $project=project_get_object($group_id);

    print '<P><h2>'.$Language->getText('project_admin_editugroup','create_ug_for',$project->getPublicName()).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup','fill_ug_desc').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
	<input type="hidden" name="func" value="do_create">
	<input type="hidden" name="group_id" value="'.$group_id.'">';
    display_name_and_desc_form($ugroup_name,$ugroup_description);
    echo '<tr> 
	  <td width="21%"><b>'.$Language->getText('project_admin_editugroup','create_from').'</b>:</td>
	  <td width="79%">';
    //<textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
    $group_arr=array();
    $group_arr[]=$Language->getText('project_admin_editugroup','empty_g');
    $group_arr_value[]='cx_empty';
    $group_arr[]='-------------------';
    $group_arr_value[]='cx_empty2';
    $group_arr[]=$Language->getText('project_admin_editugroup','proj_members');
    $group_arr_value[]='cx_members';
    $group_arr[]=$Language->getText('project_admin_editugroup','proj_admins');
    $group_arr_value[]='cx_admins';
    $group_arr[]='-------------------';
    $group_arr_value[]='cx_empty2';
    $res= ugroup_db_get_existing_ugroups($group_id);
    while ($row = db_fetch_array($res)) {
        $group_arr[]=$row['name'];
        $group_arr_value[]=$row['ugroup_id'];
    }
    echo html_build_select_box_from_arrays ($group_arr_value,$group_arr,"group_templates",'cx_empty',false);
     
    echo '</td>
	</tr><tr><td><input type="submit" value="'.$Language->getText('project_admin_editugroup','create_ug').'"></tr></td>
        </table>
      </form>';
}


if (($func=='edit')||($func=='do_create')) {
    // Sanity check
    if (!$ugroup_id) { 
        exit_error($Language->getText('global','error'),'The ugroup ID is missing');
    }
    $res=ugroup_db_get_ugroup($ugroup_id);
    if (!$res) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','ug_not_found',array($ugroup_id,db_error())));
    }
    if (!$ugroup_name) { $ugroup_name=db_result($res,0,'name'); }
    if (!$ugroup_description) { $ugroup_description=db_result($res,0,'description'); }

    project_admin_header(array('title'=>$Language->getText('project_admin_editugroup','edit_ug'),'group'=>$group_id,
			   'help' => 'UserGroups.html#UGroupCreation'));
    print '<P><h2>'.$Language->getText('project_admin_editugroup','ug_admin',$ugroup_name).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup','upd_ug_name').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.$group_id.'" onSubmit="return selIt();">
	<input type="hidden" name="func" value="do_update">
	<input type="hidden" name="group_id" value="'.$group_id.'">
	<input type="hidden" name="ugroup_id" value="'.$ugroup_id.'">';
    display_name_and_desc_form($ugroup_name,$ugroup_description);
    echo '</table>';
	
    // Get existing members from group
    $sql="SELECT user_id FROM ugroup_user WHERE ugroup_id=$ugroup_id";
    $res = db_query($sql);
    if (db_numrows($res)>0) {
        while ($row = db_fetch_array($res)) {
            $user_in_group[$row['user_id']]=1;
        }
    }

    echo '
<style>
.t1 { visibility:hidden; }
.t2 { visibility:visible; }
</style>
<p><b>'.$Language->getText('project_admin_editugroup','select_ug_members').'</b>
<SCRIPT src="/include/filterlist.js" type="text/javascript"></SCRIPT>

<TABLE cellpadding=0 cellspacing=0>
<TR>
<TD>
'.$Language->getText('project_admin_editugroup','quick_filters').'
<A title="'.$Language->getText('project_admin_editugroup','show_items','A').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^A\');switchMessage(1)">A</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','B').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^B\');switchMessage(1)">B</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','C').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^C\');switchMessage(1)">C</A>  
<A title="'.$Language->getText('project_admin_editugroup','show_items','D').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^D\');switchMessage(1)">D</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','E').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^E\');switchMessage(1)">E</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','F').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^F\');switchMessage(1)">F</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','G').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^G\');switchMessage(1)">G</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','H').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^H\');switchMessage(1)">H</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','I').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^I\');switchMessage(1)">I</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','J').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^J\');switchMessage(1)">J</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','K').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^K\');switchMessage(1)">K</A> <br>
<A title="'.$Language->getText('project_admin_editugroup','show_items','L').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^L\');switchMessage(1)">L</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','M').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^M\');switchMessage(1)">M</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','N').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^N\');switchMessage(1)">N</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','O').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^O\');switchMessage(1)">O</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','P').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^P\');switchMessage(1)">P</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','Q').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Q\');switchMessage(1)">Q</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','R').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^R\');switchMessage(1)">R</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','S').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^S\');switchMessage(1)">S</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','T').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^T\');switchMessage(1)">T</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','U').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^U\');switchMessage(1)">U</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','V').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^V\');switchMessage(1)">V</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','W').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^W\');switchMessage(1)">W</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','X').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^X\');switchMessage(1)">X</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','Y').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Y\');switchMessage(1)">Y</A> 
<A title="'.$Language->getText('project_admin_editugroup','show_items','Z').'" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Z\');switchMessage(1)">Z</A> 
</TD>
<td></td>
<td></td>
</tr>
<tr>
<TD align="right">
<SELECT multiple size=16 name="SelectList" ID="SelectList"> ';

    // Display list of users

    // First make a quick hash of this project's restricted users
    $current_group_restricted_users=array();
    $sql="SELECT user.user_id from user, user_group WHERE user.status='R' AND user.user_id=user_group.user_id AND user_group.group_id=$group_id";
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
        $current_group_restricted_users[$row['user_id']] = true;
    }

    $sql="SELECT user_id, user_name, realname, status FROM user WHERE status='A' OR status='R' ORDER BY user_name";
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
        // Don't display restricted users that don't belong to the project
        if ($row['status']=='R') { 
            if (!$current_group_restricted_users[$row['user_id']]) {
                continue;
            }
        }
        // Don't display users that already belong to the group
        if (!$user_in_group[$row['user_id']]) {
            echo '<option value='.$row['user_id'].'>'.$row['user_name'].' ('.addslashes($row['realname']).")\n";
        } else {
            $member_id[]=$row['user_id'];
            $member_name[]=$row['user_name'].' ('.addslashes($row['realname']).")";
        }
    }


    echo '
</SELECT>
</TD>
<TD align="center">
&nbsp;<INPUT TYPE="BUTTON" VALUE="  ->  " ONCLICK="addIt();"></INPUT>&nbsp;<BR>
&nbsp;<INPUT TYPE="BUTTON" VALUE="  <-  " ONCLICK="delIt();"></INPUT>&nbsp;
</TD>
<TD align="left">
<SELECT NAME="PickList[]" ID="PickList" SIZE="16" multiple>
<OPTION VALUE="01sel">'.$Language->getText('project_admin_editugroup','sel01').'</OPTION>
</SELECT>
</TD>
</TR>
<TR>
<TD ALIGN="middle">
<p class=t1 id=textone><b>&nbsp;'.$Language->getText('project_admin_editugroup','please_wait').'</b></p>

<SCRIPT type=text/javascript>

<!--

initIt();
var myfilter = new filterlist(document.form_create.SelectList);
var _I=1;
function switchMessage(_I)
{
  if(document.getElementById)
    document.getElementById("textone").className="t"+_I;  
}';

    // Then add all existing members
    $member_count=count($member_id);
    
    for ($i=0; $i<$member_count; $i++) {
        echo 'addToPickListInit('.$member_id[$i].',"'.$member_name[$i].'");';
    }
    echo '
//-->

</SCRIPT>

<P>'.$Language->getText('project_admin_editugroup','filter').' 
<INPUT onkeydown="switchMessage(2)" onkeyup="myfilter.set(this.value);switchMessage(1)" name=regexp>
<INPUT onMousedown="switchMessage(2)" onclick="myfilter.reset();this.form.regexp.value=\'\';switchMessage(1)" type=button value="Reset"> 
</TD>
<TD></TD>
<TD ALIGN="left">
<p>&nbsp;<p>
<INPUT TYPE="submit" VALUE="'.$Language->getText('global','btn_submit').'">
</TD>
</TR>
</TABLE>
</FORM>
';

    // Display associated permissions
    $sql="SELECT * FROM permissions WHERE ugroup_id=$ugroup_id ORDER BY permission_type";
    $res=db_query($sql);
    if (db_numrows($res)>0) {
        echo '
<hr><p><b>'.$Language->getText('project_admin_editugroup','ug_perms').'</b>
<p>';
        
        $title_arr=array();
        $title_arr[]=$Language->getText('project_admin_editugroup','permission');
        $title_arr[]=$Language->getText('project_admin_editugroup','resource_name');
        echo html_build_list_table_top($title_arr,false,false,false);
        $row_num=0;
        
        while ($row = db_fetch_array($res)) {
            echo '<TR class="'. util_get_alt_row_color($row_num) .'">';
            echo '<TD>'.permission_get_name($row['permission_type']).'</TD>';
            if ($row['permission_type'] == 'PACKAGE_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','package').' <a href="/file/admin/editpackagepermissions.php?package_id='.$row['object_id'].'&group_id='.$group_id.'">'.file_get_package_name_from_id($row['object_id']).'</a></TD>';
            } else if ($row['permission_type'] == 'RELEASE_READ') {
                $package_id=file_get_package_id_from_release_id($row['object_id']);
                echo '<TD>'.$Language->getText('project_admin_editugroup','release').' <a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$group_id.'&package_id='.$package_id.'">'.file_get_release_name_from_id($row['object_id']).'</a> (from package <a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id.'">'.file_get_package_name_from_id($package_id).'</a>)</TD>';
            } else if ($row['permission_type'] == 'DOCUMENT_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','document').' <a href="/docman/admin/editdocpermissions.php?docid='.$row['object_id'].'&group_id='.$group_id.'">'.util_unconvert_htmlspecialchars(doc_get_title_from_id($row['object_id'])).'</a></TD>';
            } else if ($row['permission_type'] == 'DOCGROUP_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','document_group').' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$row['object_id'].'&group_id='.$group_id.'">'.doc_get_docgroupname_from_id($row['object_id']).'</a></TD>';
            } else {
                echo '<TD>'.$row['object_id'].'</TD>';
            }

            echo '</TR>
';
            $row_num++;
        }
        echo '</table><p>';
    }


}


$HTML->footer(array());



?>
