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

// Show/manage ugroup list

require('pre.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require ($DOCUMENT_ROOT.'/include/permissions.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($func=='delete') {
    ugroup_delete($group_id, $ugroup_id);
}

if ($func=='do_update') {
    ugroup_update($_POST['group_id'], $_POST['ugroup_id'], $_POST['ugroup_name'], $_POST['ugroup_description'], $_POST['PickList']);
}



//
// Now display main page
//


project_admin_header(array('title'=>'View User Groups','group'=>$group_id,
			   'help' => 'GroupConfiguration.html'));
$project=project_get_object($group_id);

print '<P><h2>Editing User Groups for <B>'.$project->getPublicName().'</B></h2>';
print '
<P>
<H3>New User Group</H3>
<a href="/project/admin/editugroup.php?func=create&group_id='.$group_id.'">Create a new user group.</a>
<p>


<H3>Manage User Groups:</H3>
<P>
<HR>';

echo '
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';

$title_arr=array();
$title_arr[]='User Group Name';
$title_arr[]='Description';
$title_arr[]='Members';
$title_arr[]='Delete?';
echo html_build_list_table_top($title_arr);
$row_num=0;


$result = db_query("SELECT * FROM ugroup WHERE group_id=100 ORDER BY ugroup_id");
while ($row = db_fetch_array($result)) {
    echo '<TR class="'. util_get_alt_row_color($row_num) .'">
            <TD>'.$row['name'].' *</TD>';
    echo '<TD>'.$row['description'].'</TD>
<TD align="center">-</TD>
<TD align="center">-</TD>
</TR>';
    $row_num++;
}


$result = db_query("SELECT * FROM ugroup WHERE group_id=$group_id ORDER BY name");
if (db_numrows($result) > 0) {
    while ($row = db_fetch_array($result)) {
        echo '<TR class="'. util_get_alt_row_color($row_num) .'">
            <TD>
              <a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=edit">'.$row['name'].'</TD>';
        echo '<TD>'.$row['description'].'</TD>';
        $res2=db_query("SELECT count(*) FROM ugroup_user WHERE ugroup_id=".$row['ugroup_id']);
        $nb_members=db_result($res2,0,0);
        if ($nb_members) echo '<TD align="center">'.$nb_members.'</TD>';
        else echo '<TD align="center">0</TD>';


        echo '<TD align="center"><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=delete" onClick="return confirm(\'Delete this user group ?\')"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>
</TR>';
        $row_num++;
    }
}

echo '</TABLE>';
echo '<P>* denotes predefined groups';


project_admin_footer(array());


 

?>

