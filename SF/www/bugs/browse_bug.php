<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!$offset || $offset < 0) {
	$offset=0;
}

//
// Memorize order by field as a user preference if explicitly specified.
// Automatically discard invalid field names.
//
if ($order) {
	if ($order=='bug_id' || $order=='summary' || $order=='date' || $order=='assigned_to_user' || $order=='submitted_by' || $order=='priority') {
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
	$order_by = " ORDER BY $order ".((($set=='closed' && $order=='date') || ($order=='priority')) ? ' DESC ':'');
} else {
	$order_by = "";
}

if (!$set) {
	/*
		if no set is passed in, see if a preference was set
		if no preference or not logged in, use open set
	*/
	if (user_isloggedin()) {
		$custom_pref=user_get_preference('bug_brow_cust'.$group_id);
		if ($custom_pref) {
			$pref_arr=explode('|',$custom_pref);
			$_assigned_to=$pref_arr[0];
			$_status=$pref_arr[1];
			$_category=$pref_arr[2];
			$_bug_group=$pref_arr[3];
			$set='custom';
		} else {
			$set='open';
			$_assigned_to=0;
		}
	} else {
		$set='open';
		$_assigned_to=0;
	}
}

if ($set=='my') {
	/*
		My bugs - backwards compat can be removed 9/10
	*/
	$_status='1';
	$_assigned_to=user_getid();

} else if ($set=='custom') {
	/*
		if this custom set is different than the stored one, reset preference
	*/
	$pref_=$_assigned_to.'|'.$_status.'|'.$_category.'|'.$_bug_group;
	if ($pref_ != user_get_preference('bug_brow_cust'.$group_id)) {
		//echo 'setting pref';
		user_set_preference('bug_brow_cust'.$group_id,$pref_);
	}
} else if ($set=='closed') {
	/*
		Closed bugs - backwards compat can be removed 9/10
	*/
	$_assigned_to=0;
	$_status='3';
} else {
	/*
		Open bugs - backwards compat can be removed 9/10
	*/
	$_assigned_to=0;
	$_status='1';
}

/*
	Display support requests based on the form post - by user or status or both
*/

//if status selected, add more to where clause
if ($_status && ($_status != 100)) {
	//for open tasks, add status=100 to make sure we show all
	$status_str="AND bug.status_id IN ($_status".(($_status==1)?',100':'').")";
} else {
	//no status was chosen, so don't add it to where clause
	$status_str='';
}

//if assigned to selected, add to where clause
if ($_assigned_to) {
	$assigned_str="AND bug.assigned_to='$_assigned_to'";
} else {
	//no assigned to was chosen, so don't add it to where clause
	$assigned_str='';
}

//if category selected, add to where clause
if ($_category && ($_category != 100)) {
	$category_str="AND bug.category_id='$_category' ";
} else {
	//no category to was chosen, so don't add it to where clause
	$category_str='';
}

//if bug_group selected, add to where clause
if ($_bug_group && ($_bug_group != 100)) {
	$bug_group_str="AND bug.bug_group_id='$_bug_group' ";
} else {
	//no bug_group was chosen, so don't add it to where clause
	$bug_group_str='';
}


//build page title to make bookmarking easier
//if a user was selected, add the user_name to the title
//same for status
bug_header(array('title'=>'Browse Bugs'.
	(($_assigned_to)?' For: '.user_getname($_assigned_to):'').
	(($_status && ($_status != 100))?' By Status: '. bug_data_get_status_name($_status):'')));

/*
	creating a custom technician box which includes "any" and "unassigned"
*/

$res_tech=bug_data_get_technicians ($group_id);

$tech_id_arr=util_result_column_to_array($res_tech,0);
$tech_id_arr[]='0';  //this will be the 'any' row

$tech_name_arr=util_result_column_to_array($res_tech,1);
$tech_name_arr[]='Any';

$tech_box=html_build_select_box_from_arrays ($tech_id_arr,$tech_name_arr,'_assigned_to',$_assigned_to,true,'Unassigned');


/*
	Show the new pop-up boxes to select assigned to and/or status
*/
echo '<FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
	<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0">
	<TR><TD colspan="4" nowrap>Browse Bugs by:</td></tr>
	<tr align="center" valign="bottom"><th><b>Assignee</b></th><th><b>Status</b></th><th><b>Category</b></th><th><b>Group</b></th></TR>
	<TR><TD><FONT SIZE="-1">'. $tech_box . '</TD>'.
	'<TD><FONT SIZE="-1">'. bug_status_box('_status',$_status,'Any') .'</TD>'.
	'<TD><FONT SIZE="-1">'. bug_category_box ('_category',$group_id,$_category,'Any') .'</TD>'.
	'<TD><FONT SIZE="-1">'. bug_group_box ('_bug_group',$group_id,$_bug_group,'Any') .'</TD>'.
	'<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></TD></TR></TABLE></FORM>';


if ($set=='open') {
	/*
		For open or default, see if the user has a filer set up
	*/
	$sql="SELECT sql_clause FROM bug_filter WHERE user_id='".user_getid()."' AND group_id='$group_id' AND is_active='1'";

	$result=db_query($sql);

	if ($result && db_numrows($result) > 0) {
		$sql="SELECT bug.group_id,bug.priority,bug.bug_id,bug.summary,bug.date,user.user_name AS submitted_by,".
			"user2.user_name AS assigned_to_user ".
			"FROM bug,user,user user2 ".
			"WHERE (". stripslashes( db_result($result,0,'sql_clause') ) .") ".
			"AND user.user_id=bug.submitted_by ".
			"AND user2.user_id=bug.assigned_to ".
			"AND group_id='$group_id'".
			$order_by .
			" LIMIT $offset,50";

		$statement="Using Your Filter";

	} else {
		/*
			Just browse the bugs in this group
		*/
		$sql="SELECT bug.group_id,bug.priority,bug.bug_id,bug.summary,bug.date,user.user_name AS submitted_by,".
			"user2.user_name AS assigned_to_user ".
			"FROM bug,user,user user2 ".
			"WHERE user.user_id=bug.submitted_by ".
			"AND bug.status_id <> '3' ".
			"AND user2.user_id=bug.assigned_to ".
			"AND group_id='$group_id'".
			$order_by .
			" LIMIT $offset,50";

	}

} else {
	/*
		Use the query from the form post
	*/
	$sql="SELECT bug.group_id,bug.priority,bug.bug_id,bug.summary,bug.date,user.user_name AS submitted_by,".
		"user2.user_name AS assigned_to_user ".
		"FROM bug,user,user user2 ".
		"WHERE user.user_id=bug.submitted_by ".
		" $status_str $assigned_str $bug_group_str $category_str ".
		"AND user2.user_id=bug.assigned_to ".
		"AND group_id='$group_id'".
		$order_by .
		" LIMIT $offset,51";

}

$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

	echo '<hr size="1" noshade>
';
	echo "<h3>$statement</H3>";

	//create a new $set string to be used for next/prev button
	if ($set=='custom') {
		$set .= '&_assigned_to='.$_assigned_to.'&_status='.$_status.'&_category='.$_category.'&_bug_group='.$_bug_group;
	}

	show_buglist($result,$offset,$set);
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

bug_footer(array());

?>
