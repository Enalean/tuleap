<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Project/Task Manager
	By Tim Perdue, Sourceforge, 11/99
	Heavy rewrite by Tim Perdue April 2000

*/

function pm_header($params) {
	global $group_id,$is_pm_page,$words,$group_project_id,$DOCUMENT_ROOT,$order;

	//required by site_project_header
	$params['group']=$group_id;
	$params['toptab']='pm';

	//only projects can use the bug tracker, and only if they have it turned on
	$project=project_get_object($group_id);

	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Task Manager');
	}
	if (!$project->usesPm()) {
		exit_error('Error','This Project Has Turned Off The Task Manager');
	}

	site_project_header($params);

	echo "<P><B>";

	if (isset($group_project_id)) {
		echo "<A HREF=\"/pm/?group_id=$group_id\">Subproject List</A>";
		if (user_isloggedin()) {
			if ($group_project_id) {
				// No Add if a no subproject specified
				echo " | <A HREF=\"/pm/task.php?group_id=$group_id&group_project_id=$group_project_id&func=addtask\">Add Task</A>";
			}
			echo " | <A HREF=\"/pm/task.php?group_id=$group_id&group_project_id=$group_project_id&func=browse&set=my\">My Tasks</A>";
		}
		echo " | <A HREF=\"/pm/task.php?group_id=$group_id&group_project_id=$group_project_id&func=browse&set=open\">Browse Open Tasks</A> | ";
	}
	echo " <A HREF=\"/pm/admin/?group_id=$group_id\">Admin</A></B>";
	echo ' <hr width="300" size="1" align="left" noshade>';

}

function pm_footer($params) {
	site_project_footer($params);
}

function pm_status_box($name='status_id',$checked='xyxy',$none=true,$text_none='None',$any=false,$text_any='Any') {
	$result=pm_data_get_statuses();
	return html_build_select_box($result,$name,$checked,$none,$text_none,$any,$text_any);
}

function pm_tech_select_box($name='assigned_to',$group_id=false,$checked='xzxz') {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=pm_data_get_technicians ($group_id);
		return html_build_select_box($result,$name,$checked);
	}
}

function pm_multiple_task_depend_box ($name='dependent_on[]',$group_id=false,$group_project_id=false,$project_task_id=false) {
	if (!$group_id) {
		return 'ERROR - no group_id specified';
	} else {
		if ($project_task_id) {
			$result=pm_data_get_other_tasks ($group_id,$group_project_id,$project_task_id);
			//get the data so we can mark items as SELECTED
			$result2=pm_data_get_dependent_tasks ($project_task_id);
			return html_build_multiple_select_box ($result,$name,util_result_column_to_array($result2));
		} else {
		    $result=pm_data_get_tasks ($group_id,$group_project_id);
			return html_build_multiple_select_box ($result,$name,array());
		}
	}
}

function pm_show_subprojects_box($name='group_project_id',$group_id=false,$group_project_id=false) {
	if (!$group_id || !$group_project_id) {
		return 'ERROR - no group_id defined';
	} else {
		$result=pm_data_get_subprojects($group_id);
		return html_build_select_box($result,$name,$group_project_id,false);
	}       
}

function pm_multiple_assigned_box ($name='assigned_to[]',$group_id=false,$project_task_id=false) {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=pm_data_get_technicians ($group_id);
		if ($project_task_id) {
			//get the data so we can mark items as SELECTED
			$result2=pm_data_get_assigned_to ($project_task_id);
			return html_build_multiple_select_box ($result,$name,util_result_column_to_array($result2));
		} else {
			return html_build_multiple_select_box ($result,$name,array());
		}
	}
}

function pm_show_percent_complete_box($name='percent_complete',$selected=0) {
	echo '
		<select name="'.$name.'">';
	echo '
		<option value="0">Not Started';
	for ($i=5; $i<101; $i+=5) {
		echo '
			<option value="'.$i.'"';
		if ($i==$selected) {
			echo ' SELECTED';
		}	
		echo '>'.$i.'%';
	}
	echo '
		</select>';
}

function pm_show_month_box($name,$select_month=0) {

	echo '
		<select name="'.$name.'" size="1">';
	$monthlist = array('1'=>'January',
			'2'=>'February',
			'3'=>'March',
			'4'=>'April',
			'5'=>'May',
			'6'=>'June',
			'7'=>'July',
			'8'=>'August',
			'9'=>'September',
			'10'=>'October',
			'11'=>'November',
			'12'=>'December');

	for ($i=1; $i<=count($monthlist); $i++) {
		if ($i == $select_month) {
			echo '
				<option selected value="'.$i.'">'.$monthlist[$i];
		} else {
			echo '
				<option value="'.$i.'">'.$monthlist[$i];
		}
	}
	echo '
		</SELECT>';

}

function pm_show_day_box($name,$day=1) {

	echo '
		<select name="'.$name.'" size="1">';
	for ($i=1; $i<=31; $i++) {
		if ($i == $day) {
			echo '
				<option selected value="'.$i.'">'.$i;
		} else {
			echo '
				<option value="'.$i.'">'.$i;
		}
	}
	echo '
		</select>';

}

function pm_show_year_box($name,$year=1) {

	echo '
		<select name="'.$name.'" size="1">';
	for ($i=1999; $i<=2013; $i++) {
		if ($i == $year) {
			echo '
				<option selected value="'.$i.'">'.$i;
		} else {
			echo '
				<option value="'.$i.'">'.$i;
		}
	}
	echo '
		</select>';

}

function pm_show_tasklist ($result,$result_taskdeps,$offset,$set='open') {
	global $sys_datefmt,$group_id,$group_project_id,$_status,$PHP_SELF;
	/*
		Accepts a result set from the bugs table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/

	$rows=db_numrows($result);

	/*
	   A task can be assigned to several users and they most all show on
	   the same line. Unfortunately MySQL cannot concat user names
	   from different rows on a GROUP BY clause hence this piece of code

	   Remark: the maximum nuber of rows that normally display on one
	   page is 50. But because of the possible line merge it might
	   actually be less than that. There is not much we can do to fix
	   this.
	*/

	$all_rows = array();
	while ( $a_row = db_fetch_array($result)) {

		$tid = $a_row['project_task_id'];

		if ( isset($all_rows[$tid]) ) {
			// if this task id entry already exists then
			// it means there is an additional "assigned to" user
			$all_rows[$tid]['user_name'] .= ','.$a_row['user_name'];	
		} else {
			$all_rows[$tid] = $a_row;
		}
	}

	/* Add the list of task dependencies in the form of 
	   comma separated list, each task being an hyperlink */
	while ( $a_row = db_fetch_array($result_taskdeps)) {

	    // if the task is not in the result set then we must add it
	    // in the selected task list
	    $tid = $a_row['project_task_id'];
	    if ( !isset($all_rows[$tid]) )
		break;

	    $task_url = '<a href="'.$PHP_SELF.'?func=detailtask&project_task_id='.
		$a_row['is_dependent_on_task_id'].'&group_id='.$group_id.
		'&group_project_id='.$a_row['group_project_id'].'" target="_blank">'.
		$a_row['is_dependent_on_task_id'].'</a>';

	    if ( isset($all_rows[$tid]['task_deps']) ) {
		// if there is already an entry it means it's
		// an additional task in the task dependency list
		$all_rows[$tid]['task_deps'] .= ','.$task_url;
	    } else {
		$all_rows[$tid]['task_deps'] = $task_url;		
	    }
	}

	$url = "/pm/task.php?group_id=$group_id&group_project_id=$group_project_id&func=browse&set=$set&order=";

	$title_arr=array();
	$title_arr[]='Task ID';
	$title_arr[]='Summary';
	$title_arr[]='Subproject';
	$title_arr[]='Start Date';
	$title_arr[]='End Date';
	$title_arr[]='Assigned To';
	$title_arr[]='% Complete';
	$title_arr[]='Depend On';
	$title_arr[]='Status';

	$links_arr=array();
	$links_arr[]=$url.'project_task_id';
	$links_arr[]=$url.'summary';
	$links_arr[]=$url.'project_name';
	$links_arr[]=$url.'start_date';
	$links_arr[]=$url.'end_date';
	$links_arr[]=$url.'user_name';
	$links_arr[]=$url.'percent_complete';
	$links_arr[]='#'; /* no sort on task deps */
	$links_arr[]=$url.'status_name';

	echo html_build_list_table_top ($title_arr,$links_arr);

	$now=time();

	reset($all_rows);
	while (list($k,$row) = each($all_rows)) {

		echo '
			<TR BGCOLOR="'.get_priority_color($row['priority']).'">'.
			'<TD><A HREF="'.$PHP_SELF.'?func=detailtask'.
			'&project_task_id='.$row['project_task_id'].
			'&group_id='.$group_id.
			'&group_project_id='.$row['group_project_id'].'">'.
			$row['project_task_id'].'</A></TD>'.
			'<TD>'.$row['summary'].'</TD>'.
			'<TD>'.$row['project_name'].'</TD>'.
			'<TD>'.date('Y-m-d',$row['start_date']).'</TD>'.
			'<TD>'. (($now>$row['end_date'])?'<B>* ':'&nbsp; ') . date('Y-m-d',$row['end_date']).'</TD>'.
			'<TD>'.$row['user_name'].'</TD>'.
			'<TD>'.$row['percent_complete'].'%</TD>'.
			'<TD>&nbsp;'.$row['task_deps'].'</TD>'.
		        '<TD>'.$row['status_name'].'</TD>'.
		        '</TR>';

	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '<TR><TD COLSPAN="2">';
	if ($offset > 0) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_project_id='.
			$group_project_id.'&set='.$set.'&group_id='.$group_id.'&offset='.($offset-50).'">
			<B><-- Previous 50</B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD><TD>&nbsp;</TD><TD COLSPAN="2">';
	
	if ($rows==50) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_project_id='.
			$group_project_id.'&set='.$set.'&group_id='.$group_id.'&offset='.($offset+50).
			'"><B>Next 50 --></B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD></TR></TABLE>';
}

function pm_show_dependent_tasks ($project_task_id,$group_id,$group_project_id) {
	$sql="SELECT project_task.project_task_id,project_task.summary ".
		"FROM project_task,project_dependencies ".
		"WHERE project_task.project_task_id=project_dependencies.project_task_id ".
		"AND project_dependencies.is_dependent_on_task_id='$project_task_id'";
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > 0) {
		echo '
		<H3>Tasks That Depend on This Task</H3>
		<P>';

		$title_arr=array();
		$title_arr[]='Task ID';
		$title_arr[]='Summary';

		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color ($i) .'">
				<TD><A HREF="/pm/task.php?func=detailtask&project_task_id='.
				db_result($result, $i, 'project_task_id').
				'&group_id='.$group_id.
				'&group_project_id='.$group_project_id.'">'.
				db_result($result, $i, 'project_task_id').'</TD>
				<TD>'.db_result($result, $i, 'summary').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Tasks are Dependent on This Task</H3>';
		echo db_error();
	}
}

function pm_show_dependent_bugs ($project_task_id,$group_id,$group_project_id) {
	$sql="SELECT bug.bug_id,bug.summary ".
		"FROM bug,bug_task_dependencies ".
		"WHERE bug.bug_id=bug_task_dependencies.bug_id ".
		"AND bug_task_dependencies.is_dependent_on_task_id='$project_task_id'";
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > 0) {
		echo '
		<H3>Bugs That Depend on This Task</H3>
		<P>';
		$title_arr=array();
		$title_arr[]='Bug ID';
		$title_arr[]='Summary';
		
		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color ($i) .'">
				<TD><A HREF="/bugs/?func=detailbug&bug_id='.
				db_result($result, $i, 'bug_id').
				'&group_id='.$group_id.'">'.db_result($result, $i, 'bug_id').'</A></TD>
				<TD>'.db_result($result, $i, 'summary').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Bugs are Dependent on This Task</H3>';
		echo db_error();
	}
}


function pm_show_task_details ($project_task_id) {
	/*
		Show the details rows from task_history
	*/
	global $sys_datefmt;
	$sql="SELECT project_history.field_name,project_history.old_value,project_history.date,user.user_name ".
		"FROM project_history,user ".
		"WHERE project_history.mod_by=user.user_id AND project_history.field_name = 'details' ".
		"AND project_task_id='$project_task_id' ORDER BY project_history.date DESC";
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > 0) {
		echo '
		<H3>Followups</H3>
		<P>';

		$title_arr=array();
		$title_arr[]='Comment';
		$title_arr[]='Date';
		$title_arr[]='By';
		
		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color ($i) .'">
				<TD>'. nl2br(db_result($result, $i, 'old_value')).'</TD>
				<TD VALIGN="TOP">'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>
				<TD VALIGN="TOP">'.db_result($result, $i, 'user_name').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Comments Have Been Added</H3>';
	}
	
}

function pm_show_task_history ($project_task_id) {
	/*
		show the project_history rows that are 
		relevant to this project_task_id, excluding details
	*/
	global $sys_datefmt;
	$sql="select project_history.field_name,project_history.old_value,project_history.date,user.user_name ".
		"FROM project_history,user ".
		"WHERE project_history.mod_by=user.user_id AND ".
		"project_history.field_name <> 'details' AND project_task_id='$project_task_id' ORDER BY project_history.date DESC";
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > 0) {

		echo '
		<H3>Task Change History</H3>
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
				<TR BGCOLOR="'. util_get_alt_row_color ($i) .'"><TD>'.$field.'</TD><TD>';

			if ($field == 'status_id') {

				echo pm_data_get_status_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'start_date') {

				echo date('Y-m-d',db_result($result, $i, 'old_value'));

			} else if ($field == 'end_date') {

				echo date('Y-m-d',db_result($result, $i, 'old_value'));

			} else {

				echo db_result($result, $i, 'old_value');

			}
			echo '</TD>
				<TD>'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>
				<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
		}

		echo '
			</TABLE>';
	
	} else {
		echo '
			<H3>No Changes Have Been Made</H3>';
	}
}

?>
