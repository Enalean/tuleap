<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

// Number of search criteria (boxes) displayed in one row
$fields_per_line=5;

if (!$offset || $offset < 0) {
    $offset=0;
}

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
}


/* ==================================================
   Memorize order by field as a user preference if explicitly specified.
   Automatically discard invalid field names.
   Rk: bug_id, date and submitted_by are internal fields not stored 
   in the bug field table so put them explicitely in the order since
   they are always displayed on the report table
  ================================================== */

if ($order) {
    // always accept priority as a valid sort criteria because it is shown with
    // color code and there is URL at the end of the page to re-order by
    // priority in all cases.
    if (bug_data_is_showed_on_result($order) || ($order == 'priority')) {
	if(user_isloggedin()) {
	    user_set_preference('bug_browse_order', $order);
	}
    } else {
	$order = false;
    }
} else {
    if(user_isloggedin()) {
	$order = user_get_preference('bug_browse_order');
    }
}

if ($order) {
    //if ordering by priority OR closed date, sort DESC
    $order_by = " ORDER BY $order ".
	(($order=='date') || ($order=='priority') || ($order=='hours')? ' DESC ':'');
    $order_statement = 'sorted by \''.bug_data_get_label($order).'\'';
} else {
    $order_by = '';
    $order_statement = ' not sorted';
}

/* ==================================================
  Now see what type of task set is requested (set is one of none, 
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
		// Check that the left part is a valid field and right part is numeric
		if (ereg("[0-9]+",$value_id)) {
		    //echo '<br>DBG restoring prefs : $'.$prefs[$field].'[]='.$value_id;
		    if ($field == 'advsrch') {
			$advsrch = $value_id;
		    } else if (isset($BF_USAGE_BY_NAME[$field])) {
			$prefs[$field][] = $value_id;
		    }
		}
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
    
    if ($pref_stg != user_get_preference('bug_brow_cust'.$group_id)) {
	//echo "<br> DBG setting pref = $pref_stg";
	user_set_preference('bug_brow_cust'.$group_id,$pref_stg);
    }

} else {
    // Open bugs - backwards compat can be removed 9/10
    $prefs['status_id'][]=1;
}

/* ==================================================
   At this point make sure that the Advanced search flag is defined
   as well as all the arguments that serves as selection criteria
   If not defined then defaults to ANY (0)
  ================================================== */
if (!isset($advsrch)) { $advsrch = 0; }
while ($field = bug_list_all_fields()) {
    // the select boxes for the bug DB search first
    if (bug_data_is_showed_on_query($field) &&
	bug_data_is_select_box($field) ) {
	if (!isset($prefs[$field])) $prefs[$field][] = 0;
    }
}

/* ==================================================
   Start building the SQL query (select and where clauses) as well as
   the HTML for search criteria boxes 
  ================================================== */

// Force the selection of a few fields that will be displayed  anyway
// priority is shown as a color code so don't put it in the report table column list
$col_list = $lbl_list = array();
$select = "SELECT DISTINCT bug.bug_id,bug.priority,bug.summary,bug.date,user.user_name AS submitted_by";
$from = 'FROM bug, user';
$where = 'WHERE bug.group_id='.$group_id.' AND user.user_id=bug.submitted_by ';
$col_list[] = 'bug_id'; $lbl_list[] = 'Bug ID';
$col_list[] = 'summary'; $lbl_list[] = 'Summary';
$col_list[] = 'date'; $lbl_list[] = 'Submitted on';

// prepare the where clause with the selection criteria given by the user
reset($prefs);
while (list($field,$value_id) = each($prefs)) { 
    // Only add criteria to where clause if argument is not ANY
    if (!bug_isvarany($prefs[$field])) {
	$where .= 'AND bug.'.$field.' IN ('.implode(',',$prefs[$field]).') ';
    }
}


$ib=0;$is=0;
while ($field = bug_list_all_fields()) {

    if (bug_data_is_special($field) || !bug_data_is_used($field)) { 
	continue;
    }

    // the select boxes for the bug DB search first
    if (bug_data_is_showed_on_query($field) &&
	bug_data_is_select_box($field) ) {

	// beginning of a new row
	if ($ib % $fields_per_line == 0) {
	    $labels .= "\n".'<TR align="center" valign="top">';
	    $boxes .= "\n".'<TR align="center" valign="top">';
	}

	$labels .= '<td><b>'.bug_data_get_label($field).'</b></td>';
	if ($advsrch) {
	    $boxes .= '<td><FONT SIZE="-1">'.
		bug_multiple_field_box($field,'',$group_id,$prefs[$field],
				       true,'None', true,'Any') .'</TD>';
	} else {
	    $boxes .= '<td><FONT SIZE="-1">'.
		bug_field_box($field,'',$group_id,$prefs[$field][0],true,'None',
			      true,'Any') .'</TD>';
	}
	$ib++;

	// end of this row
	if ($ib % $fields_per_line == 0) {
	    $html_select .= $labels.'</TR>'.$boxes.'</TR>';
	    $labels = $boxes = '';
	}
    }

    // Second the columns to display and the SQL query build.
    // This is complex SQL query because we want to generate a table with the real
    // user value associated with each column from the bug table not simply the
    // value_id. So a simple "SELECT * from bug..." is not enough
    if (bug_data_is_showed_on_result($field)) {

	$col_list[] = $field;
	$lbl_list[] = bug_data_get_label($field);

	if (bug_data_is_select_box($field)) {
	    if ($field == 'assigned_to') {
		// user names requires some special processing
		$select .= ",user_at.user_name AS assigned_to";
		$from .= ",user user_at";
		$where .= " AND user_at.user_id=bug.assigned_to ";
	    } else {
		// we need to "decode" the value_id and return the corresponding
		// user readable value.
		$bfv_alias = 'bug_field_value'."$is";
		$select .= ",$bfv_alias.value AS $field";
		$from .= ",bug_field_value $bfv_alias";
		$where .= " AND ($bfv_alias.bug_field_id=".bug_data_get_field_id($field).
		    " AND $bfv_alias.value_id=bug.$field AND ($bfv_alias.group_id='$group_id' OR $bfv_alias.group_id='100')) ";
		$is++;
	    }
	} else {
	    // It's a text field so leave it as it is
	    $select .= ",bug.$field";
	}
    }
}

// Make sure the last few cells are in the table
if ($labels) {
    $html_select .= $labels.'</TR>'.$boxes.'</TR>';
}

// Force the submitted_by field at the very end 
// (not sure this one is really needed but we keep it for historical reasons)
$col_list[] = 'submitted_by';
$lbl_list[] = 'Submitter';

$sql = "$select $from $where $order_by LIMIT $offset,50";

$result=db_query($sql);
//echo "<br> DBG SQL = $sql";
//exit 0;

/* ==================================================
   Display the HTML form
  ================================================== */

bug_header(array('title'=>'Browse Bugs '.$hdr));

echo '<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="3">
          <FORM ACTION="'.$PHP_SELF .'" METHOD="GET">
          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
          <INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
          <INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="'.$advsrch.'">
          <TR><TD colspan="'.$fields_per_line.'" nowrap>Browse Bugs by:&nbsp;&nbsp;&nbsp;';

// Start building the URL that we use to for hyperlink in the form
$url = "/bugs/?group_id=$group_id&func=browse&set=$set";
if ($set == 'custom') 
     $url .= $pref_stg;
else
     $url .= '&advsrch='.$advsrch;

// Build the URL for alternate Search
if ($advsrch) { 
    $url_alternate_search = str_replace('advsrch=1','advsrch=0',$url);
    $text = 'Simple Search';
} else {    
    $url_alternate_search = str_replace('advsrch=0','advsrch=1',$url); 
    $text = 'Advanced Search';
}
echo '(or use <a href="'.$url_alternate_search.'">'.$text.')</a>';

echo $html_select;

echo '</TABLE>
        <FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></FONT>
       </FORM>';

/* ==================================================
  Finally display the result set 
 ================================================== */

$numrows = db_numrows($result);

if ($result && $numrows > 0) {

    echo '<h3>'.$numrows.' matching bug'.($numrows>1 ? 's':'').' '.
	$order_statement.'</h3>';

    echo '<P>Click a column heading to sort by that column, or <A HREF="'.$url.'&order=priority"><b>Sort by Priority</b></A><p>';

    show_buglist($result,$offset,$col_list,$lbl_list,$url);
    echo '<P>* Denotes Bugs > 30 Days Old';
    show_priority_colors_key();

} else {

    echo "<H2>No Matching Bugs Found for ".group_getname($group_id)." or filters too restrictive</H2>";
    echo db_error();

}

//Debug echo '<FORM>SQL query = <TEXTAREA name ="toto"cols="60" rows="20" wrap="soft">'.$sql.'</TEXTAREA><BR></FORM>';
bug_footer(array());

?>
