<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$fields_per_line=5;

if (!$offset || $offset < 0) {
    $offset=0;
}

//
// Memorize order by field as a user preference if explicitly specified.
// Automatically discard invalid field names.
// Rk: bug_id, date and submitted_by are internal fields not stored 
// in the bug field table so put them explicitely in the order since they are
// always displayed on the report table
//

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

if (!$set) {
    /*
      if no set is passed in, see if a preference was set
      if no preference or not logged in, use open set
      (Prefs is a string of the form  field1=value_id1|field21=value_id2|.... )
    */
    if (user_isloggedin()) {
	$custom_pref=user_get_preference('bug_brow_cust'.$group_id);
	$pref_arr = explode('&',$custom_pref);
	if ($custom_pref) {
	    while (list(,$pref_elt) = each($pref_arr)) {
		list($field,$value_id) = explode('=',$pref_elt);
		$prefs[$field] = $value_id;
	    }
	    $set='custom';
	} else {
	    $set='open';
	    $prefs['assigned_to']=0;
	}
    } else {
	$set='open';
	$prefs['assigned_to']=0;
    }
}

if ($set=='my') {
    /*
      My bugs - backwards compat can be removed 9/10
    */
    $prefs['status_id']='1';
    $prefs['assigned_to']=user_getid();

} else if ($set=='custom') {

    // Get the list of bug fields used in the form (they are in the URL - GET method)
    // and then build the preferences array accordingly
    // Exclude the group_id parameter
    $vfl = bug_extract_field_list(false);
    unset($vfl['group_id']);
    while (list($field,$value_id) = each($vfl)) {
	$prefs[$field] = $value_id;
	$pref_stg .= '&'.$field.'='.$value_id;
	
	// build part of the HTML title of this page for more friendly bookmarking
	// Do not add the criteria in the header if value is "Any"
	if ($value_id != 0) {
	    $hdr .= ' By '.bug_data_get_label($field).': '.
		bug_data_get_value($field,$group_id,$value_id);
	}
    }
    
    if ($pref_stg != user_get_preference('bug_brow_cust'.$group_id)) {
	//echo 'setting pref';
	user_set_preference('bug_brow_cust'.$group_id,$pref_stg);
    }

} else if ($set=='closed') {
    /*
		Closed bugs - backwards compat can be removed 9/10
	*/
    $prefs['status_id']='3';
    $prefs['assigned_to']=0;

} else {
    /*
		Open bugs - backwards compat can be removed 9/10
	*/
    $prefs['status_id']='1';
    $prefs['assigned_to']=0;

}


//Output the HTML page title to make bookmarking easier
//if a user was selected, add the user_name to the title
bug_header(array('title'=>'Browse Bugs '.$hdr));


/*
  Display all the field select box as configured by the project admin
  and also build the part of the select and where clause for the final query
*/

// Select a few fields that will be displayed  anyway
// priority is shown as a color code so don't put it in the report table column list
$col_list = $lbl_list = array();
$select = "SELECT DISTINCT bug.bug_id,bug.priority,bug.summary,bug.date,user.user_name AS submitted_by";
$from = 'FROM bug, bug_field, user';
$where = 'WHERE bug.group_id='.$group_id.' AND user.user_id=bug.submitted_by ';
$col_list[] = 'bug_id'; $lbl_list[] = 'Bug ID';
$col_list[] = 'summary'; $lbl_list[] = 'Summary';
$col_list[] = 'date'; $lbl_list[] = 'Submitted on';

// prepare the where clause with the selection criteria given by the user
reset($prefs);
while (list($field,$value_id) = each($prefs)) { 
    // 0 means Any so no where clause in this case
    if ($value_id != 0) {
	$where .= "AND bug.$field = '$value_id' "; }
}


$ib=0;$is=0;
while ($field = bug_list_all_fields()) {

    if (bug_data_is_special($field) || !bug_data_is_used($field)) { 
	continue;}

    // the select boxes for the bug DB search first
    if (bug_data_is_showed_on_query($field) &&
	bug_data_is_select_box($field) ) {

	// beginning of a new row
	if ($ib % $fields_per_line == 0) {
	    $labels .= "\n".'<TR align="center" valign="top">';
	    $boxes .= "\n".'<TR align="center" valign="top">';
	}

	$labels .= '<td><b>'.bug_data_get_label($field).'</b></td>';
	$boxes .= '<td><FONT SIZE="-1">'.
	    bug_field_box($field,'',$group_id,$prefs[$field],true,'None',
			  true,'Any') .'</TD>';			  
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

/*
	Show the new pop-up boxes to select assigned to and/or status
*/
?>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="GET">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0">
<TR><TD colspan="<?php echo $fields_per_line; ?>" nowrap>Browse Bugs by:</td></tr>
<?php echo $html_select; ?>

</TABLE>
<FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></FONT>
</FORM>

<?php

// print the table report with the selected bugs
$result=db_query($sql);
$statement .= db_numrows($result).' bugs matching';

if ($result && db_numrows($result) > 0) {

    echo '<hr size="1" noshade>
';
    echo "<h3>$statement $order_statement</h3>";

    //create a new $set string to be used for next/prev button
    if ($set=='custom') {
	$set .= $pref_stg;
    }

    show_buglist($result,$offset,$col_list,$lbl_list,$set);
    echo '<P>* Denotes Bugs > 30 Days Old';
    show_priority_colors_key();

    $url = "/bugs/?group_id=$group_id&set=$set&order=";
    echo '<P>Click a column heading to sort by that column, or <A HREF="'.$url.'priority">Sort by Priority</A>';

} else {

    echo '<hr width="300" size="1" noshade>
';
    echo "<H3>$statement</H3>

		<H2>No Matching Bugs Found for ".group_getname($group_id)." or filters too restrictive</H2>";
    echo db_error();

}

//Debug echo '<FORM>SQL query = <TEXTAREA name ="toto"cols="60" rows="20" wrap="soft">'.$sql.'</TEXTAREA><BR></FORM>';
bug_footer(array());

?>
