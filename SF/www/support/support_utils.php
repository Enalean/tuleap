<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Support Request Manager 
	By Tim Perdue, Sourceforge, January, 2000
	Heavy Rewrite Tim Perdue, April, 2000

*/

function support_header($params) {
	global $group_id,$DOCUMENT_ROOT;

	//required by new site_project_header
	$params['group']=$group_id;
	$params['toptab']='support';

	//only projects can use the bug tracker, and only if they have it turned on
	$project=project_get_object($group_id);

	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Tech Support Manager');
	}
	if (!$project->usesSupport()) {
		exit_error('Error','This Project Has Turned Off The Tech Support Manager');
	}


	site_project_header($params);

	echo '<P><B><A HREF="/support/?func=addsupport&group_id='.$group_id.'">Submit A Request</A>';
	if (user_isloggedin()) {
		echo ' | <A HREF="/support/?func=browse&group_id='.$group_id.'&set=my">My Requests</A>';
	}
	echo ' | <A HREF="/support/?func=browse&group_id='.$group_id.'&set=open">Open Requests</A>';
	echo ' | <A HREF="/support/admin/?group_id='.$group_id.'">Admin</A>';

	echo '</B>';
	echo '<HR NoShade SIZE="1" SIZE="300">';
}

function support_footer($params) {
	site_project_footer($params);
}

function support_category_box ($group_id,$name='support_category_id',$checked='xzxz',$text_100='None') {
	if (!$group_id) {
		return 'ERROR - No group_id';
	} else {
		$result= support_data_get_categories ($group_id);
		return html_build_select_box ($result,$name,$checked,true,$text_100);
	}
}

function support_technician_box ($group_id,$name='assigned_to',$checked='xzxz') {
	if (!$group_id) {
		return 'ERROR - No group_id';
	} else {
		$result= support_data_get_technicians ($group_id);
		return html_build_select_box ($result,$name,$checked);
	}
}

function support_canned_response_box ($group_id,$name='canned_response',$checked='xzxz') {
	if (!$group_id) {
		return 'ERROR - No group_id';
	} else {
		$result= support_data_get_canned_responses ($group_id);
		return html_build_select_box ($result,$name,$checked);
	}
}

function support_status_box ($name='status_id',$checked='xzxz',$text_100='None') {
	$result=support_data_get_statuses();
	return html_build_select_box($result,$name,$checked,true,$text_100);
}

function show_supportlist ($result,$offset,$set='open') {
	global $sys_datefmt,$group_id;
	/*
		Accepts a result set from the support table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/

	$url = "/support/?group_id=$group_id&set=$set&order=";
	$title_arr=array();
	$title_arr[]='Request ID';
	$title_arr[]='Summary';
	$title_arr[]='Date';
	$title_arr[]='Assigned To';
	$title_arr[]='Submitted By';

	$links_arr=array();
	$links_arr[]=$url.'support_id';
	$links_arr[]=$url.'summary';
	$links_arr[]=$url.'date';
	$links_arr[]=$url.'assigned_to_user';
	$links_arr[]=$url.'submitted_by';

	echo html_build_list_table_top ($title_arr,$links_arr);

	$then=(time()-1296000);
	$rows=db_numrows($result);
	for ($i=0; $i < $rows; $i++) {
		echo '
			<TR BGCOLOR="'. get_priority_color(db_result($result, $i, 'priority')) .'">'.
			'<TD><A HREF="'.$PHP_SELF.'?func=detailsupport&support_id='. db_result($result, $i, 'support_id').
			'&group_id='. db_result($result, $i, 'group_id').'">'. db_result($result, $i, 'support_id') .'</A></TD>'.
			'<TD>'. db_result($result, $i, 'summary') .'</TD>'.
			'<TD>'. (($set != 'closed' && db_result($result, $i, 'date') < $then)?'<B>* ':'&nbsp; ') . date($sys_datefmt,db_result($result, $i, 'date')) .'</TD>'.
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
	
	if ($rows==50) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset+50).'"><B>Next 50 --></B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD></TR></TABLE>';
}

function mail_followup($support_id,$more_addresses=false) {
	global $sys_datefmt,$feedback;
	/*
		Send a message to the person who opened this support and the person it is assigned to
	*/

	$sql="SELECT support.priority,support.group_id,support.support_id,support.summary,".
		"support_status.status_name,support_category.category_name,support.open_date, ".
		"user.email,user2.email AS assigned_to_email ".
		"FROM support,user,user user2,support_status,support_category ".
		"WHERE user2.user_id=support.assigned_to ".
		"AND support.support_status_id=support_status.support_status_id ".
		"AND support.support_category_id=support_category.support_category_id ".
		"AND user.user_id=support.submitted_by AND support.support_id='$support_id'";

	$result=db_query($sql);

	if ($result && db_numrows($result) > 0) {
		/*
			Set up the body
		*/
		$body = "\n\nSupport Request #".db_result($result,0,'support_id').", was updated on ".date($sys_datefmt,db_result($result,0,'open_date')). 
			"\nYou can respond by visiting: ".
			"\nhttp://".$GLOBALS['sys_default_domain']."/support/?func=detailsupport&support_id=".db_result($result,0,"support_id")."&group_id=".db_result($result,0,"group_id").
			"\n\nCategory: ".db_result($result,0,'category_name').
			"\nStatus: ".db_result($result,0,'status_name').
			"\nPriority: ".db_result($result,0,'priority').
			"\nSummary: ".util_unconvert_htmlspecialchars(db_result($result,0,'summary'));


		$subject="[ SR #".db_result($result,0,"support_id")." ] ".util_unconvert_htmlspecialchars(db_result($result,0,"summary"));

		/*
			get all the email addresses that have dealt with this request
		*/

		$email_res=db_query("SELECT distinct from_email FROM support_messages WHERE support_id='$support_id'");
		$rows=db_numrows($email_res);
		if ($email_res && $rows > 0) {
			$mail_arr=result_column_to_array($email_res,0);
			$to=implode($mail_arr,', ');
		}
		if ($more_addresses) {
			$to .= ','.$more_addresses;
		}

		/*
			Now include the two most recent emails
		*/
		$sql="select * ".
			"FROM support_messages ".
			"WHERE support_id='$support_id' ORDER BY date DESC LIMIT 2";
		$result2=db_query($sql);
		$rows=db_numrows($result2);
		if ($result && $rows > 0) {
			for ($i=0; $i<$rows; $i++) {
				//get the first part of the email address
				$email_arr=explode('@',db_result($result2,$i,'from_email'));

				$body .= "\n\nBy: ". $email_arr[0] .
				"\nDate: ".date($sys_datefmt,db_result($result2,$i,'date')).
				"\n\nMessage:".
				"\n".util_unconvert_htmlspecialchars(db_result($result2,$i,'body')).
				"\n\n----------------------------------------------------------------------";
			}
			$body .= "\nYou can respond by visiting: ".
			"\nhttp://".$GLOBALS['sys_default_domain'].'/support/?func=detailsupport&support_id='.db_result($result,0,'support_id').'&group_id='.db_result($result,0,'group_id');
		}

		$more='From: noreply@'.$GLOBALS['sys_default_domain'];

		mail($to, $subject, $body, $more);

		$feedback .= " Support Request Update Emailed ";

	} else {

		$feedback .= " Could Not Send Support Request Update ";
		echo db_error();

	}
}

function show_support_details ($support_id) {
	/*
		Show the details rows from support_history
	*/
	global $sys_datefmt;
	$result= support_data_get_messages ($support_id);
	$rows=db_numrows($result);

	if ($rows > 0) {
		echo '
		<H3>Followups</H3>
		<P>';
		$title_arr=array();
		$title_arr[]='Message';

		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			$email_arr=explode('@',db_result($result,$i,'from_email'));
			echo '<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD><PRE>
Date: '. date($sys_datefmt,db_result($result, $i, 'date')) .'
Sender: '. $email_arr[0] . '
'. util_line_wrap ( db_result($result, $i, 'body'),85,"\n"). '</PRE></TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H3>No Followups Have Been Posted</H3>';
	}
}

function show_supporthistory ($support_id) {
	/*
		show the support_history rows that are relevant to this support_id, excluding details
	*/
	global $sys_datefmt;
	$result= support_data_get_history ($support_id);
	$rows= db_numrows($result);

	if ($rows > 0) {

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

			if ($field == 'support_status_id') {

				echo support_data_get_status_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'support_category_id') {

				echo support_data_get_category_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'assigned_to') {

				echo user_getname(db_result($result, $i, 'old_value'));

			} else if ($field == 'close_date') {

				echo date($sys_datefmt,db_result($result, $i, 'old_value'));

			} else {

				echo db_result($result, $i, 'old_value');

		}
		echo '</TD>'.
			'<TD>'. date($sys_datefmt,db_result($result, $i, 'date')) .'</TD>'.
			'<TD>'. db_result($result, $i, 'user_name'). '</TD></TR>';
	}

	echo '
		</TABLE>';
	
	} else {
		echo '
			<H3>No Changes Have Been Made to This Support Request</H3>';
	}
}

?>
