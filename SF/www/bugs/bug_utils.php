<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// $Id$

/*

	Bug Tracker
	By Tim Perdue, Sourceforge, 11/99
	Heavy rewrite by Tim Perdue, April 2000
	Very heavy rewrite by Laurent Julliard 2001, 2002, CodeX Team, Xerox
*/

/* Generate URL arguments from a variable wether scalar or array */
function bug_convert_to_url_arg($varname, $var) {

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

function bug_header($params) {
	global $group_id,$is_bug_page,$DOCUMENT_ROOT,$advsrch;

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
	 | <A HREF="/bugs/?func=browse&group_id='.$group_id.
	    '&set=open&advsrch='.(isset($advsrch)?$advsrch:0).
	    '">Open Bugs</A>';
	if (user_isloggedin()) {
		echo ' | <A HREF="/bugs/?func=browse&group_id='.$group_id.
		    '&set=my&advsrch='.(isset($advsrch)?$advsrch:0).
		    '">My Bugs</A>';
		// Inhibited in new version of bug tracking system because
		// not very intuitive and not used very much. Might be reactivated
		// later with a different face (like predefined custom queries)
		// echo ' | <A HREF="/bugs/?func=modfilters&group_id='.$group_id.'">Filters</A>';
		echo ' | <A HREF="/bugs/reporting/?group_id='.$group_id.'">Reporting</A>';
	}
	echo ' | <A HREF="/bugs/admin/?group_id='.$group_id.'">Admin</A></B>';
	echo ' <hr width="300" size="1" align="left" noshade>';
}

function bug_header_admin($params) {
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
    echo '<P><B><A HREF="/bugs/admin/?group_id='.$group_id.'">Admin</A></B>';
    echo ' | <B><A HREF="/bugs/admin/field_usage.php?group_id='.$group_id.'">Field Usage</A></B>';
    echo ' | <b><A HREF="/bugs/admin/field_values.php?group_id='.$group_id.'">Field Values</A></b>';
    echo ' | <b><A HREF="/bugs/admin/reports.php?group_id='.$group_id.'">Bug Reports</A></b>';
    echo ' | <b><A HREF="/bugs/admin/other_settings.php?group_id='.$group_id.'">Other Settings</A></b>';
    echo ' <hr width="300" size="1" align="left" noshade>';

}

function bug_footer($params) {
	site_project_footer($params);
}

function bug_init($group_id) {
    // Set the global arrays for faster processing at init time
    bug_data_get_all_fields($group_id, true);
}

function bug_report_init($group_id, $report_id) {
    // Set the global array with report information for faster processing
    bug_data_get_all_report_fields($group_id, $report_id, true);
}

function bug_list_all_fields($sort_func=false,$by_field_id=false) {
    global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME, $AT_START;

    // If it's the first element we fetch then apply the sort
    // function  
    if ($AT_START) {
	if (!$sort_func) { $sort_func = cmp_place; }
	uasort($BF_USAGE_BY_ID, $sort_func);
	uasort($BF_USAGE_BY_NAME, $sort_func);
	$AT_START=false;
    }

    // return the next bug field in the list. If the global
    // bug field usage array is not set then set it the
    // first time.
    // by_field_id: true return the list of field id, false returns the
    // list of field names

    if ( list($key, $field_array) = each($BF_USAGE_BY_ID)) {
	return($by_field_id ? $field_array['bug_field_id'] : $field_array['field_name']);
    } else {
	// rewind internal pointer for next time
	reset($BF_USAGE_BY_ID);
	reset($BF_USAGE_BY_NAME);
	$AT_START=true;
	return(false);
    }
}

function bug_field_display($field_name, $group_id, $value='xyxy',
			   $break=false, $label=true, $ro=false, $ascii=false, 
			   $show_none=false, $text_none='None',
			   $show_any=false, $text_any='Any') {
    /*
          Display a bug field either as a read-only value or as a read-write 
          making modification possible
          - field_name : name of th bug field (column name)
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
	$output = bug_data_get_label($field_name).': ';
	if (!$ascii) 
	    $output = '<B>'.$output.'</B>';
	if ($break) 
	    $output .= ($ascii?"\n":'<BR>');
	else
	    $output .= ($ascii? ' ':'&nbsp;');
    }

    // display depends upon display type of this field
    switch (bug_data_get_display_type($field_name)) {

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
		    $arr[$i] = bug_data_get_value($field_name,$group_id,$arr[$i]);
	    }

	    $output .= join('<br>', $arr);

	} else {
	    // If it is a user name field (assigned_to, submitted_by) then make
	    // sure to add the "None" entry in the menu 'coz it's not in the DB
	    if (bug_data_is_username_field($field_name)) {
		$show_none=true;
		$text_none='None';
	    }
	
	    if (is_array($value))
		$output .= bug_multiple_field_box($field_name,'',$group_id, $value,
				       $show_none,$text_none,$show_any,
				       $text_any);
	    else
		$output .= bug_field_box($field_name,'',$group_id, $value,
				       $show_none,$text_none,$show_any,
				       $text_any);
	}
	break; 

    case 'DF':
	if ($ascii) 
	    $output .= date($sys_datefmt,$value);
	else
	    $output .= ($ro ? date($sys_datefmt,$value) : bug_field_date($field_name,$value));
	break;

    case 'TF':
	if ($ascii) 
	    $output .= util_unconvert_htmlspecialchars($value);
	else
	    $output .= ($ro ? $value: bug_field_text($field_name,$value));
	break;
    case 'TA':
	if ($ascii) 
	    $output .= util_unconvert_htmlspecialchars($value);
	else
	    $output .= ($ro ? nl2br($value):bug_field_textarea($field_name,$value));
	break;

    default:
	$output .= 'UNKNOW BUG FIELD DISPLAY TYPE';
    }

    return($output);
}

function bug_field_date($field_name,$value='',$size=0,$maxlength=0,$ro=false) {

    // CAUTION!!!! The Javascript below assumes that the date always appear
    // in a field called 'bug_form'
    if ($ro)
	$html = $value;
    else {
	if (!$size || !$maxlength)
	    list($size, $maxlength) = bug_data_get_display_size($field_name);

	$html = '<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$value.'">'.
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'\', document.bug_form.'.$field_name.'.value);">'.
	'<img src="/images/calendar/cal.png" width="16" height="16" border="0" alt="Click Here to Pick up a date"></a>';
    }
    return($html);

}

function bug_multiple_field_date($field_name,$date_begin='',$date_end='',$size=0,$maxlength=0,$ro=false) {

    // CAUTION!!!! The Javascript below assumes that the date always appear
    // in a field called 'bug_form'

    if ($ro)
	if ($date_begin || $date_end)
	    $html = "Start:&nbsp;$date_begin<br>End:&nbsp;$date_end";
	else
	    $html = 'Any time';
    else {
	if (!$size || !$maxlength)
	    list($size, $maxlength) = bug_data_get_display_size($field_name);

	$html = 'Start:<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$date_begin.'">'.
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'\', document.bug_form.'.$field_name.'.value);">'.
	'<img src="/images/calendar/cal.png" width="16" height="16" border="0" alt="Click Here to Pick up start date"></a><br>'.
	'End :<INPUT TYPE="text" name="'.$field_name.'_end'.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$date_end.'">'.
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'_end\', document.bug_form.'.$field_name.'_end.value);">'.
	'<img src="/images/calendar/cal.png" width="16" height="16" border="0" alt="Click Here to Pick up end date"></a>';
    }

    return($html);

}

function bug_field_date_operator($field_name,$value='',$ro=false) {

    if ($ro) 
	$html = htmlspecialchars($value);
    else
	$html = '<SELECT name="'.$field_name.'_op">'.
	'<OPTION VALUE=">"'.(($value == '>') ? ' SELECTED':'').'>&gt;</OPTION>'.
	'<OPTION VALUE="="'.(($value == '=') ? ' SELECTED':'').'>=</OPTION>'.
	'<OPTION VALUE="<"'.(($value == '<') ? ' SELECTED':'').'>&lt;</OPTION>'.
	'</SELECT>';
    return($html);

}

function bug_field_text($field_name,$value='',$size=0,$maxlength=0) {

    if (!$size || !$maxlength)
	list($size, $maxlength) = bug_data_get_display_size($field_name);

    $html = '<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$value.'">';
    return($html);

}

function bug_field_textarea($field_name,$value='',$cols=0,$rows=0) {

    if (!$cols || !$rows)
	list($cols, $rows) = bug_data_get_display_size($field_name);

    $html = '<TEXTAREA NAME="'.$field_name.
	'" ROWS="'.$rows.'" COLS="'.$cols.'" WRAP="SOFT">'
	.nl2br($value).'</TEXTAREA>';
    return($html);

}

function bug_field_box($field_name,$box_name='',$group_id,$checked=false,$show_none=false,$text_none='None',$show_any=false, $text_any='Any') {

    /*
      Returns a select box populated with field values for this project
      if box_name is given then impose this name in the select box
      of the  HTML form otherwise use the field_name)
    */
    if (!$group_id) {
	return 'ERROR - no group_id';
    } else {
	$result = bug_data_get_field_predefined_values($field_name,$group_id,$checked);

	if ($box_name == '') {
	    $box_name = $field_name;
	}
	return html_build_select_box ($result,$box_name,$checked,$show_none,$text_none,$show_any, $text_any);
    }
}

function bug_multiple_field_box($field_name,$box_name='',$group_id,$checked=false,$show_none=false,$text_none='None',$show_any=false, $text_any='Any',$show_value=false) {

    /*
      Returns a multiplt select box populated with field values for this project
      if box_name is given then impose this name in the select box
      of the  HTML form otherwise use the field_name)
    */
    if (!$group_id) {
	return 'ERROR - no group_id';
    } else {
	$result = bug_data_get_field_predefined_values($field_name,$group_id,$checked);

	if ($box_name == '') {
	    $box_name = $field_name.'[]';
	}
	return html_build_multiple_select_box($result,$box_name,$checked,6,$show_none,$text_none, $show_any,$text_any,$show_value);
    }
}

function bug_extract_field_list($post_method=true) {

    global $HTTP_GET_VARS, $HTTP_POST_VARS, $BF_USAGE_BY_NAME;
    /* 
       Returns the list of field names in the HTML Form corresponding to a
       field used by this project
       */
    $vfl = array();
    if ($post_method) {
	reset($HTTP_POST_VARS);
	while ( list($key, $val) = each($HTTP_POST_VARS)) {
	    if (isset($BF_USAGE_BY_NAME[$key])) {
		$vfl[$key] = $val;
		//echo "Accepted key = ".$key." val = $val<BR>";
	    } else {
		//echo "Rejected key = ".$key." val = $val<BR>";
	    }
	}
    } else {
	reset($HTTP_GET_VARS);
	while ( list($key, $val) = each($HTTP_GET_VARS)) {
	    if (isset($BF_USAGE_BY_NAME[$key])) {
		$vfl[$key] = $val;
		//echo "Accepted key = ".$key." val = $val<BR>";
	    } else {
		//echo "Rejected key = ".$key." val = $val<BR>";
	    }
	}

    }
    return($vfl);
}

function bug_check_empty_fields($field_array) {

    /*
      Check whether empty values are allowed for the bug fields
      Params:
      field_array: associative array of field_name -> value
      */
    global $feedback;

    $ret=1;
    reset($field_array);
    while ( list($key, $val) = each($field_array)) {
	if ( ($val == '') && !bug_data_is_empty_ok($key)) {
	    $ret=0;
	    $feedback .= "<BR>'".bug_data_get_label($key)."' field must not be empty";
	}
    }

    return($ret);
}

function bug_canned_response_box ($group_id,$name='canned_response') {
	if (!$group_id) {
		return 'ERROR - No group_id';
	} else {
		$result=bug_data_get_canned_responses($group_id);
		return html_build_select_box ($result,$name);
	}
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

function show_buglist ($result,$offset,$total_rows,$field_arr,$title_arr,
		       $width_arr,$url,$nolink) {
    global $sys_datefmt,$group_id,$PHP_SELF,$chunksz;

    /*
      Accepts a result set from the bugs table. Should include all columns from
      the table, and it should be joined to USER to get the user_name.
    */
    $rows=db_numrows($result);

    // Build the list of links to use for column headings
    // Used to trigger sort on that column
    if ($url) {
	$links_arr = array();
	while (list(,$field) = each($field_arr)) {
	    $links_arr[] = $url.'&order='.$field.'#results';
	}
    }

   /*
      Show extra rows for <-- Prev / Next -->
    */	
    $nav_bar ='<table width= "100%"><tr>';
    $nav_bar .= '<td width="20%" align ="left">';

    // If all bugs on screen so no prev/begin pointer at all
    if ($total_rows > $chunksz) {
	if ($offset > 0) {
	    $nav_bar .=
	    '<A HREF="'.$url.'&offset=0#results"><B><< Begin</B></A>'.
	    '&nbsp;&nbsp;&nbsp;&nbsp;'.
	    '<A HREF="'.$url.'&offset='.($offset-$chunksz).
	    '#results"><B>< Previous '.$chunksz.'</B></A></td>';
	} else {
	    $nav_bar .=
		'<font color="Gray">&lt;&lt; Begin&nbsp;&nbsp;&lt; Previous '.$chunksz.'</font>';
	}
    }

    $nav_bar .= '</td>';
    
    $offset_last = min($offset+$chunksz-1, $total_rows-1);

    $nav_bar .= '<td width= "60% " align = "center">Items '.($offset+1).' - '.
	($offset_last+1)."</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
	if ( ($offset+$chunksz) < $total_rows ) {

	    $offset_end = ($total_rows - ($total_rows % $chunksz));
	    if ($offset_end == $total_rows) { $offset_end -= $chunksz; }

	    $nav_bar .= 
		'<A HREF="'.$url.'&offset='.($offset+$chunksz).
		'#results"><B>Next '.$chunksz.' &gt;</B></A>'.
		'&nbsp;&nbsp;&nbsp;&nbsp;'.
		'<A HREF="'.$url.'&offset='.($offset_end).
		'#results"><B>End &gt;&gt;</B></A></td>';
	} else {
	    $nav_bar .= 
		'<font color="Gray">Next '.$chunksz.
		' &gt;&nbsp;&nbsp;End &gt;&gt;</font>';
	}
    }
    $nav_bar .= '</td>';
    $nav_bar .="</tr></table>\n";
 
    echo $nav_bar;
    echo html_build_list_table_top ($title_arr,$links_arr);

    //see if the bugs are too old - so we can highlight them
    $nb_of_fields = count($field_arr);

    for ($i=0; $i < $rows ; $i++) {

	echo '<TR BGCOLOR="'.get_priority_color(db_result($result, $i, 'severity')) .'">'."\n";

	for ($j=0; $j<$nb_of_fields; $j++) {
	    
	    $value = db_result($result, $i, $field_arr[$j]);
	    if ($width_arr[$j]) {
		$width = 'WIDTH="'.$width_arr[$j].'%"';
	    } else {
		$width = '';
	    }

	    if (bug_data_is_date_field($field_arr[$j]) ) {
		if ($value)
		    echo "<TD $width>".date($sys_datefmt,$value).'</TD>'."\n";
		else
		    echo "<TD align=\"middle\" $width>-</TD>\n";

	    } else if ($field_arr[$j] == 'bug_id') {

		if ($nolink) 
		    echo "<TD $width>$value</TD>\n";
		else
		    echo "<TD $width>".'<A HREF="/bugs/?func=detailbug&bug_id='.
			$value.'&group_id='.$group_id.'">'. 
			$value .'</A></TD>'."\n";

	    } else if ( bug_data_is_username_field($field_arr[$j]) ) {

		if ($nolink)
		    echo "<TD $width>$value</TD>\n";
		else
		    echo "<TD $width><A HREF=\"/users/$value\">$value</A></TD>\n";
		
	    } else if (bug_data_is_select_box($field_arr[$j])) {
		echo "<TD $width>". bug_data_get_cached_field_value($field_arr[$j], $group_id, $value) .'</TD>'."\n";

	    } else {
		echo "<TD $width>". $value .'&nbsp;</TD>'."\n";
	    }
	}
	echo "</tr>\n";
    }

    echo '</TABLE>';
    echo $nav_bar;
}

function mail_followup($bug_id,$more_addresses=false) {
    global $sys_datefmt,$feedback;
    /*
      Send a message to the person who opened this bug and the person it is assigned to - modified by jstidd on 1/30/01 to eliminate default user assigned to
    */

    $sql="SELECT * from bug WHERE bug_id='$bug_id'";

    $result=db_query($sql);

    if ($result && db_numrows($result) > 0) {
			
	$group_id = db_result($result,0,'group_id');

	// Generate the message preamble with all required
	// bug fields
	$body = 'Bug #'.$bug_id.', was updated on '.date($sys_datefmt,db_result($result,0,'date')).
	    "\nHere is a current snapshot of the bug.".
	    "\n\nProject: ".group_getname($group_id);

	$i=0;
	while ( $field_name = bug_list_all_fields() ) {

	    // if the field is a special field or if not used by his project 
	    // then skip it. Otherwise print it in ASCII format.
	    if ( !bug_data_is_special($field_name) &&
		 bug_data_is_used($field_name) ) {

		$field_value = db_result($result,0,$field_name);
		$body .= "\n".bug_field_display($field_name, $group_id,
					  $field_value,false,true,true,true);
		}
	}

	// Now display other special fields
	
	// Summary first. It is a special field because it is both displayed in the
	// title of the bug form and here as a text field

	$body .= "\n".bug_field_display('summary', $group_id,
			   db_result($result,0,'summary'),false,true,true,true).
	    "\n\n".bug_field_display('details', $group_id,
			   db_result($result,0,'details'),false,true,true,true);

	// Then output the history of bug details from newest to oldest
	$sql="SELECT user.email,user.user_name,bug_history.date,bug_history.old_value,bug_history.type ".
	    "FROM bug_history,user ".
	    "WHERE user.user_id=bug_history.mod_by ".
	    "AND bug_history.field_name='details' ".
	    "AND bug_history.bug_id='$bug_id' ORDER BY date DESC ";
	$result2=db_query($sql);
	$rows=db_numrows($result2);
	if ($result2 && $rows > 0) {
	    $body .= "\n\nFollow-Ups:";
	    $body .= "\n**********";
	    for ($i=0; $i<$rows;$i++) {
		$comment_type = db_result($result2,$i,'type');
		$body .= "\n\n-------------------------------------------------------";
		$body .= "\nDate: ".date($sys_datefmt,db_result($result2,$i,'date'));
		$body .= "\nBy: ".db_result($result2,$i,'user_name');
		$body .= "\n\nComment:";
		if ($comment_type != 100 ) {
		    $body .= ' ['.bug_data_get_value('comment_type_id',
			     $group_id, $comment_type)."]\n";
		} else {
		    $body .="\n";
		}
		$body .= util_unconvert_htmlspecialchars(db_result($result2,$i,'old_value'));

	    }
	}

	// Finally output the message trailer
	$body .= "\n\nFor detailed info, follow this link:";
	$body .= "\nhttp://$GLOBALS[sys_default_domain]/bugs/?func=detailbug&bug_id=$bug_id&group_id=".db_result($result,0,'group_id');


	// And send it to the submitter and the assignee (if any)
	$subject='[Bug #'.db_result($result,0,'bug_id').'] '.util_unconvert_htmlspecialchars(db_result($result,0,'summary'));

	$to = user_getemail(db_result($result,0,'submitted_by'));
	$assigned_to = db_result($result,0,'assigned_to');
	if ($assigned_to != 100) {
	    $to .= ','.user_getemail($assigned_to);
	}

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
			<H4>No Other Bugs are Dependent on This Bug</H4>';
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
		    $comment_type = db_result($result, $i, 'comment_type');
		    echo '<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>';
		    if ($comment_type != 'None') {
			echo '<B> [ '.$comment_type.' ]</B><BR>';
		    }
		    echo util_make_links(nl2br(db_result($result, $i, 'old_value'))).'</TD>'.
			'</TD>'.
			'<TD VALIGN="TOP">'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
			'<TD VALIGN="TOP">'.db_result($result, $i, 'user_name').'</TD></TR>';
		}
		echo '</TABLE>';
	} else {
		echo '
			<H4>No Followups Have Been Posted</H4>';
	}
}

function show_bughistory ($bug_id,$group_id) {
    /*
		show the bug_history rows that are relevant to this bug_id, excluding details
	*/
    global $sys_datefmt;
    $result=bug_data_get_history($bug_id);
    $rows=db_numrows($result);

    if ($rows > 0) {

	echo "\n".'<H3>Bug Change History</H3><P>';
	$title_arr=array();
	$title_arr[]='Field';
	$title_arr[]='Old Value';
	$title_arr[]='Date';
	$title_arr[]='By';

	echo html_build_list_table_top ($title_arr);

	for ($i=0; $i < $rows; $i++) {
	    $field = db_result($result, $i, 'field_name');
	    $value_id =  db_result($result, $i, 'old_value');

	    echo "\n".'<TR BGCOLOR="'. util_get_alt_row_color($i) .
		'"><TD>'.$field.'</TD><TD>';

	    if (bug_data_is_select_box($field)) {
		// It's a select box look for value in clear
		echo bug_data_get_value($field, $group_id, $value_id);
	    } else {
		// It's a text zone then display directly
		// For date fields do some special processing
		if ($field == 'close_date')
		    echo date($sys_datefmt,$value_id);
		else
		    echo $value_id;

	    }

	    echo '</TD>'.
		'<TD>'.date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
		'<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
	}
        echo '</TABLE>';
    
    } else {
        echo "\n".'<H4>No Changes Have Been Made to This Bug</H4>';
    }
}


function show_attached_files ($bug_id,$group_id) {

    global $sys_datefmt;

    /*
          show the files attached to this bug
       */

    $result=bug_data_get_attached_files($bug_id);
    $rows=db_numrows($result);

    if ($rows > 0) {

	$title_arr=array();
	$title_arr[]='Name';
	$title_arr[]='Description';
	$title_arr[]='Size';
	$title_arr[]='By';
	$title_arr[]='On';
	if (user_ismember($group_id,'B2')) {
	    $title_arr[]='Delete?';
	}

	echo html_build_list_table_top ($title_arr);

	for ($i=0; $i < $rows; $i++) {

	    $bug_file_id = db_result($result, $i, 'bug_file_id');
	    $submitted_by = db_result($result, $i, 'user_name');

	    echo "\n".'<TR BGCOLOR="'. util_get_alt_row_color($i).'">'.
		"<td><a href=\"/bugs/download.php?group_id=$group_id&bug_id=$bug_id&bug_file_id=$bug_file_id\">".
		db_result($result, $i, 'filename').'</a></td>'.
		'<td>'.db_result($result, $i, 'description').'</td>'.
		'<td align="center">'.intval(db_result($result, $i, 'filesize')/1024).' KB</td>'.
		'<td align="center"><a href="/users/'.$submitted_by.'">'.$submitted_by.'</a></td>'.
		'<td align="center">'.date($sys_datefmt,db_result($result, $i, 'date')).'</td>';

	    if (user_ismember($group_id,'B2')) {
	    echo "<td align=\"center\"><a href=\"$PHP_SELF?func=delete_file&group_id=$group_id&bug_id=$bug_id&bug_file_id=$bug_file_id\" ".
		'" onClick="return confirm(\'Delete this attachment?\')">'.
		'<IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></td></tr>';
	    }
	    
	}
        echo '</TABLE>';
    
    } else {
        echo "\n".'<H4>No files currently attached</H4>';
    }
}

function bug_delete_file($group_id=false,$bug_id=false,$bug_file_id=false) {

    // Make sure the attachment belongs to the group
    $res = db_query("SELECT bug_id from bug WHERE bug_id=$bug_id AND group_id=$group_id");
    if (db_numrows($res) <= 0) {
	$feedback .= "Bug #$bug_id doesn't belong to project";
	return;
    }

    // Now delete the attachment
    $res = db_query("DELETE FROM bug_file WHERE bug_id=$bug_id AND bug_file_id=$bug_file_id");
    if (db_numrows($res) <= 0) {
	$feedback .= "Error deleting attachment #$bug_file_id: ".db_error($res);
    } else {
	$feedback .= "File successfully deleted";
    }
}

function bug_attach_file($bug_id,$input_file,$input_file_name,$input_file_type,$input_file_size,$file_description) {
    global $feedback;

    $user_id = (user_isloggedin() ? user_getid(): 100);

    $data = addslashes(fread( fopen($input_file, 'r'), filesize($input_file)));
    if ((strlen($data) < 20) && (strlen($data) > 512000)) {
	$feedback .= " - File not attached: must be > 20 chars and < 512000 chars in length";
	return;
    }

    $sql = 'INSERT into bug_file (bug_id,submitted_by,date,description, file,filename,filesize,filetype) '.
    "VALUES ($bug_id,$user_id,'".time()."','".htmlspecialchars($file_description).
    "','$data','$input_file_name','$input_file_size','$input_file_type')";
    
    $res = db_query($sql);

    if (!$res) {
	$feedback .= ' - Error while attaching file: '.db_error($res);
    } else {
	$feedback .= '- File succesfully attached';
    }
}

/* 
   The ANY value is 0 in CodeX. The simple fact that
   ANY (0) is one of the value means it is Any even if there are
   other non zero values in the  array
*/
function bug_isvarany($var) {
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


// Check is a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function bug_add_sort_criteria($criteria_list, $order, $msort)
{
    //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";

    if ($criteria_list) {
	$arr = explode(',',$criteria_list);
	$i = 0;
	while (list(,$attr) = each($arr)) {
	    preg_match("/\s*([^<>]*)([<>]*)/", $attr,$match);
	    list(,$mattr,$mdir) = $match;
	    //echo "<br>DBG \$mattr=$mattr,\$mdir=$mdir";
	    if ($mattr == $order) {
		if ( ($mdir == '>') || (!isset($mdir)) ) {
		    $arr[$i] = $order.'<';
		} else {
		    $arr[$i] = $order.'>';
		}
		$found = true;
	    }
	    $i++;
	}
    }

    if (!$found) {
	if (!$msort) { unset($arr); }
	if ( ($order == 'severity') || ($order == 'hours') || (bug_data_is_date_field($order)) ) {
	    // severity, effort and dates sorted in descending order by default
	    $arr[] = $order.'<';
	} else {
	    $arr[] = $order.'>';
	}
    }
    
    //echo "<br>DBG \$arr[]=".join(',',$arr);

    return(join(',', $arr));	

}

// Transform criteria list to SQL query (+ means ascending
// - is descending)
function bug_criteria_list_to_query($criteria_list)
{

    $criteria_list = str_replace('>',' ASC',$criteria_list);
    $criteria_list = str_replace('<',' DESC',$criteria_list);
    return $criteria_list;
}

// Transform criteria list to readable text statement
// $url must not contain the morder parameter
function bug_criteria_list_to_text($criteria_list, $url)
{

    if ($criteria_list) {

	$arr = explode(',',$criteria_list);

	while (list(,$crit) = each($arr)) {

	    $morder .= ($morder ? ",".$crit : $crit);
	    $attr = str_replace('>','',$crit);
	    $attr = str_replace('<','',$attr);

	    $arr_text[] = '<a href="'.$url.'&morder='.$morder.'#results">'.
		bug_data_get_label($attr).'</a><img src="/images/'.
		((substr($crit, -1) == '<') ? 'dn' : 'up').
		'_arrow.png" border="0">';
	}
    }

    return join(' > ',$arr_text);
}

function bug_build_match_expression($field, &$to_match)
{

    // First get the field type
    $res = db_query("SHOW COLUMNS FROM bug LIKE '$field'");
    $type = db_result($res,0,'Type');

    //echo "<br>DBG '$field' field type = $type";

    if (preg_match('/text|varchar|blob/i', $type)) {

	// If it is sourrounded by /.../ the assume a regexp
	// else transform into a series of LIKE %word%
	if (preg_match('/\/(.*)\//', $to_match, $matches))
	    $expr = "$field RLIKE '".$matches[1]."' ";
	else {
	    $words = preg_split('/\s+/', $to_match);
	    reset($words);
	    while ( list($i,$w) = each($words)) {
		//echo "<br>DBG $i, $w, $words[$i]";
		$words[$i] = "$field LIKE '%$w%'";
	    }
	    $expr = join(' AND ', $words);
	}

    } 
    else if (preg_match('/int/i', $type)) {

	// If it is sourrounded by /.../ the assume a regexp
	// else assume an equality
	if (preg_match('/\/(.*)\//', $to_match, $matches)) {
	    $expr = "$field RLIKE '".$matches[1]."' ";
	} else {
	    $int_reg = '[+\-]*[0-9]+';
	    if (preg_match("/\s*(<|>|>=|<=)\s*($int_reg)/", $to_match, $matches)) {
		// It's < or >,  = and a number then use as is
		$matches[2] = (string)((int)$matches[2]);
		$expr = "$field ".$matches[1]." '".$matches[2]."' ";
		$to_match = $matches[1].' '.$matches[2];

	    } 
	    else if (preg_match("/\s*($int_reg)\s*-\s*($int_reg)/", $to_match, $matches)) {
		// it's a range number1-number2
		$matches[1] = (string)((int)$matches[1]);
		$matches[2] = (string)((int)$matches[2]);
		$expr = "$field >= '".$matches[1]."' AND $field <= '". $matches[2]."' ";
		$to_match = $matches[1].'-'.$matches[2];

	    }
	    else if (preg_match("/\s*($int_reg)/", $to_match, $matches)) {
		// It's a number so use  equality
		$matches[1] = (string)((int)$matches[1]);
		$expr = "$field = '".$matches[1]."'";
		$to_match = $matches[1];

	    }
	    else {
		// Invalid syntax - no condition
		$expr = '1';
		$to_match = '';
	    }
	}
		     
    } 
    else if (preg_match('/float/i', $type)) {

	// If it is sourrounded by /.../ the assume a regexp
	// else assume an equality
	if (preg_match('/\/(.*)\//', $to_match, $matches)) {
	    $expr = "$field RLIKE '".$matches[1]."' ";
	} else {
	    $flt_reg = '[+\-0-9.eE]+';

	    if (preg_match("/\s*(<|>|>=|<=)\s*($flt_reg)/", $to_match, $matches)) {
		// It's < or >,  = and a number then use as is
		$matches[2] = (string)((float)$matches[2]);
		$expr = "$field ".$matches[1]." '".$matches[2]."' ";
		$to_match = $matches[1].' '.$matches[2];

	    }
	    else if (preg_match("/\s*($flt_reg)\s*-\s*($flt_reg)/", $to_match, $matches) ) {
		// it's a range number1-number2
		$matches[1] = (string)((float)$matches[1]);
		$matches[2] = (string)((float)$matches[2]);
		$expr = "$field >= '".$matches[1]."' AND $field <= '". $matches[2]."' ";
		$to_match = $matches[1].'-'.$matches[2];

	    }
	    else if (preg_match("/\s*($flt_reg)/", $to_match, $matches)) {

		// It's a number so use  equality
		$matches[1] = (string)((float)$matches[1]);
		$expr = "$field = '".$matches[1]."'";
		$to_match = $matches[1];
	    }
	    else {
		// Invalid syntax - no condition
		$expr = '1';
		$to_match = '';
	    }
	}
	
    } else {
	// All the rest (???) use =
	$expr = "$field = '$to_match'";
    }

    //echo "<br>DBG expr to match for '$field' = $expr";
    return ' ('.$expr.') ';

}
?>
