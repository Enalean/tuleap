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

require('pre.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require ($DOCUMENT_ROOT.'/project/admin/ugroup_utils.php');
require ($DOCUMENT_ROOT.'/file/file_utils.php');


function display_name_and_desc_form($ugroup_name,$ugroup_description) {
    echo '	<table width="100%" border="0" cellpadding="5">
	<tr> 
	  <td width="21%"><b>Name</b>:</td>
	  <td width="79%"> 
	    <input type="text" name="ugroup_name" value="'.$ugroup_name.'">
	  </td>
	</tr>
        <tr><td colspan=2><i>Please avoid space and punctuation in user group names</td></tr>
	<tr> 
	  <td width="21%"><b>Description</b>:</td>
	  <td width="79%"> 
	  <textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
	  </td>
	</tr>';
}




session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if (browser_is_netscape4()) {
    exit_error('Error','Sorry, your browser (Netscape 4.x) is not supported. In order to edit or create a user group, please use a different browser');
    return;
}

if (!$func) $func='create';


if ($func=='do_create') {
    $ugroup_id=ugroup_create($_POST['group_id'], $_POST['ugroup_name'], $_POST['ugroup_description'], $_POST['group_templates']);
}



if ($func=='create') {
    project_admin_header(array('title'=>'Create User Group','group'=>$group_id,
			   'help' => 'GroupConfiguration.html'));
    $project=project_get_object($group_id);

    print '<P><h2>Creating new user group for <B>'.$project->getPublicName().'</B></h2>';
    echo '<p>Please, fill the name and description of this new user group:</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
	<input type="hidden" name="func" value="do_create">
	<input type="hidden" name="group_id" value="'.$group_id.'">';
    display_name_and_desc_form($ugroup_name,$ugroup_description);
    echo '<tr> 
	  <td width="21%"><b>Create from</b>:</td>
	  <td width="79%">';
    //<textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
    $group_arr=array();
    $group_arr[]='Empty Group';
    $group_arr_value[]='cx_empty';
    $group_arr[]='-------------------';
    $group_arr_value[]='cx_empty2';
    $group_arr[]='Project Members';
    $group_arr_value[]='cx_members';
    $group_arr[]='Project Admins';
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
	</tr><tr><td><input type="submit" value="Create User Group"></tr></td>
        </table>
      </form>';
}


if (($func=='edit')||($func=='do_create')) {
    // Sanity check
    if (!$ugroup_id) { 
        exit_error("ERROR",'The ugroup ID is missing');
    }
    $res=ugroup_db_get_ugroup($ugroup_id);
    if (!$res) {
        exit_error("ERROR","ERROR - Can not find ugroup:$ugroup_id -".db_error());
    }
    if (!$ugroup_name) { $ugroup_name=db_result($res,0,'name'); }
    if (!$ugroup_description) { $ugroup_description=db_result($res,0,'description'); }

    project_admin_header(array('title'=>'Edit User Group','group'=>$group_id,
			   'help' => 'GroupConfiguration.html'));
    print '<P><h2>User Group \'<B>'.$ugroup_name.'</B>\' - Administration</h2>';
    echo '<p>You can update the name and description of this user group:</p>';
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
<p><b>Please select user group members:</b>
<SCRIPT src="/include/filterlist.js" type="text/javascript"></SCRIPT>

<TABLE cellpadding=0 cellspacing=0>
<TR>
<TD>
Quick Filters: 
<A title="Show items starting with A" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^A\');switchMessage(1)">A</A> 
<A title="Show items starting with B" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^B\');switchMessage(1)">B</A> 
<A title="Show items starting with C" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^C\');switchMessage(1)">C</A>  
<A title="Show items starting with D" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^D\');switchMessage(1)">D</A> 
<A title="Show items starting with E" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^E\');switchMessage(1)">E</A> 
<A title="Show items starting with F" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^F\');switchMessage(1)">F</A> 
<A title="Show items starting with G" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^G\');switchMessage(1)">G</A> 
<A title="Show items starting with H" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^H\');switchMessage(1)">H</A> 
<A title="Show items starting with I" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^I\');switchMessage(1)">I</A> 
<A title="Show items starting with J" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^J\');switchMessage(1)">J</A> 
<A title="Show items starting with K" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^K\');switchMessage(1)">K</A> <br>
<A title="Show items starting with L" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^L\');switchMessage(1)">L</A> 
<A title="Show items starting with M" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^M\');switchMessage(1)">M</A> 
<A title="Show items starting with N" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^N\');switchMessage(1)">N</A> 
<A title="Show items starting with O" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^O\');switchMessage(1)">O</A> 
<A title="Show items starting with P" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^P\');switchMessage(1)">P</A> 
<A title="Show items starting with Q" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Q\');switchMessage(1)">Q</A> 
<A title="Show items starting with R" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^R\');switchMessage(1)">R</A> 
<A title="Show items starting with S" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^S\');switchMessage(1)">S</A> 
<A title="Show items starting with T" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^T\');switchMessage(1)">T</A> 
<A title="Show items starting with U" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^U\');switchMessage(1)">U</A> 
<A title="Show items starting with V" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^V\');switchMessage(1)">V</A> 
<A title="Show items starting with W" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^W\');switchMessage(1)">W</A> 
<A title="Show items starting with X" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^X\');switchMessage(1)">X</A> 
<A title="Show items starting with Y" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Y\');switchMessage(1)">Y</A> 
<A title="Show items starting with Z" onMousedown="switchMessage(2)" href="javascript:myfilter.set(\'^Z\');switchMessage(1)">Z</A> 
</TD>
<td></td>
<td></td>
</tr>
<tr>
<TD>
<SELECT multiple size=16 name="SelectList" ID="SelectList"> ';

    // Display list of users
    $sql="SELECT user_id, user_name, realname FROM user WHERE status='A' ORDER BY user_name";
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
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
<TD align="center"><INPUT TYPE="BUTTON" VALUE="  ->  " ONCLICK="addIt();"></INPUT><BR>
<INPUT TYPE="BUTTON" VALUE="  <-  " ONCLICK="delIt();"></INPUT>
</TD>
<TD>
<SELECT NAME="PickList[]" ID="PickList" SIZE="16" multiple>
<OPTION VALUE="01sel">Selection 01 - please ignore</OPTION>
</SELECT>
</TD>
</TR>
<TR>
<TD ALIGN="middle">
<p class=t1 id=textone><b>&nbsp;Please wait...</b></p>

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

<P>Filter: 
<INPUT onkeydown="switchMessage(2)" onkeyup="myfilter.set(this.value);switchMessage(1)" name=regexp>
<INPUT onMousedown="switchMessage(2)" onclick="myfilter.reset();this.form.regexp.value=\'\';switchMessage(1)" type=button value="Reset"> 
</TD>
<TD></TD>
<TD ALIGN="left">
<p>&nbsp;<p>
<INPUT TYPE="submit" VALUE="Submit">
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
<hr><p><b>Current permissions associated to this group:</b>
<p>';
        
        $title_arr=array();
        $title_arr[]='Permission';
        $title_arr[]='Name';
        echo html_build_list_table_top($title_arr,false,false,false);
        $row_num=0;
        
        while ($row = db_fetch_array($res)) {
            echo '<TR class="'. util_get_alt_row_color($row_num) .'">';
            if ($row['permission_type'] == 'PACKAGE_READ') {
                echo '<TD>Package</TD>';
                echo '<TD><a href="/file/admin/editpackagepermissions.php?package_id='.$row['object_id'].'&group_id='.$group_id.'">'.file_get_package_name_from_id($row['object_id']).'</a></TD>';
            } else if ($row['permission_type'] == 'RELEASE_READ') {
                echo '<TD>Release</TD>';
                $package_id=file_get_package_id_from_release_id($row['object_id']);
                echo '<TD><a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$group_id.'&package_id='.$package_id.'">'.file_get_release_name_from_id($row['object_id']).'</a> (<a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id.'">'.file_get_package_name_from_id($package_id).'</a>)</TD>';
            } else {
                echo '<TD>'.$row['permission_type'].'</TD>
              <TD>'.$row['object_id'].'</TD>';
            }

            echo '</TR>';
            $row_num++;
        }
        echo '</table><p>';
    }


}


$HTML->footer(array());



?>
