<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$LANG->loadLanguageMsg('project/project');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// If this was a submission, make updates

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

if ($Update) {

    
    // in the database, these all default to '1', 
    // so we have to explicity set 0
    
    $sql = 'UPDATE groups SET '
        ."group_name='$form_group_name',"
        ."short_description='$form_shortdesc',"
        ."register_purpose='".htmlspecialchars($form_purpose)."', "
        ."required_software='".htmlspecialchars($form_required_sw)."', "
        ."patents_ips='".htmlspecialchars($form_patents)."',  "
        ."other_comments='".htmlspecialchars($form_comments)."'";
		
    $sql .= " WHERE group_id=$group_id";

    //echo $sql;
    $result=db_query($sql);
    if (!$result || db_affected_rows($result) < 1) {
        $feedback .= ' '.$LANG->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' ));
    } else {
        $feedback .= ' '.$LANG->getText('project_admin_editgroupinfo','upd_success').' ';
	group_add_history ($LANG->getText('project_admin_editgroupinfo','changed_public_info'),'',$group_id);
    }
}

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

project_admin_header(array('title'=>$LANG->getText('project_admin_editgroupinfo','editing_g_info'),'group'=>$group_id,
			   'help' => 'ProjectPublicInformation.html'));

print '<P><h3>'.$LANG->getText('project_admin_editgroupinfo','editing_g_info_for',$row_grp['group_name']).'</h3>';

print '
<P>
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">

<P>'.$LANG->getText('project_admin_editgroupinfo','descriptive_g_name').'
<BR><INPUT type="text" size="40" maxlen="40" name="form_group_name" value="'.$row_grp['group_name'].'">

<P>'.$LANG->getText('project_admin_editgroupinfo','short_desc').'
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'.$row_grp['short_description'].'</TEXTAREA>

<P>'.$LANG->getText('project_admin_editgroupinfo','long_desc').'
<BR><TEXTAREA cols="70" rows="10" wrap="virtual" name="form_purpose">
'.$row_grp['register_purpose'].'</TEXTAREA>

<P>'.$LANG->getText('project_admin_editgroupinfo','patents').'
<BR><TEXTAREA cols="70" rows="6" wrap="virtual" name="form_patents">
'.$row_grp['patents_ips'].'</TEXTAREA>

<P>'.$LANG->getText('project_admin_editgroupinfo','soft_required').'
<BR><TEXTAREA cols="70" rows="6"wrap="virtual" name="form_required_sw">
'.$row_grp['required_software'].'</TEXTAREA>

<P>'.$LANG->getText('project_admin_editgroupinfo','comments').'<BR>
<TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">'.$row_grp['other_comments'].'</TEXTAREA>
<HR>

<P><INPUT type="submit" name="Update" value="'.$LANG->getText('global','btn_update').'">
</FORM>
';

project_admin_footer(array());

?>
