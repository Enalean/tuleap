<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('vars.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// If this was a submission, make updates

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

if ($Update) {

    group_add_history ('Changed Public Info','',$group_id);
    
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
        $feedback .= ' UPDATE FAILED OR NO DATA CHANGED! '.db_error();
    } else {
        $feedback .= ' UPDATE SUCCESSFUL ';
    }
}

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

project_admin_header(array('title'=>'Editing Group Info','group'=>$group_id,
			   'help' => 'ProjectPublicInformation.html'));

print '<P><h3>Editing group info for: <B>'.$row_grp['group_name'].'</B></h3>';

print '
<P>
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">

<P>Descriptive Group Name:
<BR><INPUT type="text" size="40" maxlen="40" name="form_group_name" value="'.$row_grp['group_name'].'">

<P>Short Description (255 Character Max):
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'.$row_grp['short_description'].'</TEXTAREA>

<P>Long Description:
<BR><TEXTAREA cols="70" rows="10" wrap="virtual" name="form_purpose">
'.$row_grp['register_purpose'].'</TEXTAREA>

<P>Patents and Invention Proposals (IPs)
<BR><TEXTAREA cols="70" rows="6" wrap="virtual" name="form_patents">
'.$row_grp['patents_ips'].'</TEXTAREA>

<P>Other Software Required
<BR><TEXTAREA cols="70" rows="6"wrap="virtual" name="form_required_sw">
'.$row_grp['required_software'].'</TEXTAREA>

<P>Other Comments:<BR>
<TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">'.$row_grp['other_comments'].'</TEXTAREA>
<HR>

<P><INPUT type="submit" name="Update" value="Update">
</FORM>
';

project_admin_footer(array());

?>
