<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Standard header to be used on all /project/admin/* pages

*/

function project_admin_header($params) {
	global $DOCUMENT_ROOT,$group_id,$feedback;

	$params['toptab']='admin';
	$params['group']=$group_id;
	site_project_header($params);

	echo '
	<P><B>
	<A HREF="/project/admin/?group_id='.$group_id.'">Admin</A> | 
	<A HREF="/project/admin/userperms.php?group_id='.$group_id.'">User Permissions</A> | 
	<A HREF="/project/admin/editgroupinfo.php?group_id='.$group_id.'">Edit Public Info</A> |
	<A HREF="/project/admin/history.php?group_id='.$group_id.'">Project History</A> |
	<A HREF="/project/export/index.php?group_id='.$group_id.'">Project Data Export</A>
	<BR>
	<A HREF="/project/admin/servicebar.php?group_id='.$group_id.'">Service Configuration</A> |
	<A HREF="/project/admin/ugroup.php?group_id='.$group_id.'">User Groups Admin</A> |
        <A HREF="/project/stats/source_code_access.php/?group_id='.$group_id.'">Source Code Access Logs</A>';

	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo '</B>
	<P>';
}

/*

	Standard footer to be used on all /project/admin/* pages

*/

function project_admin_footer($params) {
	site_project_footer($params);
}



/*

	The following three functions are for group
	audit trail

	When changes like adduser/rmuser/change status
	are made to a group, a row is added to audit trail
	using group_add_history()

*/

function group_get_history ($group_id=false) {
	$sql="select group_history.field_name,group_history.old_value,group_history.date,user.user_name ".
		"FROM group_history,user ".
		"WHERE group_history.mod_by=user.user_id ".
		"AND group_id='$group_id' ORDER BY group_history.date DESC";
	return db_query($sql);
}	       
	
function group_add_history ($field_name,$old_value,$group_id) {
	/*      
		handle the insertion of history for these parameters
	*/
	
	$sql="insert into group_history(group_id,field_name,old_value,mod_by,date) ".
		"VALUES ('$group_id','$field_name','$old_value','". user_getid() ."','".time()."')";
	return db_query($sql);
}	       

/*

	Nicely html-formatted output of this group's audit trail

*/

function show_grouphistory ($group_id) {
	/*      
		show the group_history rows that are relevant to 
		this group_id
	*/
	global $sys_datefmt;
	$result=group_get_history($group_id);
	$rows=db_numrows($result);
	
	if ($rows > 0) {
	
		echo '
		<H3>Group Change History</H3>
		<P>';
		$title_arr=array();
		$title_arr[]='Field';
		$title_arr[]='Old Value';
		$title_arr[]='Date';
		$title_arr[]='By';
		
		echo html_build_list_table_top ($title_arr);
		
		for ($i=0; $i < $rows; $i++) { 
			$field=db_result($result, $i, 'field_name');
			echo '
			<TR class="'. html_get_alt_row_color($i) .'"><TD>'.$field.'</TD><TD>';
			
			if ($field=='removed user') {
				echo user_getname(db_result($result, $i, 'old_value'));
			} else {
				echo db_result($result, $i, 'old_value');
			}			
			echo '</TD>'.
				'<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
		}	       
				
		echo '	 
		</TABLE>';      
		
	} else {
		echo '  
		<H3>No Changes Have Been Made to This Group</H3>';
	}       
}       

?>
