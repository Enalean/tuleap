<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('project/project');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// If this was a submission, make updates

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

if (isset($Update)) {

    
    // in the database, these all default to '1', 
    // so we have to explicity set 0
    
    $sql = 'UPDATE groups SET '
        ."group_name='".htmlspecialchars($form_group_name)."',"
        ."short_description='$form_shortdesc',"
        ."register_purpose='".htmlspecialchars($form_purpose)."', "
        ."required_software='".htmlspecialchars($form_required_sw)."', "
        ."patents_ips='".htmlspecialchars($form_patents)."',  "
        ."other_comments='".htmlspecialchars($form_comments)."', "
        ."hide_members='$hide_members'";
		
    $sql .= " WHERE group_id=$group_id";

    //echo $sql;
    $result=db_query($sql);
    if (!$result || db_affected_rows($result) < 1) {
        $feedback .= ' '.$Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' ));
    } else {
        $feedback .= ' '.$Language->getText('project_admin_editgroupinfo','upd_success').' ';
	group_add_history('changed_public_info','',$group_id);
    }
}

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

project_admin_header(array('title'=>$Language->getText('project_admin_editgroupinfo','editing_g_info'),'group'=>$group_id,
			   'help' => 'ProjectPublicInformation.html'));

print '<P><h3>'.$Language->getText('project_admin_editgroupinfo','editing_g_info_for',$row_grp['group_name']).'</h3>';

print '
<P>
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">

<P>'.$Language->getText('project_admin_editgroupinfo','descriptive_g_name').'
<BR><INPUT type="text" size="40" maxlen="40" name="form_group_name" value="'.$row_grp['group_name'].'">

<P>'.$Language->getText('project_admin_editgroupinfo','short_desc').'
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'.$row_grp['short_description'].'</TEXTAREA>

<P>'.$Language->getText('project_admin_editgroupinfo','long_desc').'
<BR><TEXTAREA cols="70" rows="10" wrap="virtual" name="form_purpose">
'.$row_grp['register_purpose'].'</TEXTAREA>

<P>'.$Language->getText('project_admin_editgroupinfo','patents').'
<BR><TEXTAREA cols="70" rows="6" wrap="virtual" name="form_patents">
'.$row_grp['patents_ips'].'</TEXTAREA>

<P>'.$Language->getText('project_admin_editgroupinfo','soft_required').'
<BR><TEXTAREA cols="70" rows="6"wrap="virtual" name="form_required_sw">
'.$row_grp['required_software'].'</TEXTAREA>

<P>'.$Language->getText('project_admin_editgroupinfo','comments').'<BR>
<TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">'.$row_grp['other_comments'].'</TEXTAREA>

<P>'.$Language->getText('project_admin_editgroupinfo','hide_members').'
<INPUT TYPE="CHECKBOX" NAME="hide_members" VALUE="1"'.(($row_grp['hide_members']==1) ? ' CHECKED' : '' ).'><BR> 	 
<HR>

<P><INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
</FORM>
';

project_admin_footer(array());

?>
