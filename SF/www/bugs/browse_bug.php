<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// $Id$
//
//
//	Bug Tracker originally by Tim Perdue 11/99
//	Very Heavy rewrite by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//


// Number of search criteria (boxes) displayed in one row
$fields_per_line=5;

// Number of bugs displayed on screen in one chunk.
// Default 50
if (!$chunksz) { $chunksz = 50; }

// Make sure offset is defined and has a correct value
if (!$offset || $offset < 0) { $offset=0; }


/*  ==================================================
    Get the list of bug fields used in the form (they are in the URL - GET method)
    and then build the preferences array accordingly
    Exclude the group_id parameter// Extract the list of bug fields
 ================================================== */

$prefs = bug_extract_field_list(false);
unset($prefs['group_id']);

/* ==================================================
   Make sure all URL arguments are captured as array. For simple
   search they'll be arrays with only one element at index 0 (this
   will avoid to deal with scalar in simple search and array in 
   advanced which would greatly complexifies the code)
 ================================================== */
while (list($field,$value_id) = each($prefs)) {
    if (!is_array($value_id)) {
	unset($prefs[$field]);
	$prefs[$field][] = $value_id;
	//echo '<br> DBG Setting $prefs['.$field.'] [] = '.$value_id;
    } else {
	//echo '<br> DBG $prefs['.$field.'] = ('.implode(',',$value_id).')';
    }

    if (bug_data_is_date_field($field)) {
	if ($advsrch) {
	    $field_end = $field.'_end';
	    $prefs[$field_end] = $$field_end;
	    //echo '<br> DBG Setting $prefs['.$field.'_end]= '.$prefs[$field.'_end'];
	} else {
	    $field_op = $field.'_op';
	    $prefs[$field_op] = $$field_op;
	    if (!$prefs[$field_op])
		$prefs[$field_op] = '>';
	    //echo '<br> DBG Setting $prefs['.$field.'_op]= '.$prefs[$field.'_op'];
	}
    }
}


/* ==================================================
   Memorize order by field as a user preference if explicitly specified.
   
   $morder = comma separated list of sort criteria followed by - for
     DESC and + for ASC order
   $order = last sort criteria selected in the UI
   $msort = 1 if multicolumn sort activated.
  ================================================== */
//echo "<br>DBG \$morder at top: [$morder ]";
//   if morder not defined then reuse the one in preferences
if (user_isloggedin() && !isset($morder)) {
    $morder = user_get_preference('bug_browse_order'.$group_id);
}

if (isset($order)) {

    if ($order != '') {
	// Add the criteria to the list of existing ones
	$morder = bug_add_sort_criteria($morder, $order, $msort);
    } else {
	// reset list of sort criteria
	$morder = '';
    }
}

if (isset($morder)) {

    if (user_isloggedin()) {
	if ($morder != user_get_preference('bug_browse_order'.$group_id))
	    user_set_preference('bug_browse_order'.$group_id, $morder);
    }

    if ($morder != '') {
	$order_by = ' ORDER BY '.bug_criteria_list_to_query($morder);
    }
}

//echo "<BR> DBG Order by = $order_by";


/* ==================================================
  If the report type is not defined then get it from the user preferences.
  If it is set then update the user preference.  Also initialize the
  bug report structures.
  ================================================== */
if (user_isloggedin()) {
    if (!isset($report_id)) {
	$report_id = user_get_preference('bug_browse_report'.$group_id);
    } else {
	if ($report_id != user_get_preference('bug_browse_report'.$group_id))
	    user_set_preference('bug_browse_report'.$group_id, $report_id);
    }
}

// If still not defined then force it to system 'Default' report
if (!$report_id) { $report_id=100; }

bug_report_init($group_id, $report_id);



/* ==================================================
  Now see what type of bug set is requested (set is one of none, 
  'my', 'open', 'custom'). 
    - if no set is passed in, see if a preference was set ('custom' set).
    - if no preference and logged in then use 'my' set
    - if no preference and not logged in the use 'open' set
     (Prefs is a string of the form  &field1[]=value_id1&field2[]=value_id2&.... )
  ================================================== */
if (!$set) {

    if (user_isloggedin()) {

	$custom_pref=user_get_preference('bug_brow_cust'.$group_id);

	if ($custom_pref) {
	    $pref_arr = explode('&',substr($custom_pref,1));
	    while (list(,$expr) = each($pref_arr)) {
		// Extract left and right parts of the assignment
		// and remove the '[]' array symbol from the left part
		list($field,$value_id) = explode('=',$expr);
		$field = str_replace('[]','',$field);
		if ($field == 'advsrch') 
		    $advsrch = $value_id;
		else if ($field == 'msort')
		    $msort = $value_id;
		else if ($field == 'chunksz')
		    $chunksz = $value_id;
		else if ($field == 'report_id')
		    $report_id = $value_id;
		else
		    $prefs[$field][] = $value_id;

		//echo '<br>DBG restoring prefs : $prefs['.$field.'] []='.$value_id;
	    }
	    $set='custom';

	} else {
	    $set='my';
	}

    } else {
	$set='open';
    }
}


if ($set=='my') {
    /*
      My bugs - backwards compat can be removed 9/10
    */
    $prefs['status_id'][]=1;
    $prefs['assigned_to'][]=user_getid();

} else if ($set=='custom') {

    // Get the list of bug fields used in the form (they are in the URL - GET method)
    // and then build the preferences array accordingly
    // Exclude the group_id parameter
    reset($prefs);
    while (list($field,$arr_val) = each($prefs)) {
	while (list(,$value_id) = each($arr_val)) {
	    $pref_stg .= '&'.$field.'[]='.$value_id;
	}

	// build part of the HTML title of this page for more friendly bookmarking
	// Do not add the criteria in the header if value is "Any"
	if ($value_id != 0) {
	    $hdr .= ' By '.bug_data_get_label($field).': '.
		bug_data_get_value($field,$group_id,$value_id);
	}
    }
    $pref_stg .= '&advsrch='.$advsrch;
    $pref_stg .= '&msort='.$msort;
    $pref_stg .= '&chunksz='.$chunksz;
    $pref_stg .= '&report_id='.$report_id;
    
    if ($pref_stg != user_get_preference('bug_brow_cust'.$group_id)) {
	//echo "<br> DBG setting pref = $pref_stg";
	user_set_preference('bug_brow_cust'.$group_id,$pref_stg);
    }

} else {
    // Open bugs - backwards compat can be removed 9/10
    $prefs['status_id'][]=1;
}

/* ==================================================
   At this point make sure that all paramaters are defined
   as well as all the arguments that serves as selection criteria
   If not defined then defaults to ANY (0)
  ================================================== */
if (!isset($advsrch)) { $advsrch = 0; }
if (!isset($msort)) { $msort = 0; }
while ($field = bug_list_all_fields()) {
    // the select boxes for the bug DB search first
    if (bug_data_is_showed_on_query($field) &&
	bug_data_is_select_box($field) ) {
	if (!isset($prefs[$field])) $prefs[$field][] = 0;
    }
}

/* ==================================================
   Start building the SQL query (select and where clauses)
  ================================================== */

// Force the selection of severity because it is always shown as color code
$col_list = $lbl_list = array();
$select_count = 'SELECT count(*) AS count ';
$select = 'SELECT bug.severity ';
$from = 'FROM bug';
$where = 'WHERE bug.group_id='.$group_id.' ';
if (!$pv) { $limit = " LIMIT $offset,$chunksz";}


// prepare the where clause with the selection criteria given by the user
reset($prefs);
while (list($field,$value_id) = each($prefs)) { 

    // If the criteria is not in the field showed on query screen then 
    // skip it. This is a sanity check to make sure that the SQL
    // query we run actually matches the displayed search criteria
    if (!bug_data_is_showed_on_query($field)) { continue; }

    if (bug_data_is_select_box($field) && !bug_isvarany($prefs[$field]) ) {

	// Only select box criteria to where clause if argument is not ANY
	$where .= ' AND bug.'.$field.' IN ('.implode(',',$prefs[$field]).') ';

    } else if (bug_data_is_date_field($field) && $prefs[$field][0]) {

	// transform a date field into a unix time and use <, > or =

	preg_match("/\s*(\d+)-(\d+)-(\d+)/", $prefs[$field][0],$match);
	list(,$year,$month,$day) = $match;
	//echo "<br>DBG Matching $field: ".$prefs[$field][0];
	//echo "<br>DBG $field -> year $year, month $month,day $day";
	$time = mktime(0, 0, 0, $month, $day, $year);

	if ($advsrch) {
	    preg_match("/\s*(\d+)-(\d+)-(\d+)/", $prefs[$field.'_end'][0],$match_end);
	    list(,$year_end,$month_end,$day_end) = $match_end;
	    //echo "<br>DBG Matching $field"."_end:".$prefs[$field.'_end'][0];
	    //echo "<br>DBG $field"."_end -> year $year_end, month $month_end,day $day_end";
	    $time_end = mktime(23, 59, 59, $month_end, $day_end, $year_end);

	    if ($match)
		$where .= ' AND bug.'.$field.' >= '. $time;

	    if ($match_end)
		$where .= ' AND bug.'.$field.' <= '. $time_end;


	} else {

	    $operator = $prefs[$field.'_op'][0];
	    // '=' means that day between 00:00 and 23:59
	    if ($operator == '=') {
		$time_end = mktime(23, 59, 59, $month, $day, $year);
		$where .= ' AND bug.'.$field." >= $time ".'AND bug.'.$field." <= $time_end ";
	    } else {
		$where .= ' AND bug.'.$field." $operator= $time ";
	    }
	}

	// Always exclude undefined dates (0)
	$where .= ' AND bug.'.$field." <> 0 ";

    } else if (bug_data_is_text_field($field) && $prefs[$field][0]) {

	// It's a text field accept. Process INT or TEXT,VARCHAR fields differently
	$where .= ' AND '.bug_build_match_expression($field, $prefs[$field][0]);
    }
}

/* ==================================================
   Loop through the list of used fields to define label and fields/boxes
   used as search criteria
  ================================================== */
$ib=0;$is=0;
$load_cal=false;
while ( $field = bug_list_all_fields(cmp_place_query)) {

    if (!bug_data_is_used($field) || 
	!bug_data_is_showed_on_query($field) ) { 
	continue;
    }

    // beginning of a new row
    if ($ib % $fields_per_line == 0) {
	$align = ($pv ? "left" : "center");
	$labels .= "\n".'<TR align="'.$align.'" valign="top">';
	$boxes .= "\n".'<TR align="'.$align.'" valign="top">';
    }

    $labels .= '<td><b>'.bug_data_get_label($field).'&nbsp;'.
	($pv ? '':help_button('browse_bug_query_field',$field)).
	'</b></td>';

    $boxes .= '<TD><FONT SIZE="-1">';

    if (bug_data_is_select_box($field) ) {

	$boxes .= 
	    bug_field_display($field,$group_id,
			      ($advsrch ? $prefs[$field] : $prefs[$field][0]),
			      false,false,($pv?true:false),false,true,'None', true,'Any');

    } else if (bug_data_is_date_field($field) ){

	$load_cal = true; // We need to load the Javascript Calendar
	if ($advsrch) 
	    $boxes .= bug_multiple_field_date($field,$prefs[$field][0],
					      $prefs[$field.'_end'][0],0,0,$pv);
	else
	    $boxes .= bug_field_date_operator($field,$prefs[$field.'_op'][0],$pv).
		bug_field_date($field,$prefs[$field][0],0,0,$pv);
		
    } else if (bug_data_is_text_field($field) || 
	       bug_data_is_text_area($field) ) {

	$boxes .= 
	    ($pv ? $prefs[$field][0] : bug_field_text($field,$prefs[$field][0],15,80)) ;

    }
    $boxes .= "</TD>\n";

    $ib++;

    // end of this row
    if ($ib % $fields_per_line == 0) {
	$html_select .= $labels.'</TR>'.$boxes.'</TR>';
	$labels = $boxes = '';
    }

}

// Make sure the last few cells are in the table
if ($labels) {
    $html_select .= $labels.'</TR>'.$boxes.'</TR>';
}


/* ==================================================
   Loop through the list of used fields to see what fields are in the 
   result table and complement the SQL query accordingly.
  ================================================== */
while ( $field = bug_list_all_fields(cmp_place_result)) {

    if (!bug_data_is_used($field) || 
	!bug_data_is_showed_on_result($field) ) { 
	continue;
    }

    $col_list[] = $field;
    $width_list[] = bug_data_get_col_width($field);
    $lbl_list[] = bug_data_get_label($field);
    
    if ( bug_data_is_username_field($field)) {
	// user names requires some special processing to display the username
	// instead of the user_id
	$select .= ",user_$field.user_name AS $field";
	$from .= ",user user_$field";
	$where .= " AND user_$field.user_id=bug.$field ";
    } else {
	// otherwise just select this column as is
	$select .= ",bug.$field";
    }
    
}

/* ==================================================
    Run 2 queries : one to count the total number of results, and the second
    one with the LIMIT argument. It is faster than selecting all
    rows (without LIMIT) because when the number of bugs is large it takes
    time to transfer all the results from the server to the client. It is also faster
    than using the SQL_CALC_FOUND_ROWS/FOUND_ROWS() capabilities of
    MySQL
  ================================================== */
$sql_count = "$select_count $from $where";
$result_count=db_query($sql_count);
$totalrows = db_result($result_count,0,'count');

$sql = "$select $from $where $order_by $limit";
$result=db_query($sql);
//echo "<br> DBG SQL = $sql";
//exit 0;


/* ==================================================
   Display the HTML search form
  ================================================== */

if ($pv) {
    help_header('Bug Search Report - '.date($sys_datefmt,time()),false);
} else {
    bug_header(array('title'=>'Browse Bugs '.$hdr));
}

if ($load_cal) {
    echo "\n<script language=\"JavaScript\" src=\"/include/calendar.js\"></script>\n";
}

echo '<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="5">
          <FORM ACTION="'.$PHP_SELF .'" METHOD="GET" NAME="bug_form">
          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
          <INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
          <INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="'.$advsrch.'">
          <INPUT TYPE="HIDDEN" NAME="msort" VALUE="'.$msort.'">
          <TR><TD colspan="'.$fields_per_line.'" nowrap>'.
     ($pv ? '<h3>Selected Bugs</h3>':'<h3>Browse Bugs');

//Show the list of available bug reports
if (!$pv) {
    $res_report = bug_data_get_reports($group_id,user_getid());
    $box_name = 'report_id" onChange="document.bug_form.go_report.click()';

echo ' using report '.
	html_build_select_box($res_report,$box_name,$report_id,true,'Default').
	'<input VALUE="Go" NAME="go_report" type="submit">';
}

// Start building the URL that we use to for hyperlink in the form
$url = "/bugs/?group_id=$group_id&func=browse&set=$set&msort=$msort";
if ($set == 'custom')
     $url .= $pref_stg;
else
     $url .= '&advsrch='.$advsrch;

$url_nomorder = $url;
$url .= "&morder=$morder";

// Build the URL for alternate Search
if ($advsrch) { 
    $url_alternate_search = str_replace('advsrch=1','advsrch=0',$url);
    $text = 'Simple Search';
} else {    
    $url_alternate_search = str_replace('advsrch=0','advsrch=1',$url); 
    $text = 'Advanced Search';
}

if (!$pv) {
     echo '<small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(or use <a href="'.
	 $url_alternate_search.'">'.$text.'</a>)</small></h3><P>';
}

echo $html_select;

echo '</TABLE>';
if (!$pv) {
    echo '<p><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></FONT> '.
	'<input TYPE="text" name="chunksz" size="3" MAXLENGTH="5" '.
	'VALUE="'.$chunksz.'">bugs at once.</FORM>';
}

/* ==================================================
  Finally display the result table
 ================================================== */

$numrows = db_numrows($result);

if ($result && $numrows > 0) {

    // Build the sorting header messages
    if ($morder) {
	$order_statement = 'sorted by '.($pv ? '':help_button('browse_bug_sort',false)).
	    ' : '.bug_criteria_list_to_text($morder, $url_nomorder);
    } else {
	$order_statement ='';
    }
    echo '<A name="results"></A>';
    echo '<h3>'.$totalrows.' matching bug'.($totalrows>1 ? 's':'').' '.
	$order_statement.'</h3>';

    if (!$pv)
	echo '<P>Click a column heading to sort results (up or down), '.
	'or <A HREF="'.$url.'&order=severity#results"><b>Sort by Severity</b></A> '.
	'or <A HREF="'.$url.'&order=#results"><b>Reset sort</b></a>. ';
    
    if ($msort) { 
	$url_alternate_sort = str_replace('msort=1','msort=0',$url).
	    '&order=#results';
	$text = 'Deactivate';
    } else {    
	$url_alternate_sort = str_replace('msort=0','msort=1',$url).
	    '&order=#results';
	$text = 'Activate';
    }

    if (!$pv) {
	echo 'You can also <a href="'.$url_alternate_sort.'"><b> '.$text.
	    ' multicolumn sort</b></a>.&nbsp;&nbsp;&nbsp;&nbsp;'.
	    '(<a href="'.$url.'&pv=1"> <img src="/images/msg.gif" border="0">'.
	    '&nbsp;Printer version</a>)'."\n";
    }

    if ($pv) { $chunksz = 100000; }
    show_buglist($result,$offset,$totalrows,$col_list,$lbl_list,$width_list,
		 ($pv ? '' : $url), ($pv ? true:false) );
    show_priority_colors_key('Severity colors:');

} else {

    echo "<H2>No Matching Bugs Found for ".group_getname($group_id)." or filters too restrictive</H2>";
    echo db_error();

}

if ($pv)
     help_footer();
else
     bug_footer(array());

?>
