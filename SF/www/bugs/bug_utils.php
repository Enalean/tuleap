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

    $size_hr = 300;
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
        if ( $params['create_task'] != '' ) {
            echo ' | <b><A HREF="/pm/task.php?group_id='.$group_id.'&group_project_id=0&func=addtask&summary='.urlencode($params['summary']).'&details='.urlencode($params['details']).'&assigned_to='.urlencode($params['assigned_to']).'&hours='.urlencode($params['hours']).'&bug_id='.urlencode($params['bug_id']).'">Create Task</A></b>';
            $size_hr = 345;
        }
		echo ' | <A HREF="/bugs/reporting/?group_id='.$group_id.'">Reporting</A>';
	}
	echo ' | <A HREF="/bugs/admin/?group_id='.$group_id.'">Admin</A></B>';
	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo ' <hr width="'.$size_hr.'" size="1" align="left" noshade>';
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
    if ( $params['create_task'] != '' ) {
        echo ' | <b><A HREF="/bugs/admin/field_values.php?group_id='.$group_id.'">Create Task</A></b>';
    }
    echo ' | <b><A HREF="/bugs/admin/reports.php?group_id='.$group_id.'">Bug Reports</A></b>';
    echo ' | <b><A HREF="/bugs/admin/notification_settings.php?group_id='.$group_id.'">Notification Settings</A></b>';
    echo ' | <b><A HREF="/bugs/admin/other_settings.php?group_id='.$group_id.'">Other Settings</A></b>';
    if ($params['help']) {
	echo ' | '.help_button($params['help'],false,'Help');
    }
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

function bug_field_label_display($field_name, $group_id,$break=false,$ascii=false) {
    $output = bug_data_get_label($field_name).': ';
    if (!$ascii) 
	$output = '<B>'.$output.'</B>';
    if ($break) 
	$output .= ($ascii?"\n":'<BR>');
    else
	$output .= ($ascii? ' ':'&nbsp;');
    return $output;
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
	$output = bug_field_label_display($field_name,$group_id,$break,$ascii);
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
	    $output .= ( ($value == 0) ? '' : format_date($sys_datefmt,$value));
	else
	    if ($ro) {
		$output .= format_date($sys_datefmt,$value);
	    } else {
		$output .= bug_field_date($field_name,
					  (($value == 0) ? '' : format_date("Y-m-j",$value,'')));
	    }
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

    global $theme;

    // CAUTION!!!! The Javascript below assumes that the date always appear
    // in a form called 'bug_form'
    if ($ro)
	$html = $value;
    else {
	if (!$size || !$maxlength)
	    list($size, $maxlength) = bug_data_get_display_size($field_name);

	$html = '<INPUT TYPE="text" name="'.$field_name.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$value.'">'.
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'\', document.bug_form.'.$field_name.'.value,\''.$theme.'\');">'.
	'<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="Click Here to Pick up a date"></a>';
    }
    return($html);

}

function bug_multiple_field_date($field_name,$date_begin='',$date_end='',$size=0,$maxlength=0,$ro=false) {

    global $theme;

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
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'\', document.bug_form.'.$field_name.'.value,\''.$theme.'\');">'.
	'<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="Click Here to Pick up start date"></a><br>'.
	'End :<INPUT TYPE="text" name="'.$field_name.'_end'.
	'" size="'.$size.'" MAXLENGTH="'.$maxlength.'" VALUE="'.$date_end.'">'.
	'<a href="javascript:show_calendar(\'document.bug_form.'.$field_name.'_end\', document.bug_form.'.$field_name.'_end.value,\''.$theme.'\');">'.
	'<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="Click Here to Pick up end date"></a>';
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
	'" ROWS="'.$rows.'" COLS="'.$cols.'" WRAP="SOFT">'.$value.'</TEXTAREA>';
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

    $bad_fields = array();
    reset($field_array);
    while ( list($key, $val) = each($field_array)) {
	$is_empty = (bug_data_is_select_box($key) ? ($val==100) : ($val==''));
	if ( $is_empty && !bug_data_is_empty_ok($key)) {
	    $bad_fields[] = bug_data_get_label($key);
	}
    }

    if (count($bad_fields) > 0) {
	$feedback = 'Missing fields: '.join(', ',$bad_fields).
	    '<p>Empty values for the above listed field(s) are not allowed. Click on the'.
	    'Back arrow of your browser and try again';
	return false;
    } else {
	return true;
    }

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
		'<span class="disable">&lt;&lt; Begin&nbsp;&nbsp;&lt; Previous '.$chunksz.'</span>';
	}
    }

    $nav_bar .= '</td>';
    
    $offset_last = min($offset+$chunksz-1, $total_rows-1);

    $nav_bar .= '<td width= "60% " align = "center" class="small">Items '.($offset+1).' - '.
	($offset_last+1)."</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
	if ( ($offset+$chunksz) < $total_rows ) {

	    $offset_end = ($total_rows - ($total_rows % $chunksz));
	    if ($offset_end == $total_rows) { $offset_end -= $chunksz; }

	    $nav_bar .= 
		'<A HREF="'.$url.'&offset='.($offset+$chunksz).
		'#results" class="small"><B>Next '.$chunksz.' &gt;</B></A>'.
		'&nbsp;&nbsp;&nbsp;&nbsp;'.
		'<A HREF="'.$url.'&offset='.($offset_end).
		'#results" class="small"><B>End &gt;&gt;</B></A></td>';
	} else {
	    $nav_bar .= 
		'<span class="disable">Next '.$chunksz.
		' &gt;&nbsp;&nbsp;End &gt;&gt;</span>';
	}
    }
    $nav_bar .= '</td>';
    $nav_bar .="</tr></table>\n";
 
    echo $nav_bar;
    echo html_build_list_table_top ($title_arr,$links_arr);

    //see if the bugs are too old - so we can highlight them
    $nb_of_fields = count($field_arr);

    for ($i=0; $i < $rows ; $i++) {

	echo '<TR class="'.get_priority_color(db_result($result, $i, 'severity')) .'">'."\n";

	for ($j=0; $j<$nb_of_fields; $j++) {
	    
	    $value = db_result($result, $i, $field_arr[$j]);
	    if ($width_arr[$j]) {
		$width = 'WIDTH="'.$width_arr[$j].'%"';
	    } else {
		$width = '';
	    }
	    $width .= ' class="small"';

	    if (bug_data_is_date_field($field_arr[$j]) ) {
		if ($value)
		    echo "<TD $width>".format_date($sys_datefmt,$value).'</TD>'."\n";
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
		    echo "<TD $width>".util_user_link($value)."</TD>\n";
		
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

function bug_build_notification_matrix($user_id) {

    // Build the notif matrix indexed with roles and events labels (not id)
    $res_notif = bug_data_get_notification_with_labels($user_id);
    while ($arr = db_fetch_array($res_notif)) {
	$arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
    }
    return $arr_notif;
}


function bug_check_notification($user_id, $role, $changes=false) {

    $send = false;
    $arr_notif = bug_build_notification_matrix($user_id);
    if (!$arr_notif) { return true; }

    //echo "==== DBG Checking Notif. for $user_id (role=$role)<br>";
    $user_name = user_getname($user_id);

    //----------------------------------------------------------
    // If it's a new bug only (changes is false) check the NEW_BUG event and
    // ignore all other events
    if ($changes==false) {
	if ($arr_notif[$role]['NEW_BUG']) {
	    //echo "DBG NEW_BUG notified<br>";
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
    //Check: CLOSED  (The bug is closed)
    // Rk: this one has precedence over PSS_CHANGE. So notify even if PSS_CHANGE
    // says no.
    if ($arr_notif[$role]['CLOSED'] && ($changes['status_id']['add'] == 'Closed')) {
	//echo "DBG CLOSED bug notified<br>";
	return true;
    }

    //----------------------------------------------------------
    //Check: PSS_CHANGE  (Priority,Status,Severity changes)
    if ($arr_notif[$role]['PSS_CHANGE'] && 
	(isset($changes['priority']) || isset($changes['status_id']) || isset($changes['severity'])) ) {
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
	(($changes['submitted_by']['add'] == $user_name) || ($changes['submitted_by']['del'] == $user_name)) &&
	($role == 'SUBMITTER') ) {
	//echo "DBG ROLE_CHANGE for submitter notified<br>";
	return true;
    }

    if ($arr_notif['ASSIGNEE']['ROLE_CHANGE'] &&
	(($changes['assigned_to']['add'] == $user_name) || ($changes['assigned_to']['del'] == $user_name)) &&
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
    unset($changes['severity']);
    unset($changes['status_id']);
    unset($changes['CC']);
    unset($changes['assigned_to']);
    unset($changes['submitted_by']);
    if ($arr_notif[$role]['ANY_OTHER_CHANGE'] && count($changes)) {
	//echo "DBG ANY_OTHER_CHANGE notified<br>";
	return true;
    }

    // Sorry, no notification...
    //echo "DBG No notification!!<br>";
    return false;
}

function bug_build_notification_list($bug_id, $group_id, $changes) {

    $sql="SELECT assigned_to, submitted_by from bug WHERE bug_id='$bug_id'";
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
    $user_id = db_result($res_as,0,'submitted_by');
    if ($user_id != 100) {
	if (bug_check_notification($user_id, 'SUBMITTER', $changes)) {
	    $user_ids[$user_id] = true;
	}
    }

    // check assignee  notification preferences
    $user_id = db_result($res_as,0,'assigned_to');
    if ($user_id != 100) {
	if (!$user_ids[$user_id] && bug_check_notification($user_id, 'ASSIGNEE', $changes)) {
	    $user_ids[$user_id] = true;
	}
    }

    // check old assignee  notification preferences if assignee was just changed
    $user_name = $changes['assigned_to']['del'];
    if ($user_name) {
	$res_oa = user_get_result_set_from_unix($user_name);
	$user_id = db_result($res_oa,0,'user_id');
	if (!$user_ids[$user_id] && bug_check_notification($user_id, 'ASSIGNEE', $changes)) {
	    $user_ids[$user_id] = true;
	}
    }
    
    // check all CC 
    // a) check all the people in the current CC list
    // b) check the CC that has just been removed if any and see if she
    // wants to be notified as well
    // if the CC indentifier is an email address then notify in any case
    // because this user has no personal setting
    $res_cc = bug_data_get_cc_list($bug_id);
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
	    if (!$user_ids[$user_id] && bug_check_notification($user_id, 'CC', $changes)) {
		$user_ids[$user_id] = true;
	    }
	}
    } // while


    // check all commenters
    $res_com = bug_data_get_commenters($bug_id);
    if (db_numrows($res_com) > 0) {
	while ($row = db_fetch_array($res_com)) {
	    $user_id = $row['mod_by'];
	    if (!$user_ids[$user_id] && bug_check_notification($user_id, 'COMMENTER', $changes)) {
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

function bug_mail_followup($bug_id,$more_addresses=false,$changes=false) {
    global $sys_datefmt,$feedback;
    /*
      Send a message to the person who opened this bug and the person it is assigned to - 
      modified by jstidd on 1/30/01 to eliminate default user assigned to
    */

    $sql="SELECT * from bug WHERE bug_id='$bug_id'";

    $result=db_query($sql);
    $bug_href = "http://$GLOBALS[sys_default_domain]/bugs/?func=detailbug&bug_id=$bug_id&group_id=".db_result($result,0,'group_id');

    if ($result && db_numrows($result) > 0) {
			
	$group_id = db_result($result,0,'group_id');
	$fmt = "%-40s";

	// bug fields
	// Generate the message preamble with all required
	// bug fields - Changes first if there are some.
	if ($changes) {
	    $body = "\n=================== BUG #".$bug_id.
		": LATEST MODIFICATIONS ==================\n".$bug_href."\n\n".
		format_bug_changes($changes)."\n\n\n\n";
	}

	$body .= "=================== BUG #".$bug_id.
	    ": FULL BUG SNAPSHOT ===================\n".
	    ($changes ? '':$bug_href)."\n\n";
    
	// Some special field first (group, submitted by/on)
	$body .= sprintf($fmt.$fmt."\n", 
			 'Submitted by: '.user_getname(db_result($result,0,'submitted_by')),
			 'Project: '.group_getname($group_id) );
	$body .= 'Submitted on: '.format_date($sys_datefmt,db_result($result,0,'date'))."\n";

	// All other regular fields now		 
	$i=0;
	while ( $field_name = bug_list_all_fields() ) {

	    // if the field is a special field or if not used by his project 
	    // then skip it. Otherwise print it in ASCII format.
	    if ( !bug_data_is_special($field_name) &&
		 bug_data_is_used($field_name) ) {

		$field_value = db_result($result,0,$field_name);
		$body .= sprintf($fmt,bug_field_display($field_name, $group_id,
					  $field_value,false,true,true,true));
		$i++;
		$body .= ($i % 2 ? '':"\n");
	    }
	}
	$body .= ($i % 2 ? "\n":'');

	// Now display other special fields
	
	// Summary first. It is a special field because it is both displayed in the
	// title of the bug form and here as a text field

	$body .= "\n".bug_field_display('summary', $group_id,
			   db_result($result,0,'summary'),false,true,true,true).
	    "\n\n".bug_field_display('details', $group_id,
			   db_result($result,0,'details'),false,true,true,true);

	// Then output the history of bug details from newest to oldest
	$body .= "\n\n".format_bug_details($bug_id, $group_id, true);

	// Then output the CC list
	$body .= "\n\n".format_bug_cc_list($bug_id, $group_id, true);

	// Then output the history of bug details from newest to oldest
	$body .= "\n\n".format_bug_attached_files($bug_id, $group_id, true);

	// Finally output the message trailer
	$body .= "\n\nFor detailed info, follow this link:";
	$body .= "\n".$bug_href;


	// See who is going to receive the notification. Plus append any other email 
	// given at the end of the list.
	$arr_addresses = bug_build_notification_list($bug_id,$group_id,$changes);
	$to = join(',',$arr_addresses);

	if ($more_addresses) {
	    $to .= ($to ? ',':'').$more_addresses;
	}

	//echo "DBG Sending email to: $to<br";

	$more='From: noreply@'.$GLOBALS['sys_default_domain'];
        $subject='[Bug #'.db_result($result,0,'bug_id').'] '.util_unconvert_htmlspecialchars(db_result($result,0,'summary'));


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
			<TR class="'. util_get_alt_row_color($i) .'">
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

function format_bug_details ($bug_id, $group_id, $ascii=false) {

    /*
      Format the details rows from bug_history
      */
    global $sys_datefmt;
    $result=bug_data_get_followups ($bug_id);
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
	
	$comment_type = db_result($result, $i, 'comment_type');
	if ($comment_type == 'None') 
	    $comment_type = '';
	else
	    $comment_type = '['.$comment_type.']';
	
	if ($ascii) {
	    $fmt = "\n\n-------------------------------------------------------\n".
		"Date: %-30sBy: %s\n".
		($comment_type ? "%s\n%s" : '%s%s');
	} else {
	    $fmt = "\n".'<tr class="%s"><td><b>%s</b><BR>%s</td>'.
		'<td valign="top">%s</td><td valign="top">%s</td></tr>';
	}
	
	// I wish we had sprintf argument swapping in PHP3 but
	// we don't so do it the ugly way...
	if ($ascii) {
	    $out .= sprintf($fmt,
			    format_date($sys_datefmt,db_result($result, $i, 'date')),
			    db_result($result, $i, 'user_name'),
			    $comment_type,
			    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
			    );
	} else {
	    $out .= sprintf($fmt,
			    util_get_alt_row_color($i),
			    $comment_type,
			    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id),
			    format_date($sys_datefmt,db_result($result, $i, 'date')),
			    db_result($result, $i, 'user_name'));
	}
    }

    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");

    return($out);
}

function show_bug_details ($bug_id,$group_id, $ascii=false) {
    echo format_bug_details($bug_id, $group_id, $ascii);
}

function format_bug_changes($changes) {

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
	$out_com = "\n\n------------------ Additional Follow-up Comments ----------------------------\n";
	if ($changes['details']['type'] != 'None') {
	    $out_com .= '['.$changes['details']['type']."]\n";
	}
	$out_com .= util_unconvert_htmlspecialchars($changes['details']['add']);
	unset($changes['details']);
    }

    //Process special cases first: bug file attachment
    if ($changes['attach']) {
	$out_att = "\n\n------------------ Additional Bug Attachment  ----------------------------\n";
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

	$label = bug_data_get_label($field);
	if (!$label) { $label = $field; }
	$out .= sprintf($fmt, $label, $h['del'],$h['add']);
    }
    if ($out) {
	$out = "\n\n".sprintf($fmt,'What    ','Removed','Added').
	"---------------------------------------------------------------------------\n".$out;
    }

    return($out_hdr.$out.$out_com.$out_att);

}


function show_bughistory ($bug_id,$group_id) {
    /*
		show the bug_history rows that are relevant to this bug_id, excluding details
	*/
    global $sys_datefmt;
    $result=bug_data_get_history($bug_id);
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
		'"><TD>'.bug_data_get_label($field).'</TD><TD>';

	    if (bug_data_is_select_box($field)) {
		// It's a select box look for value in clear
		echo bug_data_get_value($field, $group_id, $value_id);
	    } else if (bug_data_is_date_field($field)) {
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
        echo "\n".'<H4>No Changes Have Been Made to This Bug</H4>';
    }
}


function format_bug_attached_files ($bug_id,$group_id,$ascii=false) {

    global $sys_datefmt;

    /*
          show the files attached to this bug
       */

    $result=bug_data_get_attached_files($bug_id);
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
	    (user_ismember($group_id,'B2') ? '<td align="center">%s</td>':'').'</tr>';
    }

    // Loop throuh the attached files and format them
    for ($i=0; $i < $rows; $i++) {

	$bug_file_id = db_result($result, $i, 'bug_file_id');
	$href = "/bugs/download.php?group_id=$group_id&bug_id=$bug_id&bug_file_id=$bug_file_id";

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
			    "<a href=\"$PHP_SELF?func=delete_file&group_id=$group_id&bug_id=$bug_id&bug_file_id=$bug_file_id\" ".
			    '" onClick="return confirm(\'Delete this attachment?\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A>');
	}
    }

    // final touch...
    $out .= ($ascii ? "\n" : "</TABLE>");

    return($out);

}

function show_bug_attached_files ($bug_id,$group_id, $ascii=false) {
    echo format_bug_attached_files ($bug_id,$group_id, $ascii);
}

function format_bug_cc_list ($bug_id,$group_id, $ascii=false) {

    global $sys_datefmt;

    /*
          show the files attached to this bug
       */

    $result=bug_data_get_cc_list($bug_id);
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
	$out .= "------------------------------------+-----------------------------\n";
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
	$bug_cc_id = db_result($result, $i, 'bug_cc_id');

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
	    // a) current user is a bug admin
	    // b) then CC name is the current user 
	    // c) the CC email address matches the one of the current user
	    // d) the current user is the person who added a gieven name in CC list
	    if ( user_ismember($group_id,'B2') ||
		(user_getname(user_getid()) == $email) ||  
		(user_getemail(user_getid()) == $email) ||
		(user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
		$html_delete = "<a href=\"$PHP_SELF?func=delete_cc&group_id=$group_id&bug_id=$bug_id&bug_cc_id=$bug_cc_id\" ".
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

function show_bug_cc_list ($bug_id,$group_id, $ascii=false) {
    echo format_bug_cc_list ($bug_id,$group_id, $ascii);
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

function bug_attach_file($bug_id,$group_id,$input_file,$input_file_name,$input_file_type,$input_file_size,$file_description, &$changes) {
    global $feedback;

    $user_id = (user_isloggedin() ? user_getid(): 100);

    $data = addslashes(fread( fopen($input_file, 'r'), filesize($input_file)));
    if ((strlen($data) < 20) || (strlen($data) > 512000)) {
	$feedback .= " - File not attached: must be > 20 chars and < 512000 chars in length";
	return false;
    }

    $sql = 'INSERT into bug_file (bug_id,submitted_by,date,description, file,filename,filesize,filetype) '.
    "VALUES ($bug_id,$user_id,'".time()."','".htmlspecialchars($file_description).
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
	    "/bugs/download.php?group_id=$group_id&bug_id=$bug_id&bug_file_id=$file_id";
	return true;
    }
}

function bug_exist_cc($bug_id,$cc) {
    $sql = "SELECT bug_cc_id FROM bug_cc WHERE bug_id='$bug_id' AND email='$cc'";
    $res = db_query($sql);
    return (db_numrows($res) >= 1);
}

function bug_insert_cc($bug_id,$cc,$added_by,$comment,$date) {
    $sql = "INSERT INTO bug_cc (bug_id,email,added_by,comment,date) ".
	"VALUES ('$bug_id','$cc','$added_by','$comment','$date')";
    $res = db_query($sql);
    return ($res);

}

function bug_add_cc($bug_id,$group_id,$email,$comment,&$changes) {
    global $feedback;

    $user_id = (user_isloggedin() ? user_getid(): 100);

    $arr_email = util_split_emails($email);
    $date = time();
    $ok = true;
    $changed = false;
    while (list(,$cc) = each($arr_email)) {
	// Add this cc only if not there already
	if (!bug_exist_cc($bug_id,$cc)) {
	    $changed = true;
	    $res = bug_insert_cc($bug_id,$cc,$user_id,$comment,$date);
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

function bug_delete_cc($group_id=false,$bug_id=false,$bug_cc_id=false,&$changes) {
    global $feedback;

    // If both bug_id and bug_cc_id are given make sure the cc belongs 
    // to this bug (it's a bit paranoid but...)
    if ($bug_id) {
	$res1 = db_query("SELECT bug_id,email from bug_cc WHERE bug_cc_id='$bug_cc_id'");
	if ((db_numrows($res1) <= 0) || (db_result($res1,0,'bug_id') != $bug_id) ) {
	    $feedback .= " - Error CC ID $bug_cc_id doesn't belong to bug ID";
	    return false;
	}
    }

    // Now delete the CC address
    $res2 = db_query("DELETE FROM bug_cc WHERE bug_cc_id='$bug_cc_id'");
    if (!$res2) {
	$feedback .= " - Error deleting CC ID $bug_cc_id: ".db_error($res2);
	return false;
    } else {
	$feedback .= " - CC Removed";
	$changes['CC']['del'] = db_result($res1,0,'email');
	return true;
    }
}


/* 
   The ANY value is 0. The simple fact that
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
		bug_data_get_label($attr).'</a><img src="'.util_get_dir_image_theme().
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
