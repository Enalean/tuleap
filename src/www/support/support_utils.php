<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*

	Support Request Manager 
	By Tim Perdue, Sourceforge, January, 2000
	Heavy Rewrite Tim Perdue, April, 2000

*/

function support_header($params) {
	global $group_id,$is_support_page,$DOCUMENT_ROOT;

	//set to 1 so the search box will add the necessary element to the pop-up box
	$is_support_page=1;

	//required by new site_project_header
	$params['group']=$group_id;
	$params['toptab']='support';

	//only projects can use the bug tracker, and only if they have it turned on
	$project=project_get_object($group_id);

	if ($project->isFoundry()) {
		exit_error('Error','Only Projects Can Use The Support Request Manager');
	}
	if (!$project->usesSupport()) {
		exit_error('Error','This Project Has Turned Off The Support Request Manager');
	}


	site_project_header($params);

	echo '<P><B><A HREF="/support/?func=addsupport&group_id='.$group_id.'">Submit A Request</A>';
	if (user_isloggedin()) {
		echo ' | <A HREF="/support/?func=browse&group_id='.$group_id.'&set=my">My Requests</A>';
	}
	echo ' | <A HREF="/support/?func=browse&group_id='.$group_id.'&set=open">Open Requests</A>';
	echo ' | <A HREF="/support/admin/?group_id='.$group_id.'">Admin</A>';
	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo '</B>';
	echo '<HR NoShade SIZE="1" SIZE="300">';
}

function support_header_admin($params) {
    global $group_id,$is_support_page,$DOCUMENT_ROOT;

    //used so the search box will add the necessary element to the pop-up box
    $is_support_page=1;
    
    //required params for site_project_header();
    $params['group']=$group_id;
    $params['toptab']='support';
    
    $project=project_get_object($group_id);
    
    //only projects can use the bug tracker, and only if they have it turned on
    if ($project->isFoundry()) {
	exit_error('Error','Only Projects Can Use The Support Request Manager');
    }
    if (!$project->usesSupport()) {
	exit_error('Error','This Project Has Turned Off The Support Request Manager');
    }
    echo site_project_header($params);
    echo '<P><B><A HREF="/support/admin/?group_id='.$group_id.'">Admin</A></B>';
    echo ' | <B><A HREF="/support/admin/index.php?support_cat=1&group_id='.$group_id.'">Manage Categories</A></B>';
    echo ' | <b><A HREF="/support/admin/index.php?create_canned=1&group_id='.$group_id.'">Manage Canned Responses</A></b>';
    echo ' | <b><A HREF="/support/admin/index.php?other_settings=1&group_id='.$group_id.'">Other Settings</A></b>';
    if ($params['help']) {
	echo ' | <b>'.help_button($params['help'],false,'Help').'</b>';
    }
    echo ' <hr width="300" size="1" align="left" noshade>';
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
			<TR class="'. get_priority_color(db_result($result, $i, 'priority')) .'">'.
			'<TD class="small"><A HREF="'.$PHP_SELF.'?func=detailsupport&support_id='. db_result($result, $i, 'support_id').
			'&group_id='. db_result($result, $i, 'group_id').'">'. db_result($result, $i, 'support_id') .'</A></TD>'.
			'<TD class="small">'. db_result($result, $i, 'summary') .'</TD>'.
			'<TD class="small">'. (($set != 'closed' && db_result($result, $i, 'date') < $then)?'<B>* ':'&nbsp; ') . format_date($sys_datefmt,db_result($result, $i, 'date')) .'</TD>'.
			'<TD class="small">'.util_user_link(db_result($result,$i,'assigned_to_user')).'</TD>'.
			'<TD class="small">'.util_user_link(db_result($result,$i,'submitted_by')).'</TD></TR>';

	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '
		<TR><TD COLSPAN="2" class="small">';
	if ($offset > 0) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset-50).'"><B><-- Previous 50</B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD><TD>&nbsp;</TD><TD COLSPAN="2" class="small">';
	
	if ($rows==50) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset+50).'"><B>Next 50 --></B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD></TR></TABLE>';
}

function sr_utils_mail_followup($support_id,$more_addresses=false,$changes=false) {
    global $sys_datefmt,$feedback,$sys_lf;
    /*
             Send a message to the person who opened this support and the person it is assigned to
    */

    $sql="SELECT support.priority,support.group_id,support.support_id,support.summary,".
	"support_status.status_name,support_category.category_name,support.open_date, ".
	"support.submitted_by,support.assigned_to ".
	"FROM support,user,support_status,support_category ".
	"WHERE support.support_status_id=support_status.support_status_id ".
	"AND support.support_category_id=support_category.support_category_id ".
	"AND support.support_id='$support_id'";

	$result=db_query($sql);
	$sr_href = get_server_url()."/support/?func=detailsupport&support_id=$support_id&group_id=".db_result($result,0,'group_id');

	if ($result && db_numrows($result) > 0) {

	    $group_id = db_result($result,0,'group_id');
	    $fmt = "%-40s";

	    // Generate the message preamble with all required
	    // bug fields - Changes first if there are some.
	    if ($changes) {
		$body = "\n============   SUPPORT REQ. #".$support_id.
		    ": LATEST MODIFICATIONS   =============\n".$sr_href."\n\n".
		format_support_changes($changes)."\n\n\n\n";
	    }


	    $body .= "\n============   SUPPORT REQ. #".$support_id.
		": FULL SNAPSHOT   ==============\n".
		($changes ? '':$sr_href)."\n\n";
	    
	    $body .= sprintf("$fmt$fmt\n$fmt\n",
			     'Submitted by: '.user_getname(db_result($result,0,'submitted_by')),
			     'Project: '.group_getname($group_id),
			     'Submitted on: '.format_date($sys_datefmt,db_result($result,0,'open_date')));
	    $body .= sprintf("$fmt$fmt\n$fmt$fmt\n\n%s\n\n",
			     "Category: ".db_result($result,0,'category_name'),
			     'Assigned to: '.user_getname(db_result($result,0,'assigned_to')),
			     "Status: ".db_result($result,0,'status_name'),
			     "Priority: ".db_result($result,0,'priority'),
			     "Summary: ".util_unconvert_htmlspecialchars(db_result($result,0,'summary')) );

	    $odq = support_data_get_original_description($support_id);
	    $body .= "Original submission:\n".util_unconvert_htmlspecialchars(db_result($odq,0,'body'))."\n\n";

	    // Include all follow-up comments
	    $body .= format_support_details($support_id,$group_id,true);

	    /*
	                  get all the email addresses that have dealt with this request
		also add a) the assignee and b) the notification email address
		**** IMPORTANT REMARK ****
		The from_email field contains both user login names or email addresses. 
		So the mail command assumes that each login name has an email 
		alias ok in /etc/aliases
	         */

	    $email_res=db_query("SELECT distinct from_email FROM support_messages WHERE support_id='$support_id'");
	    $rows=db_numrows($email_res);
	    if ($email_res && $rows > 0) {
		$mail_arr=result_column_to_array($email_res,0);
                $not_first=false;
                while (list(,$user_mail) = each($mail_arr)) {
                    // The list may contain email adresses and unix names
                    // We need to convert the unix names to the corresponding email adresses.
                    if ($not_first) $to .= ', ';
                    $not_first=true;
                    if (strstr($user_mail,'@')) {
                        // already email adress
                        $to .= $user_mail;
                    } else {
                        // Unix name
                        $to .= user_getemail_from_unix($user_mail);
                    }
                }
	    }
	    // Add the assignee and the submitter for email notification
	    $user_id = db_result($result,0,'assigned_to');
	    if ($user_id != 100) {
		$user_email = user_getemail($user_id);
		if ($user_email) { $to .= ','.$user_email; }
	    }
	    $user_id = db_result($result,0,'submitted_by');
	    if ($user_id != 100) {
		$user_email = user_getemail($user_id);
		if ($user_email) { $to .= ','.$user_email; }
	    }
	    
	    // global email address for notification
	    if ($more_addresses) {
		$to .= ','.$more_addresses;
	    }
	    
	    // Send the email message
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
	    $hdrs = 'From: noreply@'.$host.$sys_lf;
	    $hdrs .='Content-type: text/plain; charset=iso-8859-1'.$sys_lf;
	    $hdrs .='X-CodeX-Project: '.group_getunixname($group_id).$sys_lf;
	    $hdrs .='X-CodeX-Artifact: support'.$sys_lf;
	    $hdrs .='X-CodeX-Artifact-ID: '.$support_id.$sys_lf;
	    $subject="[ SR #".db_result($result,0,"support_id")." ] ".
		util_unconvert_htmlspecialchars(db_result($result,0,"summary"));

	    mail($to, $subject, $body, $hdrs);

	    $feedback .= " Support Request Update Emailed ";
	    
	} else {

	    $feedback .= " Could Not Send Support Request Update ";
	    echo db_error();   

	}
}

function format_support_details ($support_id, $group_id, $ascii=false) {
    /*
           Show the details rows from support_history
          */
    global $sys_datefmt;
    $result= support_data_get_messages ($support_id);
    $rows=db_numrows($result);
    
    // No followup comment -> return now
    if ($rows <= 0) {
	if ($ascii)
	    $out = "\n\nNo Followups Have Been Posted\n";
	else
	    $out = '<H4>No Followups Have Been Posted</H4>';
	return $out;
    }
    
    // Header first define formats
    if ($ascii) {
	$out .= "Follow-up Comments\n*******************";
	$fmt = "\n\n-------------------------------------------------------\n".
	    "Date: %-30sBy: %s\n%s";
    } else {
	$out .= '
		<H3>Follow-up Comments</H3>
		<P>';
	$title_arr=array();
	$title_arr[]='Message';
	$title_arr[]='Date';
	$title_arr[]='By';
	
	$out .= html_build_list_table_top ($title_arr);
	
	$fmt = "\n".'<tr class="%s"><td>%s</td>'.
	    '<td valign="top">%s</td><td valign="top">%s</td></tr>';
	
    }
    
    // Loop throuh the follow-up comments and format them
    for ($i=0; $i < $rows; $i++) {
	
	// Determine  wether we use the login name or the email address
	$email_arr=explode('@',db_result($result,$i,'from_email'));
	if ($ascii) {
	    $user_link = ($email_arr[1] ? db_result($result,$i,'from_email') : $email_arr[0]);
	} else {
	    if ($email_arr[1]) {
		$user_link = '<a href="mailto:'. db_result($result,$i,'from_email').'">'.$email_arr[0].'</a>';
	    } else {
		$user_link = util_user_link($email_arr[0]);
	    }
	}
	
	// Generate formatted output
	if ($ascii) {
	    $out .= sprintf($fmt,	
			    format_date($sys_datefmt,db_result($result, $i, 'date')),
			    $user_link,
			    util_unconvert_htmlspecialchars(db_result($result, $i, 'body')) );
	} else {
	    $out .= sprintf($fmt, util_get_alt_row_color($i),
			    util_make_links(nl2br(db_result($result, $i, 'body')), $group_id),
			    format_date($sys_datefmt,db_result($result, $i, 'date')),
			    $user_link);
	}
    }
    
    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");
    
    return($out);
}

function show_support_details ($support_id, $group_id, $ascii=false) {
    echo format_support_details($support_id, $group_id, $ascii);
}


function format_support_changes($changes) {

    global $sys_datefmt, $user_email;

    reset($changes);
    $fmt = "%20s | %-25s | %s\n";

    if (user_isloggedin()) {
	$user_id = user_getid();
	$out_hdr = 'Changes by: '.user_getrealname($user_id).' <'.user_getemail($user_id).">\n";
	$out_hdr .= 'Date: '.format_date($sys_datefmt,time()).' ('.user_get_timezone().')';
    } else {
	$out_hdr = 'Changes by: '.$user_email.'     Date: '.format_date($sys_datefmt,time());
    }


    //Process special cases first: follow-up comment
    if ($changes['details']) {
	$out_com = "\n\n----------------   Additional Follow-up Comments   ---------------------------\n";
	$out_com .= util_unconvert_htmlspecialchars($changes['details']['add']);
	unset($changes['details']);
    }

    //Process special cases first: bug file attachment
    if ($changes['attach']) {
	$out_att = "\n\n----------------   Additional File Attachment   ---------------------------\n";
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

	$label = $h['label'];
	if (!$label) { $label = $field; }
	$out .= sprintf($fmt, $label, $h['del'],$h['add']);
    }
    if ($out) {
	$out = "\n\n".sprintf($fmt,'What    ','Removed','Added').
	"---------------------------------------------------------------------------\n".$out;
    }

    return($out_hdr.$out.$out_com.$out_att);

}

function show_supporthistory ($support_id) {
	/*
		show the support_history rows that are relevant to this support_id, excluding details
	*/
	global $sys_datefmt;
	$result= support_data_get_history ($support_id);
	$rows= db_numrows($result);

	if ($rows > 0) {

	    echo "\n".'<H3>Change History</H3><P>';
		$title_arr=array();
		$title_arr[]='Field';
		$title_arr[]='Old Value';
		$title_arr[]='Date';
		$title_arr[]='By';

		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i < $rows; $i++) {
			$field=db_result($result, $i, 'field_name');
			echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD>'.$field.'</TD><TD>';

			if ($field == 'support_status_id') {

				echo support_data_get_status_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'support_category_id') {

				echo support_data_get_category_name(db_result($result, $i, 'old_value'));

			} else if ($field == 'assigned_to') {

				echo user_getname(db_result($result, $i, 'old_value'));

			} else if ($field == 'close_date') {

				echo format_date($sys_datefmt,db_result($result, $i, 'old_value'));

			} else {

				echo db_result($result, $i, 'old_value');

		}
		echo '</TD>'.
			'<TD>'. format_date($sys_datefmt,db_result($result, $i, 'date')) .'</TD>'.
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
