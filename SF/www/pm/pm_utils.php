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

/* Generate URL arguments from a variable wether scalar or array */
function pm_convert_to_url_arg($varname, $var) {

    if (is_array($var)) {
	reset($var);
	while (list(,$v) = each($var)) {
	    $ret .= '&'.$varname.'[]='.$v;
	}
    } else {
	$ret .= '&'.$varname.'='.$var;
    }
    return $ret;
}

function pm_header($params) {
	global $group_id,$is_pm_page,$words,$group_project_id,$DOCUMENT_ROOT,$order,$advsrch;

	//Set to 1 so the  search box will add the necessary element to the pop-up box
	$is_pm_page=1;

	//required by site_project_header
	$params['group']=$group_id;
	$params['toptab']='pm';

	//only projects can use the task tracker, and only if they have it turned on
	$project=project_get_object($group_id);

	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Task Manager');
	}
	if (!$project->usesPm()) {
		exit_error('Error','This Project Has Turned Off The Task Manager');
	}

	site_project_header($params);

	echo "<P><B>";

	echo "<A HREF=\"/pm/?group_id=$group_id\">Subproject List</A>";
	if (user_isloggedin()) {
	    
	    // For Add Task there must be only one subproject
	    echo' | <A HREF="/pm/task.php?group_id='.$group_id.
		'&group_project_id='.
		(is_array($group_project_id) ? $group_project_id[0] : $group_project_id).
		'&func=addtask">Add a Task</A>';
	    
	    echo ' | <A HREF="/pm/task.php?group_id='.$group_id.
		pm_convert_to_url_arg('group_project_id',$group_project_id).
		'&func=browse&advsrch='.(isset($advsrch)?$advsrch:0).
		'&set=my">My Tasks</A>';
	}
	
	echo ' | <A HREF="/pm/task.php?group_id='.$group_id.
	    pm_convert_to_url_arg('group_project_id',$group_project_id).
	    '&func=browse&advsrch='.(isset($advsrch)?$advsrch:0)
	    .'&set=open">Open Tasks</A> |';
	
	echo " <A HREF=\"/pm/admin/?group_id=$group_id\">Admin</A></B>";
	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo '</b>';
	echo ' <hr width="300" size="1" align="left" noshade>';

}

function pm_header_admin($params) {
    global $group_id,$is_pm_page,$DOCUMENT_ROOT;

    //used so the search box will add the necessary element to the pop-up box
    $is_pm_page=1;
    
    //required params for site_project_header();
    $params['group']=$group_id;
    $params['toptab']='pm';
    
    $project=project_get_object($group_id);
    
    //only projects can use the task tracker, and only if they have it turned on
    if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use The Task Manager');
    }
    if (!$project->usesPm()) {
	exit_error('Error','This Project Has Turned Off The Task Manager');
    }
    echo site_project_header($params);
    echo '<P><B><A HREF="/pm/admin/?group_id='.$group_id.'">Admin</A></B>';
    echo ' | <B><A HREF="/pm/admin/index.php?projects=1&group_id='.$group_id.'">Add Subproject</A></B>';
    echo ' | <b><A HREF="/pm/admin/index.php?change_status=1&group_id='.$group_id.'">Update Subprojects</A></b>';
    echo ' | <b><A HREF="/pm/admin/notification_settings.php?group_id='.$group_id.'">Notification Settings</A></b>';
    echo ' | <b><A HREF="/pm/admin/personal_settings.php?group_id='.$group_id.'">Personal Settings</A></b>';
    echo ' | <b><A HREF="/pm/admin/other_settings.php?group_id='.$group_id.'">Other Settings</A></b>';
    if ($params['help']) {
	echo ' | <b>'.help_button($params['help'],false,'Help').'</b>';
    }
    echo ' <hr width="685" size="1" align="left" noshade>';
}

function pm_footer($params) {
	site_project_footer($params);
}


function pm_multiple_status_box($name='status[]',$checked,$show_none=true,$text_none='None',$show_any=false,$text_any='Any') {
    $result=pm_data_get_statuses();
    return html_build_multiple_select_box($result,$name,$checked,6,$show_none,$text_none,$show_any,$text_any,false);
  
}

function pm_status_box($name='status_id',$checked='xyxy',$show_none=true,$text_none='None',$show_any=false,$text_any='Any') {
	$result=pm_data_get_statuses();
	return html_build_select_box($result,$name,$checked,$show_none,$text_none,$show_any,$text_any);
}

function pm_multiple_tech_box($name='assigned_to[]',$group_id=false,$checked,$show_none=true,$text_none='None',$show_any=false,$text_any='Any') {
	if (!$group_id) {
		return 'ERROR - no group_id specified';
	} else {
	    $result=pm_data_get_technicians($group_id);
	    return html_build_multiple_select_box($result,$name,$checked,6,$show_none,$text_none, $show_any,$text_any,false);
	}
}

function pm_tech_box($name='assigned_to',$group_id=false,$checked='xzxz',$show_none=true,$text_none='None',$show_any=false,$text_any='Any') {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=pm_data_get_technicians ($group_id);
		return html_build_select_box($result,$name,$checked,$show_none,$text_none,$show_any,$text_any);
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

function pm_multiple_subprojects_box($name='group_project_id[]',$group_id=false,$checked,$show_none=true,$text_none='None',$show_any=false,$text_any='Any') {
	if (!$group_id) {
		return 'ERROR - no group_id specified';
	} else {
	    $result=pm_data_get_subprojects($group_id);
	    return html_build_multiple_select_box($result,$name,$checked,6,$show_none,$text_none,$show_any,$text_any,false);
	}
}

function pm_subprojects_box($name='group_project_id',$group_id=false,$group_project_id=-1,$show_none=false,$text_none='None',$show_any=false,$text_any='Any') {
	if (!$group_id || ($group_project_id == -1) ) {
		return 'ERROR - no group_id or subproject_id defined';
	} else {
		$result=pm_data_get_subprojects($group_id);
		return html_build_select_box($result,$name,$group_project_id,$show_none,$text_none,$show_any,$text_any);
	}
}

function pm_multiple_assigned_box ($name='assigned_to[]',$group_id=false,$project_task_id=false,$checked_array=false) {
        if (!$group_id) {
                return 'ERROR - no group_id';
        } else {
                $result=pm_data_get_technicians ($group_id);
                if ($project_task_id) {
                        //get the data so we can mark items as SELECTED
                        $result2=pm_data_get_assigned_to ($project_task_id);
                        return html_build_multiple_select_box ($result,$name,util_result_column_to_array($result2),6,true,'None',false,'',false);
                } else {
                        if ( !$checked_array ) {
                            return html_build_multiple_select_box ($result,$name,array(),6);
                        } else {
                            return html_build_multiple_select_box ($result,$name,$checked_array,6);
                        }
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

function pm_show_month_box($name,$month=0) {

	echo '
		<select name="'.$name.'" size="1">';
	$monthlist = array('0' => ' ',
			'1'=>'January',
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

	for ($i=0; $i<count($monthlist); $i++) {
		if ($i == $month) {
			echo '
				<option selected value="'.$i.'">'.$monthlist[$i].'</option>';
		} else {
			echo '
				<option value="'.$i.'">'.$monthlist[$i].'</option>';
		}
	}
	echo '
		</SELECT>';

}

function pm_show_day_box($name,$day=0) {

	echo '
		<select name="'.$name.'" size="1">';
	echo '
     	                 <option value="0"'.($day ? '':'selected').'> </option>';
	for ($i=1; $i<=31; $i++) {
		if ($i == $day) {
			echo '
				<option selected value="'.$i.'">'.$i.'</option>';
		} else {
			echo '
				<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '
		</select>';

}

function pm_show_year_box($name,$year=0) {

	echo '
		<select name="'.$name.'" size="1">';
	echo '
     	                 <option value="0"'.($year?'':'selected').'> </option>';
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

function pm_show_tasklist ($result,$result_taskdeps,$offset,$url) {
    echo pm_format_tasklist ($result,$result_taskdeps,$offset,$url,$count);
}

function pm_format_tasklist ($result,$result_taskdeps,$offset,$url,&$count) {
	global $sys_datefmt,$group_id,$group_project_id,$_status,$PHP_SELF;
	/*
		Accepts a result set from the tasks table. Should include all columns from
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
			$all_rows[$tid]['user_name'] .= ','.util_user_link($a_row['user_name']);	
		} else {
			$a_row['user_name'] = util_user_link($a_row['user_name']);
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
		'&group_project_id='.$a_row['dep_task_group_project_id'].'" target="_blank">'.
		$a_row['is_dependent_on_task_id'].'</a>';

	    if ( isset($all_rows[$tid]['task_deps']) ) {
		// if there is already an entry it means it's
		// an additional task in the task dependency list
		$all_rows[$tid]['task_deps'] .= ','.$task_url;
	    } else {
		$all_rows[$tid]['task_deps'] = $task_url;		
	    }
	}

	$title_arr=array();
	$title_arr[]='Task ID';
	$title_arr[]='Summary';
	$title_arr[]='Subproject';
	$title_arr[]='Start Date';
	$title_arr[]='End Date';
	$title_arr[]='Assignee';
	$title_arr[]='Effort';
	$title_arr[]='% Complete';
	$title_arr[]='Depend On';
	$title_arr[]='Status';

	$links_arr=array();
	$links_arr[]=$url.'&order=project_task_id';
	$links_arr[]=$url.'&order=summary';
	$links_arr[]=$url.'&order=project_name';
	$links_arr[]=$url.'&order=start_date';
	$links_arr[]=$url.'&order=end_date';
	$links_arr[]=$url.'&order=user_name';
	$links_arr[]=$url.'&order=hours';
	$links_arr[]=$url.'&order=percent_complete';
	$links_arr[]='#'; /* no sort on task deps */
	$links_arr[]=$url.'&order=status_name';

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	if ($offset > 0) {
		$out .= '<A HREF="'.$url.'&offset='.($offset-50).'">
			<B><<< Previous 50</B></A>';
	}
	if (($offset > 0) && ($rows >= 50)) {
	    $out .= '&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
	}
	if ($rows >= 50) {
		$out .= '<A HREF="'.$url.'&offset='.($offset+50).
			'"><B>Next 50 >>></B></A>';
	}

	$out .= html_build_list_table_top ($title_arr,$links_arr);

	$now=time();
	$count = count($all_rows);

	reset($all_rows);
	while (list($k,$row) = each($all_rows)) {

	        $task_url = $PHP_SELF.'?func=detailtask'.
		    '&project_task_id='.$row['project_task_id'].
		    '&group_id='.$group_id.
		    '&group_project_id='.$row['group_project_id'];

		$out .= '
			<TR class="'.get_priority_color($row['priority']).'">'.
			'<TD class="small"><A HREF="'.$task_url.'">'.
			$row['project_task_id'].'</A></TD>'.
			'<TD class="small"><A HREF="'.$task_url.'">'.
		        $row['summary'].'</A></TD>'.
			'<TD class="small">'.$row['project_name'].'</TD>'.
			'<TD class="small">'.format_date('Y-m-d',$row['start_date']).'</TD>'.
			'<TD class="small">'. (($now>$row['end_date'])?'<B>* ':'&nbsp; ') . format_date('Y-m-d',$row['end_date']).'</TD>'.
			'<TD class="small">'.$row['user_name'].'</TD>'.
			'<TD class="small">'.sprintf("%10.2f",$row['hours']).'</TD>'.
			'<TD class="small">'.($row['percent_complete']-1000).'%</TD>'.
			'<TD class="small">&nbsp;'.$row['task_deps'].'</TD>'.
		        '<TD class="small">'.$row['status_name'].'</TD>'.
		        '</TR>';

	}

	$out .= '</TABLE>';
	return($out);
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
			<TR class="'. util_get_alt_row_color ($i) .'">
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
			<TR class="'. util_get_alt_row_color ($i) .'">
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


function pm_show_task_details ($project_task_id, $group_id) {
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

		$title_arr=array();
		$title_arr[]='Comment';
		$title_arr[]='Date';
		$title_arr[]='By';
		
		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR class="'. util_get_alt_row_color ($i) .'">
				<TD>'. util_make_links(nl2br(db_result($result, $i, 'old_value')), $group_id).'</TD>
				<TD VALIGN="TOP">'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>
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
		show the project_history rows that are relevant to this project_task_id, excluding details
	*/
    global $sys_datefmt;
    $result=pm_data_get_history($project_task_id);
    $rows=db_numrows($result);

    if ($rows > 0) {

        $title_arr=array();
        $title_arr[]='Field';
        $title_arr[]='Old Value';
        $title_arr[]='Date';
        $title_arr[]='By';
        
        echo html_build_list_table_top ($title_arr);
        
        for ($i=0; $i < $rows; $i++) {
            $field = db_result($result, $i, 'field_name');
            $value_id =  db_result($result, $i, 'old_value');
        
            echo "\n".'<TR class="'. util_get_alt_row_color($i) .
        	'"><TD>'.pm_data_get_label($field).'</TD><TD>';
        
            if (pm_data_is_select_box($field)) {
        		// It's a select box look for value in clear
        		echo pm_data_get_value($field, $group_id, $value_id);
            } else if (pm_data_is_date_field($field)) {
        		// For date fields do some special processing
        		echo format_date($sys_datefmt,$value_id);
            } else {
        		// It's a text zone then display directly
        		echo $value_id;
            }
        
            echo '</TD>'.
        	'<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
        	'<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
        }
        echo '</TABLE>';
    
    } else {
        echo "\n".'<H4>No Changes Have Been Made to This Task</H4>';
    }
}

/* 
   The ANY value is 0. The simple fact that
   ANY (0) is one of the value means it is Any even if there are
   other non zero values in the  array
*/
function pm_isvarany($var) {
    if (is_array($var)) {
	reset($var);
	while (list(,$v) = each($var)) {
	    if ($v == 0) { return true; }
	}
	return false;
    } else {
	return ($var == 0);
    }

}

function pm_exist_cc($project_task_id,$cc) {
    $sql = "SELECT project_cc_id FROM project_cc WHERE project_task_id='$project_task_id' AND email='$cc'";
    $res = db_query($sql);
    return (db_numrows($res) >= 1);
}

function pm_insert_cc($project_task_id,$cc,$added_by,$comment,$date) {
    $sql = "INSERT INTO project_cc (project_task_id,email,added_by,comment,date) ".
	"VALUES ('$project_task_id','$cc','$added_by','$comment','$date')";
    $res = db_query($sql);
    return ($res);

}

function pm_add_cc($project_task_id,$group_id,$email,$comment,&$changes) {
    global $feedback;

    $user_id = (user_isloggedin() ? user_getid(): 100);

    $arr_email = util_split_emails($email);
    $date = time();
    $ok = true;
    $changed = false;
    while (list(,$cc) = each($arr_email)) {
    	// Add this cc only if not there already
    	if (!pm_exist_cc($project_task_id,$cc)) {
    	    $changed = true;
    	    $res = pm_insert_cc($project_task_id,$cc,$user_id,$comment,$date);
    	    if (!$res) { $ok = false; }
    	}
    }

    if (!$ok) {
    	$feedback .= ' - CC addition failed';
    } else {
	    $feedback .= '- CC Added';
	    $changes['CC']['add'] = join(',', $arr_email);
    }
    return $ok;
}

function pm_delete_cc($group_id=false,$project_task_id=false,$project_cc_id=false,&$changes) {
    global $feedback;

    // If both project_task_id and project_cc_id are given make sure the cc belongs 
    // to this task (it's a bit paranoid but...)
    if ($project_task_id) {
	$res1 = db_query("SELECT project_task_id,email from project_cc WHERE project_cc_id='$project_cc_id'");
	if ((db_numrows($res1) <= 0) || (db_result($res1,0,'project_task_id') != $project_task_id) ) {
	    $feedback .= " - Error CC ID $project_cc_id doesn't belong to task ID";
	    return false;
	}
    }

    // Now delete the CC address
    $res2 = db_query("DELETE FROM project_cc WHERE project_cc_id='$project_cc_id'");
    if (!$res2) {
	    $feedback .= " - Error deleting CC ID $project_cc_id: ".db_error($res2);
	    return false;
    } else {
       	$feedback .= " - CC Removed";
    	$changes['CC']['del'] = db_result($res1,0,'email');
    	return true;
    }
}

function show_task_cc_list ($project_task_id,$group_id, $ascii=false) {
    echo format_task_cc_list ($project_task_id,$group_id, $ascii);
}

function format_task_cc_list ($project_task_id,$group_id, $ascii=false) {

    global $sys_datefmt;

    /*
          show the files attached to this task
       */

    $result=pm_data_get_cc_list($project_task_id);
    $rows=db_numrows($result);

    // No file attached -> return now
    if ($rows <= 0) {
	if ($ascii)
	    $out = "CC list is empty\n";
	else
	    $out = '<H4>CC list is empty</H4>';
	return $out;
    }

    // Header first an determine what the print out format is
    // based on output type (Ascii, HTML)
    if ($ascii) {
	$out .= "CC List\n*******\n\n";
	$fmt = "%-35s | %s\n";
	$out .= sprintf($fmt, 'CC Address', 'Comment');
	$out .= "------------------------------------------------------------------\n";
    } else {	

	$title_arr=array();
	$title_arr[]='CC Address';
	$title_arr[]='Comment';
	$title_arr[]='Added by';
	$title_arr[]='On';
	$title_arr[]='Delete?';
	$out .= html_build_list_table_top ($title_arr);

	$fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
	    '<td align="center">%s</td><td align="center">%s</td></tr>';
    }

    // Loop through the cc and format them
    for ($i=0; $i < $rows; $i++) {

	$email = db_result($result, $i, 'email');
	$project_cc_id = db_result($result, $i, 'project_cc_id');

	// if the CC is a user point to its user page else build a mailto: URL
	$res_username = user_get_result_set_from_unix($email);
	if ($res_username && (db_numrows($res_username) == 1))
	    $href_cc = util_user_link($email);
	else
	    $href_cc = '<a href="mailto:'.util_normalize_email($email).'">'.$email.'</a>';

	if ($ascii) {
	    $out .= sprintf($fmt, $email, db_result($result, $i, 'comment'));
	} else {

	    // show CC delete icon if one of the condition is met:
	    // a) current user is a task admin
	    // b) then CC name is the current user 
	    // c) the CC email address matches the one of the current user
	    // d) the current user is the person who added a gieven name in CC list
	    if ( user_ismember($group_id,'B2') ||
		(user_getname(user_getid()) == $email) ||  
		(user_getemail(user_getid()) == $email) ||
		(user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
		$html_delete = "<a href=\"$PHP_SELF?func=delete_cc&group_id=$group_id&project_task_id=$project_task_id&project_cc_id=$project_cc_id\" ".
		'" onClick="return confirm(\'Delete this CC address?\')">'.
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A>';
	    } else {
		$html_delete = '-';
	    }

	    $out .= sprintf($fmt,
			    util_get_alt_row_color($i),
			    $href_cc,
			    db_result($result, $i, 'comment'),
			    util_user_link(db_result($result, $i, 'user_name')),
			    format_date($sys_datefmt,db_result($result, $i, 'date')),
			    $html_delete);
	}
    }

    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");

    return($out);

}

function pm_extract_field_list($post_method=true) {

    global $HTTP_GET_VARS, $HTTP_POST_VARS, $TF_USAGE_BY_NAME;
    /* 
       Returns the list of field names in the HTML Form corresponding to a
       field used by this project
       */
    $vfl = array();
    if ($post_method) {
    	reset($HTTP_POST_VARS);
    	while ( list($key, $val) = each($HTTP_POST_VARS)) {
    	    if (isset($TF_USAGE_BY_NAME[$key])) {
        		$vfl[$key] = $val;
        		//echo "Accepted key = ".$key." val = $val<BR>";
    	    } else {
        		//echo "Rejected key = ".$key." val = $val<BR>";
    	    }
    	}
    } else {
    	reset($HTTP_GET_VARS);
    	while ( list($key, $val) = each($HTTP_GET_VARS)) {
    	    if (isset($TF_USAGE_BY_NAME[$key])) {
        		$vfl[$key] = $val;
        		//echo "Accepted key = ".$key." val = $val<BR>";
    	    } else {
        		//echo "Rejected key = ".$key." val = $val<BR>";
    	    }
    	}

    }
    return($vfl);
}

function pm_init($group_id) {
    // Set the global arrays for faster processing at init time
    pm_data_get_all_fields($group_id, true);
}

function pm_check_empty_fields($field_array) {

    /*
      Check whether empty values are allowed for the task fields
      Params:
      field_array: associative array of field_name -> value
      */
    global $feedback;

    $bad_fields = array();
    reset($field_array);
    while ( list($key, $val) = each($field_array)) {
	    $is_empty = (pm_data_is_select_box($key) ? ($val==100) : ($val==''));
    	if ( $is_empty && !pm_data_is_empty_ok($key)) {
    	    $bad_fields[] = pm_data_get_label($key);
    	}
    }

    if (count($bad_fields) > 0) {
	    $feedback = 'Missing fields: '.join(', ',$bad_fields).
	    '<p>Empty values for the above listed field(s) are not allowed. Click on the '.
	    'Back arrow of your browser and try again';
	    return false;
    } else {
	    return true;
    }

}

function pm_field_label_display($field_name, $group_id,$break=false,$ascii=false) {
    $output = pm_data_get_label($field_name).': ';
    if (!$ascii) 
	    $output = '<B>'.$output.'</B>';
    if ($break) 
	    $output .= ($ascii?"\n":'<BR>');
    else
	    $output .= ($ascii? ' ':'&nbsp;');
    return $output;
}

function pm_field_box($field_name,$box_name='',$group_id,$checked=false,$show_none=false,$text_none='None',$show_any=false, $text_any='Any') {

    /*
      Returns a select box populated with field values for this project
      if box_name is given then impose this name in the select box
      of the  HTML form otherwise use the field_name)
    */
    if (!$group_id) {
	    return 'ERROR - no group_id';
    } else {
	    $result = pm_data_get_field_predefined_values($field_name,$group_id,$checked);

    	if ($box_name == '') {
    	    $box_name = $field_name;
    	}
    	return html_build_select_box ($result,$box_name,$checked,$show_none,$text_none,$show_any, $text_any);
    }
}

function pm_multiple_field_box($field_name,$box_name='',$group_id,$checked=false,$show_none=false,$text_none='None',$show_any=false, $text_any='Any',$show_value=false) {

    /*
      Returns a multiplt select box populated with field values for this project
      if box_name is given then impose this name in the select box
      of the  HTML form otherwise use the field_name)
    */
    if (!$group_id) {
    	return 'ERROR - no group_id';
    } else {
    	$result = pm_data_get_field_predefined_values($field_name,$group_id,$checked);
    
    	if ($box_name == '') {
    	    $box_name = $field_name.'[]';
    	}
    	return html_build_multiple_select_box($result,$box_name,$checked,6,$show_none,$text_none, $show_any,$text_any,$show_value);
    }
}

function pm_field_text($field_name,$value='',$size=0,$maxlength=0) {

    if (!$size || !$maxlength)
	list($size, $maxlength) = pm_data_get_display_size($field_name);

    $html = '<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$value.'">';
    return($html);

}

function pm_field_textarea($field_name,$value='',$cols=0,$rows=0) {

    if (!$cols || !$rows)
	list($cols, $rows) = pm_data_get_display_size($field_name);

    $html = '<TEXTAREA NAME="'.$field_name.
	'" ROWS="'.$rows.'" COLS="'.$cols.'" WRAP="SOFT">'.$value.'</TEXTAREA>';
    return($html);

}

function pm_field_date($field_name,$value='',$size=0,$maxlength=0,$ro=false) {

    // CAUTION!!!! The Javascript below assumes that the date always appear
    // in a form called 'task_form'
    if ($ro)
	$html = $value;
    else {
	if (!$size || !$maxlength)
	    list($size, $maxlength) = pm_data_get_display_size($field_name);

	$html = '<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$value.'">'.
	'<a href="javascript:show_calendar(\'document.task_form.'.$field_name.'\', document.task_form.'.$field_name.'.value,\''.$GLOBALS['sys_user_theme'].'\',\''.getFontsizeName($GLOBALS['sys_user_font_size']).'\');">'.
	'<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="Click Here to Pick up a date"></a>';
    }
    return($html);

}


function pm_field_display($field_name, $group_id, $value='xyxy',
			   $break=false, $label=true, $ro=false, $ascii=false, 
			   $show_none=false, $text_none='None',
			   $show_any=false, $text_any='Any') {
    /*
          Display a task field either as a read-only value or as a read-write 
          making modification possible
          - field_name : name of th task field (column name)
          - group_id : the group id (project id)
          - value: the current value stored in this field (for select boxes type of field
                  it is the value_id actually. It can also be an array with mutliple values.
          - break: true if a break line is to be inserted between the field label
                 and the field value
          - label: if true display the field label.
          - ro: true if only the field value is to be displayed. Otherwise
                 display an HTML select box, text field or text area to modify the value
          - ascii: if true do not use any HTML decoration just plain text (if true
                 then read-only (ro) flag is forced to true as well)
          - show_none: show the None entry in the select box if true (value_id 100)
          - text_none: text associated with the none value_id to display in the select box
          - show_any: show the Any entry in the select box if true (value_id 0)
          - text_any: text associated with the any value_id  tp display in the select box
     */
    global $sys_datefmt;

    if ($label) {
	    $output = pm_field_label_display($field_name,$group_id,$break,$ascii);
    }
   
    // display depends upon display type of this field
    switch (pm_data_get_display_type($field_name)) {

    case 'SB':
	if ($ro) {

	    // if multiple selected values return a list of <br> separated values
	    $arr = ( is_array($value) ? $value : array($value));
	    for ($i=0;$i < count($arr); $i++) {
    		if ($arr[$i] == 0 )
    		    $arr[$i] = $text_any;
    		else if ($arr[$i] == 100 )
    		    $arr[$i] = $text_none;
    		else 
		        $arr[$i] = pm_data_get_value($field_name,$group_id,$arr[$i]);
	    }

	    $output .= join('<br>', $arr);

	} else {
	    // If it is a user name field (assigned_to, submitted_by) then make
	    // sure to add the "None" entry in the menu 'coz it's not in the DB
	    if (pm_data_is_username_field($field_name)) {
    		$show_none=true;
    		$text_none='None';
	    }
	
	    if (is_array($value))
    		$output .= pm_multiple_field_box($field_name,'',$group_id, $value,
    				       $show_none,$text_none,$show_any,
    				       $text_any);
	    else
    		$output .= pm_field_box($field_name,'',$group_id, $value,
    				       $show_none,$text_none,$show_any,
    				       $text_any);
	}
	break; 

    case 'DF':
	if ($ascii) 
	    $output .= ( ($value == 0) ? '' : format_date($sys_datefmt,$value));
	else
	    if ($ro) {
		$output .= format_date($sys_datefmt,$value);
	    } else {
		$output .= pm_field_date($field_name,
					  (($value == 0) ? '' : format_date("Y-m-j",$value,'')));
	    }
	break;

    case 'TF':
	if ($ascii) 
	    $output .= util_unconvert_htmlspecialchars($value);
	else
	    $output .= ($ro ? $value: pm_field_text($field_name,$value));
	break;

    case 'TA':
	if ($ascii) 
	    $output .= util_unconvert_htmlspecialchars($value);
	else
	    $output .= ($ro ? nl2br($value):pm_field_textarea($field_name,$value));
	break;

    default:
	$output .= 'UNKNOW TASK FIELD DISPLAY TYPE';
    }

    return($output);
}

function format_task_changes($changes) {

    global $sys_datefmt;

    reset($changes);
    $fmt = "%20s | %-25s | %s\n";

    if (user_isloggedin()) {
    	$user_id = user_getid();
    	$out_hdr = 'Changes by: '.user_getrealname($user_id).' <'.user_getemail($user_id).">\n";
    	$out_hdr .= 'Date: '.format_date($sys_datefmt,time()).' ('.user_get_timezone().')';
    } else {
    	$out_hdr = 'Changes by: Anonymous user        Date: '.format_date($sys_datefmt,time());
    }

    //Process special cases first: follow-up comment
    if ($changes['details']) {
    	$out_com = "\n\n----------------   Additional Follow-up Comments   --------------------------\n";
    	$out_com .= util_unconvert_htmlspecialchars($changes['details']['add']);
    	unset($changes['details']);
    }

    //Process special cases first: task file attachment
    if ($changes['attach']) {
    	$out_att = "\n\n----------------   Additional Task Attachment   --------------------------\n";
    	$out_att .= sprintf("File name: %-30s Size:%d KB\n",$changes['attach']['name'],
    			 intval($changes['attach']['size']/1024) );
    	$out_att .= $changes['attach']['description']."\n".$changes['attach']['href'];
    	unset($changes['attach']);
    }

    // All the rest of the fields now
    reset($changes);
    while ( list($field,$h) = each($changes)) {

    	// If both removed and added items are empty skip - Sanity check
	    if (!$h['del'] && !$h['add']) { continue; }

	    $label = pm_data_get_label($field);
	    if (!$label) { $label = $field; }
	    $out .= sprintf($fmt, $label, $h['del'],$h['add']);
    }
    
    if ($out) {
    	$out = "\n\n".sprintf($fmt,'What    ','Removed','Added').
    	"---------------------------------------------------------------------------\n".$out;
    }

    return($out_hdr.$out.$out_com.$out_att);

}

function format_task_details ($project_task_id, $group_id, $ascii=false) {

    /*
      Format the details rows from task_history
      */
    global $sys_datefmt;
    $result=pm_data_get_followups ($project_task_id);
    $rows=db_numrows($result);

    // No followup comment -> return now
    if ($rows <= 0) {
    	if ($ascii)
    	    $out = "\n\nNo Followups Have Been Posted\n";
    	else
    	    $out = '<H4>No Followups Have Been Posted</H4>';
    	return $out;
    }


    // Header first
    if ($ascii) {
	    $out .= "Follow-up Comments\n*******************";
    } else {
    	$title_arr=array();
    	$title_arr[]='Comment';
    	$title_arr[]='Date';
    	$title_arr[]='By';
    	
    	$out .= html_build_list_table_top ($title_arr);
    }
    
    // Loop throuh the follow-up comments and format them
    for ($i=0; $i < $rows; $i++) {
	
    	if ($ascii) {
    	    $fmt = "\n\n-------------------------------------------------------\n".
    		"Date: %-30sBy: %s\n%s";
    	} else {
    	    $fmt = "\n".'<tr class="%s"><td><b>%s</b><BR>%s</td>'.
    		'<td valign="top">%s</td></tr>';
    	}
    	
    	// I wish we had sprintf argument swapping in PHP3 but
    	// we don't so do it the ugly way...
    	if ($ascii) {
    	    $out .= sprintf($fmt,
    			    format_date($sys_datefmt,db_result($result, $i, 'date')),
    			    db_result($result, $i, 'user_name'),
    			    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
    			    );
    	} else {
    	    $out .= sprintf($fmt,
    			    util_get_alt_row_color($i),
    			    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id),
    			    format_date($sys_datefmt,db_result($result, $i, 'date')),
    			    db_result($result, $i, 'user_name'));
    	}
    }

    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");

    return($out);
}

function format_task_assigned_to ($project_task_id, $group_id, $ascii=false) {

    /*
      Format the details rows from task_history
      */
    global $sys_datefmt;
    $result=pm_data_get_assigned_to_name ($project_task_id);
    $rows=db_numrows($result);

    // No followup comment -> return now
    if ($rows <= 0) {
    	if ($ascii)
    	    $out = "\n\nNo Assigned to\n";
    	else
    	    $out = '<H4>No Assigned to</H4>';
    	return $out;
    }


    // Header first
    if ($ascii) {
	    $out .= "Assigned to:\n";
    } else {
        $out .= "<p>Assigned to:";
    }
    
    // Loop throuh the follow-up comments and format them
    for ($i=0; $i < $rows-1; $i++) {
	    $out .= db_result($result, $i, 'user_name').", ";
    }
    $out .= db_result($result, $i, 'user_name');

    // final touch...
    $out .= ($ascii ? "\n" : "</p>");

    return($out);
}

function pm_build_notification_matrix($user_id) {

    // Build the notif matrix indexed with roles and events labels (not id)
    $res_notif = pm_data_get_notification_with_labels($user_id);
    while ($arr = db_fetch_array($res_notif)) {
	    $arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
    }
    return $arr_notif;
}

function pm_check_notification($user_id, $role, $changes=false) {

    $send = false;
    $arr_notif = pm_build_notification_matrix($user_id);
    if (!$arr_notif) { return true; }

    //echo "==== DBG Checking Notif. for $user_id (role=$role)<br>";
    $user_name = user_getname($user_id);

    //----------------------------------------------------------
    // If it's a new task only (changes is false) check the NEW_TASK event and
    // ignore all other events
    if ($changes==false) {
    	if ($arr_notif[$role]['NEW_TASK']) {
    	    //echo "DBG NEW_TASK notified<br>";
    	    return true;
    	} else {
    	    //echo "DBG No notification<br>";
    	    return false;
    	}
    }

    //----------------------------------------------------------
    //Check: I_MADE_IT  (I am the author of the change )
    // Check this one first because if the user said no she doesn't want to be 
    // aware of any of her change in this role and we can return immediately.
    if (($user_id == user_getid()) && !$arr_notif[$role]['I_MADE_IT']) {
    	//echo "DBG Dont want to receive my own changes<br>";
    	return false;
    }
    
    //----------------------------------------------------------
    // Check :  NEW_COMMENT  A new followup comment is added 
    if ($arr_notif[$role]['NEW_COMMENT'] && isset($changes['details'])) {
    	//echo "DBG NEW_COMMENT notified<br>";
    	return true;
    }

    //----------------------------------------------------------
    //Check: NEW_FILE  (A new file attachment is added)
    if ($arr_notif[$role]['NEW_FILE'] && isset($changes['attach'])) {
    	//echo "DBG NEW_FILE notified<br>";
    	return true;
    }
  
    //----------------------------------------------------------
    //Check: CLOSED  (The task is closed)
    // Rk: this one has precedence over PSS_CHANGE. So notify even if PSS_CHANGE
    // says no.
    if ($arr_notif[$role]['CLOSED'] && ($changes['status_id']['add'] == 'Closed')) {
    	//echo "DBG CLOSED task notified<br>";
    	return true;
    }

    //----------------------------------------------------------
    //Check: PSS_CHANGE  (Priority,Status changes)
    if ($arr_notif[$role]['PSS_CHANGE'] && 
	(isset($changes['priority']) || isset($changes['status_id'])) ) {
    	//echo "DBG PSS_CHANGE notified<br>";
    	return true;
    }


    //----------------------------------------------------------
    // Check :  ROLE_CHANGE (I'm added to or removed from this role)
    // Rk: This event is meanningless for Commenters. It also is for submitter but may be
    // one day the submitter will be changeable by the project admin so test it.
    // Rk #2: check this one at the end because it is the most CPU intensive and this
    // event seldomly happens
    if ($arr_notif['SUBMITTER']['ROLE_CHANGE'] &&
	(($changes['created_by']['add'] == $user_name) || ($changes['created_by']['del'] == $user_name)) &&
	($role == 'SUBMITTER') ) {
    	//echo "DBG ROLE_CHANGE for submitter notified<br>";
    	return true;
    }

    if ($arr_notif['ASSIGNEE']['ROLE_CHANGE'] &&
	(($changes['Assigned to']['add'] == $user_name) || ($changes['Assigned to']['del'] == $user_name)) &&
	($role == 'ASSIGNEE') ) {
    	//echo "DBG ROLE_CHANGE for role assignee notified<br>";
    	return true;
    }

    $arr_cc_changes = array();
    if (isset($changes['CC']['add']))
    	$arr_cc_changes = split(',',$changes['CC']['add']);
    $arr_cc_changes[] = $changes['CC']['del'];
    $is_user_in_cc_changes = in_array($user_name,$arr_cc_changes);    
    $are_anyother_user_in_cc_changes =
	(!$is_user_in_cc_changes || count($arr_cc_changes)>1);    

    if ($arr_notif['CC']['ROLE_CHANGE'] && ($role == 'CC')) {
    	if ($is_user_in_cc_changes) {
    	    //echo "DBG ROLE_CHANGE for cc notified<br>";
    	    return true;
    	}
    }
    
    //----------------------------------------------------------
    //Check: CC_CHANGE  (CC_CHANGE is added or removed)
    // check this right after because  role cahange for cc can contradict
    // thee cc_change notification. If the role change on cc says no notification
    // then it has precedence over a cc_change
    if ($arr_notif[$role]['CC_CHANGE'] && isset($changes['CC'])) {
    	// it's enough to test role against 'CC' because if we are at that point
    	// it means that the role_change for CC was false or that role is not CC
    	// So if role is 'CC' and we are here it means that the user asked to not be
    	// notified on role_change as CC, unless other users are listed in the cc changes
    	if (($role != 'CC') || (($role == 'CC') && $are_anyother_user_in_cc_changes)) {
    	    //echo "DBG CC_CHANGE notified<br>";
    	    return true; 
    	}
    }


    //----------------------------------------------------------
    //Check: CHANGE_OTHER  (Any changes not mentioned above)
    // *** THIS ONE MUST ALWAYS BE TESTED LAST
    
    // Delete all tested fields from the $changes array. If any remains then it
    // means a notification must be sent
    unset($changes['details']);
    unset($changes['attach']);
    unset($changes['priority']);
    unset($changes['status_id']);
    unset($changes['CC']);
    unset($changes['Assigned to']);
    unset($changes['created_by']);
    if ($arr_notif[$role]['ANY_OTHER_CHANGE'] && count($changes)) {
    	//echo "DBG ANY_OTHER_CHANGE notified<br>";
    	return true;
    }

    // Sorry, no notification...
    //echo "DBG No notification!!<br>";
    return false;
}

function pm_build_notification_list($project_task_id, $group_id, $changes) {

    $sql="SELECT created_by from project_task WHERE project_task_id='$project_task_id'";
    $res_as=db_query($sql);

    // Rk: we store email addresses in a hash to make sure they are only
    // stored once. Normally if an email is repeated several times sendmail
    // would take care of it but I prefer taking care of it now.
    // Same for user ids.
    // We also use the user_ids hash to check if a user has already been selected for 
    // notification. If so it is not necessary to check it again in another role.
    $addresses = array();
    $user_ids = array();

    // check submitter notification preferences
    $user_id = db_result($res_as,0,'created_by');
    if ($user_id != 100) {
    	if (pm_check_notification($user_id, 'SUBMITTER', $changes)) {
    	    $user_ids[$user_id] = true;
    	}
    }

    // check assignees notification preferences
    $res_assignee_to=pm_data_get_assigned_to($project_task_id);
    $rows=db_numrows($res_assignee_to);

    for ($i=0; $i < $rows; $i++) {
	    
        $user_id = db_result($res_assignee_to,$i,'assigned_to_id');
        if ($user_id != 100) {
        	if (!$user_ids[$user_id] && pm_check_notification($user_id, 'ASSIGNEE', $changes)) {
        	    $user_ids[$user_id] = true;
        	}
        }
    }

    // check old assignee  notification preferences if assignee was just changed
    $user_name = $changes['Assigned to']['del'];
    if ($user_name) {
    	$res_oa = user_get_result_set_from_unix($user_name);
    	$user_id = db_result($res_oa,0,'user_id');
    	if (!$user_ids[$user_id] && pm_check_notification($user_id, 'ASSIGNEE', $changes)) {
    	    $user_ids[$user_id] = true;
    	}
    }
    
    // check all CC 
    // a) check all the people in the current CC list
    // b) check the CC that has just been removed if any and see if she
    // wants to be notified as well
    // if the CC indentifier is an email address then notify in any case
    // because this user has no personal setting
    $res_cc = pm_data_get_cc_list($project_task_id);
    $arr_cc = array();
    if ($res_cc && (db_numrows($res_cc) > 0)) {
    	while ($row = db_fetch_array($res_cc)) {
    	    $arr_cc[] = $row['email'];
    	}
    }
    // Only one CC can be deleted at once so just append it to the list....
    $arr_cc[] = $changes['CC']['del'];

    while (list(,$cc) = each($arr_cc)) {
    	if (validate_email($cc)) {
    	    $addresses[util_normalize_email($cc)] = true;
    	} else {
    	    $res = user_get_result_set_from_unix($cc);
    	    $user_id = db_result($res,0,'user_id');
    	    if (!$user_ids[$user_id] && pm_check_notification($user_id, 'CC', $changes)) {
    		    $user_ids[$user_id] = true;
    	    }
    	}
    } // while


    // check all commenters
    $res_com = pm_data_get_commenters($project_task_id);
    if (db_numrows($res_com) > 0) {
    	while ($row = db_fetch_array($res_com)) {
    	    $user_id = $row['mod_by'];
    	    if (!$user_ids[$user_id] && pm_check_notification($user_id, 'COMMENTER', $changes)) {
    		$user_ids[$user_id] = true;
    	    }
    	}
    }

    // build the final list of email addresses
    reset($user_ids);
    while (list($user_id,) = each($user_ids)) {
	    $addresses[user_getemail($user_id)] = true;
    }

    // return an array with all the email addresses the notification must be sent to
    return (array_keys($addresses));

}

function pm_list_all_fields($sort_func=false,$by_field_id=false) {
    global $TF_USAGE_BY_ID, $TF_USAGE_BY_NAME, $AT_START;

    // If it's the first element we fetch then apply the sort
    // function  
    if ($AT_START) {
    	if (!$sort_func) { $sort_func = cmp_place; }
    	uasort($TF_USAGE_BY_ID, $sort_func);
    	uasort($TF_USAGE_BY_NAME, $sort_func);
    	$AT_START=false;
    }

    // return the next task field in the list. If the global
    // task field usage array is not set then set it the
    // first time.
    // by_field_id: true return the list of field id, false returns the
    // list of field names

    if ( list($key, $field_array) = each($TF_USAGE_BY_ID)) {
	    return($by_field_id ? $field_array['project_field_id'] : $field_array['field_name']);
    } else {
    	// rewind internal pointer for next time
    	reset($TF_USAGE_BY_ID);
    	reset($TF_USAGE_BY_NAME);
    	$AT_START=true;
    	return(false);
    }
}

function pm_mail_followup($project_task_id,$more_addresses=false,$changes=false) {
    global $sys_datefmt,$feedback;
    /*
      Send a message to the person who opened this task and the person it is assigned to - 
    */

    $sql="SELECT project_task_id,project_task.group_project_id,summary,details,percent_complete,priority,hours,start_date,end_date,created_by,status_id,group_id from project_task, project_group_list WHERE project_task_id='$project_task_id' and project_task.group_project_id=project_group_list.group_project_id";

    $result=db_query($sql);

    if (session_issecure())
	    $task_href = "https://".$GLOBALS['sys_https_host'];
    else
	    $task_href = "http://".$GLOBALS['sys_default_domain'];

    $task_href .= "/pm/task.php?func=detailtask&project_task_id=$project_task_id&group_id=".db_result($result,0,'group_id')."&group_project_id=".db_result($result,0,'group_project_id');

    if ($result && db_numrows($result) > 0) {

    	$group_id = db_result($result,0,'group_id');
    	$fmt = "%-40s";
    
    	// task fields
    	// Generate the message preamble with all required
    	// task fields - Changes first if there are some.
    	if ($changes) {
    
    	    $body = "\n=================   TASK #".$project_task_id.
    		": LATEST MODIFICATIONS   ================\n".$task_href."\n\n".
    		format_task_changes($changes)."\n\n\n\n";
    	}
    
    	$body .= "=================   TASK #".$project_task_id.
    	    ": FULL TASK SNAPSHOT   =================\n".
    	    ($changes ? '':$task_href)."\n\n";
        
    	// Some special field first (group, created by/on)
    	$body .= sprintf($fmt.$fmt."\n", 
    			 'Submitted by: '.user_getname(db_result($result,0,'created_by')),
    			 'Project: '.group_getname($group_id) );
    
    	// All other regular fields now		 
    	$i=0;
    	while ( $field_name = pm_list_all_fields() ) {
    
    	    // if the field is a special field or if not used by his project 
    	    // then skip it. Otherwise print it in ASCII format.
    	    if ( !pm_data_is_special($field_name) &&
    		 pm_data_is_used($field_name) ) {
    
        		$field_value = db_result($result,0,$field_name);
        		$body .= sprintf($fmt,pm_field_display($field_name, $group_id,
        					  $field_value,false,true,true,true));

        		$i++;
        		$body .= ($i % 2 ? '':"\n");
    	    }
    	}
    	$body .= ($i % 2 ? "\n":'');
    
    	// Now display other special fields
    	
    	// Summary first. It is a special field because it is both displayed in the
    	// title of the task form and here as a text field
    
    	$body .= "\n".pm_field_display('summary', $group_id,
    		      util_unconvert_htmlspecialchars(db_result($result,0,'summary')),false,true,true,true).
    	    "\n\n".pm_field_display('details', $group_id,
    		   util_unconvert_htmlspecialchars(db_result($result,0,'details')),false,true,true,true);
    
    	// Then output for assigned to 
    	$body .= "\n\n".format_task_assigned_to($project_task_id, $group_id, true);

    	// Then output the history of task details from newest to oldest
    	$body .= "\n\n".format_task_details($project_task_id, $group_id, true);
    
    	// Then output the CC list
    	$body .= "\n\n".format_task_cc_list($project_task_id, $group_id, true);
    
        // Then output the history of task details from newest to oldest
        $body .= "\n\n".format_pm_attached_files($project_task_id, $group_id, true);

    	// Finally output the message trailer
    	$body .= "\n\nFor detailed info, follow this link:";
    	$body .= "\n".$task_href;
    
    
    	// See who is going to receive the notification. Plus append any other email 
    	// given at the end of the list.
    	$arr_addresses = pm_build_notification_list($project_task_id,$group_id,$changes);
    	$to = join(',',$arr_addresses);
    
    	if ($more_addresses) {
    	    $to .= ($to ? ',':'').$more_addresses;
    	}
    
    	//echo "DBG Sending email to: $to<br";
    
    	$hdrs = 'From: noreply@'.$GLOBALS['sys_default_domain']."\n";
	$hdrs .='X-CodeX-Project: '.group_getunixname($group_id)."\n";
	$hdrs .='X-CodeX-Artifact: task'."\n";
	$hdrs .='X-CodeX-Artifact-ID: '.$project_task_id."\n";
	$subject='[Task #'.$project_task_id.'] '.util_unconvert_htmlspecialchars(db_result($result,0,'summary'));

    	mail($to,$subject,$body,$hdrs);
    
    	$feedback .= ' Task Update Sent '; //to '.$to;

    } else {

	    $feedback .= ' Could Not Send Task Update ';

    }
}

function pm_get_assigned_to_list_name($assigned_to) {

    $return_string = "";
        
    for ($i=0;$i < count($assigned_to)-1; $i++) {
        $return_string .= user_getname($assigned_to[$i]).", ";
    }
    $return_string .= user_getname($assigned_to[$i]);
    
    return $return_string;
}

function pm_attach_file($project_task_id,$group_id,$input_file,$input_file_name,$input_file_type,$input_file_size,$file_description, &$changes) {
    global $feedback,$sys_max_size_attachment;

    $user_id = (user_isloggedin() ? user_getid(): 100);

    $data = addslashes(fread( fopen($input_file, 'r'), filesize($input_file)));
    if ((strlen($data) < 1) || (strlen($data) > $sys_max_size_attachment)) {
	$feedback .= " - File not attached: File size must be less than ".formatByteToMb($sys_max_size_attachment)." Mbytes";
    	return false;
    }

    $sql = 'INSERT into project_file (project_task_id,submitted_by,date,description, file,filename,filesize,filetype) '.
    "VALUES ($project_task_id,$user_id,'".time()."','".htmlspecialchars($file_description).
    "','$data','$input_file_name','$input_file_size','$input_file_type')";
    
    $res = db_query($sql);

    if (!$res) {
    	$feedback .= ' - Error while attaching file: '.db_error($res);
    	return false;
    } else {
    	$file_id = db_insertid($res);
    	$feedback .= " - File attached (ID $file_id) ";
    	$changes['attach']['description'] = $file_description;
    	$changes['attach']['name'] = $input_file_name;
    	$changes['attach']['size'] = $input_file_size;
    	$changes['attach']['href'] = 'http://'.$GLOBALS['sys_default_domain'].
    	    "/pm/download.php?group_id=$group_id&project_task_id=$project_task_id&project_file_id=$file_id";
    	return true;
    }
}

function show_pm_attached_files ($project_task_id,$group_id, $ascii=false) {
    echo format_pm_attached_files ($project_task_id,$group_id, $ascii);
}

function format_pm_attached_files ($project_task_id,$group_id,$ascii=false) {

    global $sys_datefmt;

    /*
          show the files attached to this task
       */

    $result=pm_data_get_attached_files($project_task_id);
    $rows=db_numrows($result);

    // No file attached -> return now
    if ($rows <= 0) {
	if ($ascii)
	    $out = "No files currently attached\n";
	else
	    $out = '<H4>No files currently attached</H4>';
	return $out;
    }

    // Header first
    if ($ascii) {
    	$out .= "File Attachments\n****************";
    } else {	
	
    	$title_arr=array();
    	$title_arr[]='Name';
    	$title_arr[]='Description';
    	$title_arr[]='Size';
    	$title_arr[]='By';
    	$title_arr[]='On';
    	if (user_ismember($group_id,'B2')) {
    	    $title_arr[]='Delete?';
    	}
    
    	$out .= html_build_list_table_top ($title_arr);
    }

    // Determine what the print out format is based on output type (Ascii, HTML
    if ($ascii) {
    	$fmt = "\n\n-------------------------------------------------------\n".
    	    "Date: %s  Name: %s  Size: %dKB   By: %s\n%s\n%s";
    } else {
    	$fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>'.
    	    (user_ismember($group_id,'P2') ? '<td align="center">%s</td>':'').'</tr>';
    }

    // Loop throuh the attached files and format them
    for ($i=0; $i < $rows; $i++) {

    	$project_file_id = db_result($result, $i, 'project_file_id');
    	$href = "/pm/download.php?group_id=$group_id&project_task_id=$project_task_id&project_file_id=$project_file_id";
    
    	if ($ascii) {
    	    $out .= sprintf($fmt,
    			    format_date($sys_datefmt,db_result($result, $i, 'date')),
    			    db_result($result, $i, 'filename'),
    			    intval(db_result($result, $i, 'filesize')/1024),
    			    db_result($result, $i, 'user_name'),
    			    db_result($result, $i, 'description'),
    			    'http://'.$GLOBALS['sys_default_domain'].$href);
    	} else {
    	    $out .= sprintf($fmt,
    			    util_get_alt_row_color($i),
    			    "<a href=\"$href\">". db_result($result, $i, 'filename').'</a>',
    			    db_result($result, $i, 'description'),
    			    intval(db_result($result, $i, 'filesize')/1024),
    			    util_user_link(db_result($result, $i, 'user_name')),
    			    format_date($sys_datefmt,db_result($result, $i, 'date')),
    			    "<a href=\"$PHP_SELF?func=delete_file&group_id=$group_id&project_task_id=$project_task_id&project_file_id=$project_file_id\" ".
    			    '" onClick="return confirm(\'Delete this attachment?\')">'.
    			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A>');
    	}
    }

    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");

    return($out);

}

function pm_delete_file($group_id=false,$project_task_id=false,$project_file_id=false) {

    // Make sure the attachment belongs to the group
    $res = db_query("SELECT project_task_id from project_task WHERE project_task_id=$project_task_id");
    if (db_numrows($res) <= 0) {
    	$feedback .= "Task #$project_task_id doesn't belong to project";
    	return;
    }

    // Now delete the attachment
    $res = db_query("DELETE FROM project_file WHERE project_task_id=$project_task_id AND project_file_id=$project_file_id");
    if (db_numrows($res) <= 0) {
    	$feedback .= "Error deleting attachment #$project_file_id: ".db_error($res);
    } else {
    	$feedback .= "File successfully deleted";
    }
    
}

?>
