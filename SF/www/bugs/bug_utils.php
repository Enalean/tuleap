<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Bug Tracker
	By Tim Perdue, Sourceforge, 11/99
	Heavy rewrite by Tim Perdue, April 2000

*/

function bug_header($params) {
	global $group_id,$is_bug_page,$DOCUMENT_ROOT;

	//used so the search box will add the necessary element to the pop-up box
	$is_bug_page=1;

	//required params for site_project_header();
	$params['group']=$group_id;
	$params['toptab']='bugs';

	$project=project_get_object($group_id);

	//only projects can use the bug tracker, and only if they have it turned on
	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Bug Tracker');
	}
	if (!$project->usesBugs()) {
		exit_error('Error','This Project Has Turned Off The Bug Tracker');
	}
	echo site_project_header($params);

	echo '<P><B><A HREF="/bugs/?func=addbug&group_id='.$group_id.'">Submit A Bug</A>
	 | <A HREF="/bugs/?func=browse&group_id='.$group_id.'&set=open">Open Bugs</A>';
	if (user_isloggedin()) {
		echo ' | <A HREF="/bugs/?func=browse&group_id='.$group_id.'&set=my">My Bugs</A>';
		echo ' | <A HREF="/bugs/?func=modfilters&group_id='.$group_id.'">Filters</A>';
		echo ' | <A HREF="/bugs/reporting/?group_id='.$group_id.'">Reporting</A>';
	}
	echo ' | <A HREF="/bugs/admin/?group_id='.$group_id.'">Admin</A></B>';
	echo ' <hr width="300" size="1" align="left" noshade>';
}

function bug_footer($params) {
	site_project_footer($params);
}

function bug_category_box ($name='bug_category_id',$group_id=false,$checked='xyxy',$text_100='None') {
	/*
		Returns a select box populated with categories defined for this project
	*/
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=bug_data_get_categories ($group_id);
		return html_build_select_box ($result,$name,$checked,true,$text_100);
	}
}

function bug_group_box ($name='bug_group_id',$group_id=false,$checked='xyxy',$text_100='None') {
	/*
		Returns a select box populated with groups defined for this project
	*/
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=bug_data_get_groups ($group_id);
		return html_build_select_box ($result,$name,$checked,true,$text_100);
	}
}

function bug_resolution_box ($name='bug_resolution_id',$checked='xyxy') {
	/*
		Returns a select box populated with our predefined resolutions
	*/
	$result=bug_data_get_resolutions ();
	return html_build_select_box ($result,$name,$checked);
}

function bug_canned_response_box ($group_id,$name='canned_response') {
	if (!$group_id) {
		return 'ERROR - No group_id';
	} else {
		$result=bug_data_get_canned_responses($group_id);
		return html_build_select_box ($result,$name);
	}
}

function bug_technician_box ($name='assigned_to',$group_id,$checked='xyxy') {
	/*
		Returns a select box populated with the bug_techs that are defined for this project
	*/
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=bug_data_get_technicians ($group_id);
		return html_build_select_box ($result,$name,$checked);
	}
}

function bug_status_box ($name='bug_status_id',$checked='xyxy',$text_100='None') {
	/*
		Returns a select box populated with the pre-defined bug statuses
	*/
	$result=bug_data_get_statuses ();
	return html_build_select_box ($result,$name,$checked,true,$text_100);
}

function bug_multiple_task_depend_box ($name='dependent_on_task[]',$group_id=false,$bug_id=false) {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else if (!$bug_id) {
		return 'ERROR - no bug_id';
	} else {
		$result=bug_data_get_tasks ($group_id);
		$result2=bug_data_get_dependent_tasks ($bug_id);
		return html_build_multiple_select_box ($result,$name,util_result_column_to_array($result2));

	}
}

function bug_multiple_bug_depend_box ($name='dependent_on_bug[]',$group_id=false,$bug_id=false) {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else if (!$bug_id) {
		return 'ERROR - no bug_id';
	} else {
		$result=bug_data_get_valid_bugs ($group_id,$bug_id);
		$result2=bug_data_get_dependent_bugs ($bug_id);
		return html_build_multiple_select_box($result,$name,util_result_column_to_array($result2));
	}
}

function show_buglist ($result,$offset,$set='open') {
	global $sys_datefmt,$group_id,$PHP_SELF;
	/*
		Accepts a result set from the bugs table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/

//      $IS_BUG_ADMIN=false; //user_ismember($group_id,'B2');
/*
	echo '
		<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="massupdate">';
*/      

	$rows=db_numrows($result);
	$url = "/bugs/?group_id=$group_id&set=$set&order=";

	$title_arr=array();
	$title_arr[]='Bug ID';
	$title_arr[]='Summary';
	$title_arr[]='Date';
	$title_arr[]='Assigned To';
	$title_arr[]='Submitted By';

	$links_arr=array();
	$links_arr[]=$url.'bug_id';
	$links_arr[]=$url.'summary';
	$links_arr[]=$url.'date';
	$links_arr[]=$url.'assigned_to_user';
	$links_arr[]=$url.'submitted_by';

	echo html_build_list_table_top ($title_arr,$links_arr);

	//see if the bugs are too old - so we can highlight them
	$then=(time()-2592000);

	for ($i=0; ($i < $rows && $i < 50); $i++) {
		//(($IS_BUG_ADMIN)?'<INPUT TYPE="CHECKBOX" NAME="bug_id[]" VALUE="'. db_result($result, $i, 'bug_id') .'"> ':'')
		echo '
		<TR BGCOLOR="'. get_priority_color(db_result($result, $i, 'priority')) .'">'.
		'<TD><A HREF="/bugs/?func=detailbug&bug_id='. db_result($result, $i, 'bug_id') .
		'&group_id='. db_result($result, $i, 'group_id') .'">'. db_result($result, $i, 'bug_id') .'</A></TD>'.
		'<TD>'. db_result($result, $i, 'summary') .'</TD>'.
		'<TD>'. (($set != 'closed' && db_result($result, $i, 'date') < $then)?'<B>* ':'&nbsp; ') . date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
		'<TD>'. db_result($result, $i, 'assigned_to_user') .'</TD>'.
		'<TD>'. db_result($result, $i, 'submitted_by') .'</TD></TR>';

	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '
		<TR><TD COLSPAN="2">';
	if ($offset > 0) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset-50).'"><B><-- Previous 50</B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD><TD>&nbsp;</TD><TD COLSPAN="2">';
	
	if ($rows > 50) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset+50).'"><B>Next 50 --></B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD></TR>';

       /*
		Mass Update Code
	* /     
	if ($IS_BUG_ADMIN) {
		echo '<TR><TD COLSPAN="5">
		<TABLE WIDTH="100%" BORDER="0">

		<TR><TD><B>Category:</B><BR>'. bug_category_box ('bug_category_id',$group_id) .'</TD>
		<TD><B>Priority:</B><BR>';
		echo build_priority_select_box ('priority');
		echo '</TD></TR>

		<TR><TD><B>Group:</B><BR>'. bug_group_box ('bug_group_id',$group_id) .'</TD>
		<TD><B>Bug Group:</B><BR>'. bug_resolution_box ('resolution_id') .'</TD></TR>

		<TR><TD><B>Assigned To:</B><BR>'. bug_technician_box ('assigned_to',$group_id) .'</TD>
		<TD><B>Status:</B><BR>'. bug_status_box ('status_id') .'</TD></TR>

		<TR><TD COLSPAN="2" ALIGN="MIDDLE"><INPUT TYPE="SUBMIT" name="submit" VALUE="Mass Update"></TD></TR>

		</TABLE>	
		</TD></TR>';
	}

	*/


	echo '</TABLE>';

}

function mail_followup($bug_id,$more_addresses=false) {
	global $sys_datefmt,$feedback;
	/*
		Send a message to the person who opened this bug and the person it is assigned to
	*/

	$sql="SELECT bug.date,bug.details,bug.group_id,bug.priority,bug.bug_id,bug.summary,bug_resolution.resolution_name,bug_group.group_name,".
		"bug.date,bug_category.category_name,bug_status.status_name,user.user_name,user.email,user2.email AS assigned_to_email, groups.group_name AS project_name ".
		"FROM bug,user,user user2,bug_category,bug_status,bug_group,bug_resolution,groups ".
		"WHERE user2.user_id=bug.assigned_to AND bug.status_id=bug_status.status_id ".
		"AND bug_resolution.resolution_id=bug.resolution_id AND bug_group.bug_group_id=bug.bug_group_id ".
		"AND bug.category_id=bug_category.bug_category_id AND user.user_id=bug.submitted_by AND bug.bug_id='$bug_id' AND groups.group_id = bug.group_id";

	$result=db_query($sql);



	if ($result && db_numrows($result) > 0) {
			
			
		$body = 'Bug #'.db_result($result,0,'bug_id').', was updated on '.date($sys_datefmt,db_result($result,0,'date')).
		"\nHere is a current snapshot of the bug.".
		"\n\nProject: ".db_result($result,0,'project_name').
		"\nCategory: ".db_result($result,0,'category_name').
		"\nStatus: ".db_result($result,0,'status_name').
		"\nResolution: ".db_result($result,0,'resolution_name').
		"\nBug Group: ".db_result($result,0,'group_name').
		"\nPriority: ".db_result($result,0,'priority').
		"\nSummary: ".util_unconvert_htmlspecialchars(db_result($result,0,'summary')).
		"\n\nDetails: ".util_unconvert_htmlspecialchars(db_result($result,0,'details'));

		$sql="SELECT user.email,user.user_name,bug_history.date,bug_history.old_value ".
			"FROM bug_history,user ".
			"WHERE user.user_id=bug_history.mod_by ".
			"AND bug_history.field_name='details' ".
			"AND bug_history.bug_id='$bug_id'";
		$result2=db_query($sql);
		$rows=db_numrows($result2);
		if ($result2 && $rows > 0) {
			$body .= "\n\nFollow-Ups:";
			for ($i=0; $i<$rows;$i++) {
				$body .= "\n\nDate: ".date($sys_datefmt,db_result($result2,$i,'date'));
				$body .= "\nBy: ".db_result($result2,$i,'user_name');
				$body .= "\n\nComment:\n".util_unconvert_htmlspecialchars(db_result($result2,$i,'old_value'));
				$body .= "\n-------------------------------------------------------";
			}
		}
		$body .= "\n\nFor detailed info, follow this link:";
		$body .= "\nhttp://$GLOBALS[sys_default_domain]/bugs/?func=detailbug&bug_id=$bug_id&group_id=".db_result($result,0,'group_id');

		$subject='[Bug #'.db_result($result,0,'bug_id').'] '.util_unconvert_htmlspecialchars(db_result($result,0,'summary'));

		$to=db_result($result,0,'email').','.db_result($result,0,'assigned_to_email');

		if ($more_addresses) {
			$to .= ','.$more_addresses;
		}

		$more='From: noreply@'.$GLOBALS['sys_default_domain'];

		mail($to,$subject,$body,$more);

		$feedback .= ' Bug Update Sent '; //to '.$to;

	} else {

		$feedback .= ' Could Not Send Bug Update ';

	}
}

function show_dependent_bugs ($bug_id,$group_id) {
	$sql="SELECT bug.bug_id,bug.summary ".
		"FROM bug,bug_bug_dependencies ".
		"WHERE bug.bug_id=bug_bug_dependencies.bug_id ".
		"AND bug_bug_dependencies.is_dependent_on_bug_id='$bug_id'";
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > 0) {
		echo '
			<H3>Other Bugs That Depend on This Bug</H3>';

		$title_arr=array();
		$title_arr[]='Bug ID';
		$title_arr[]='Summary';
	
		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
				<TD><A HREF="/bugs/?func=detailbug&bug_id='.
				db_result($result, $i, 'bug_id').
				'&group_id='.$group_id.'">'.db_result($result, $i, 'bug_id').'</A></TD>
				<TD>'.db_result($result, $i, 'summary').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Other Bugs are Dependent on This Bug</H3>';
		echo db_error();
	}
}

function show_bug_details ($bug_id) {
	/*
		Show the details rows from bug_history
	*/
	global $sys_datefmt;
	$result=bug_data_get_followups ($bug_id);
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
			echo '<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>'.
				ereg_replace("\n","<BR>",db_result($result, $i, 'old_value')).'</TD>'.
				'</TD>'.
				'<TD VALIGN="TOP">'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
				'<TD VALIGN="TOP">'.db_result($result, $i, 'user_name').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Followups Have Been Posted</H3>';
	}
}

function show_bughistory ($bug_id) {
	/*
		show the bug_history rows that are relevant to this bug_id, excluding details
	*/
	global $sys_datefmt;
	$result=bug_data_get_history($bug_id);
	$rows=db_numrows($result);

	if ($rows > 0) {

		echo '
		<H3>Bug Change History</H3>
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
				<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>'.$field.'</TD><TD>';

			if ($field == 'status_id') {

				echo bug_data_get_status_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'category_id') {

				echo bug_data_get_category_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'assigned_to') {

				echo user_getname(db_result($result, $i, 'old_value'));

			} else if ($field == 'close_date') {

				echo date($sys_datefmt,db_result($result, $i, 'old_value'));

			} else if ($field == 'resolution_id') {

				echo bug_data_get_resolution_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'bug_group_id') {

				echo bug_data_get_group_name(db_result($result, $i, 'old_value'));

			} else {

				echo db_result($result, $i, 'old_value');

			}
			echo '</TD>'.
				'<TD>'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
		}

		echo '
			</TABLE>';
	
	} else {
		echo '
			<H3>No Changes Have Been Made to This Bug</H3>';
	}
}

?>
