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
	if ($order=='support_id' || $order=='summary' || $order=='date' || $order=='assigned_to_user' || $order=='submitted_by' || $order=='priority') {
		if(user_isloggedin()) {
			user_set_preference('support_browse_order', $order);
		}
	} else {
		$order = false;
	}
} else {
	if(user_isloggedin()) {
		$order = user_get_preference('support_browse_order');
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
		$custom_pref=user_get_preference('sup_brow_cust'.$group_id);
		if ($custom_pref) {
			$pref_arr=explode('|',$custom_pref);
			$_assigned_to=$pref_arr[0];
			$_status=$pref_arr[1];
			$_category=$pref_arr[2];
			$set='custom';
		} else {
			$_assigned_to=0;
			$set='open';
		}
	} else {
		$_assigned_to=0;
		$set='open';
	}
}

if ($set=='my') {
	/*
		My requests - backwards compat can be removed 9/10
	*/
	$_status=1;
	$_assigned_to=user_getid();

} else if ($set=='custom') {
	/*
		if this custom set is different than the stored one, reset preference
	*/
	$pref_=$_assigned_to.'|'.$_status.'|'.$_category;
	if ($pref_ != user_get_preference('sup_brow_cust'.$group_id)) {
		//echo 'setting pref';
		user_set_preference('sup_brow_cust'.$group_id,$pref_);
	}
} else if ($set=='closed') {
	/*
		Closed requests - backwards compat can be removed 9/10
	*/
	$_assigned_to=0;
	$_status='2';
} else {
	/*
		Open requests - backwards compat can be removed 9/10
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
	$status_str="AND support.support_status_id IN ($_status".(($_status==1)?',100':'').")";
} else {
	//no status was chosen, so don't add it to where clause
	$status_str='';
}

//if assigned to selected, add to where clause
if ($_assigned_to) {
	$assigned_str="AND support.assigned_to='$_assigned_to'";
} else {
	//no assigned to was chosen, so don't add it to where clause
	$assigned_str='';
}

//if category selected, add to where clause
if ($_category && ($_category != 100)) {
	$category_str="AND support.support_category_id='$_category'";
} else {
	//no assigned to was chosen, so don't add it to where clause
	$category_str='';
}

//build page title to make bookmarking easier
//if a user was selected, add the user_name to the title
//same for status
support_header(array('title'=>'Browse Support Requests'.
	(($_assigned_to)?' For: '.user_getname($_assigned_to):'').
	(($_status && ($_status != 100))?' By Status: '. support_data_get_status_name($_status):'')));

//now build the query using the criteria built above
$sql="SELECT support.priority,support.group_id,support.support_id,support.summary,".
	"support.open_date AS date,user.user_name AS submitted_by,user2.user_name AS assigned_to_user ".
	"FROM support,user,user user2 ".
	"WHERE user.user_id=support.submitted_by ".
	" $status_str $assigned_str $category_str ".
	"AND user2.user_id=support.assigned_to ".
	"AND group_id='$group_id'".
	$order_by .
	" LIMIT $offset,50";

/*
        creating a custom technician box which includes "any" and "unassigned"
*/

$res_tech=support_data_get_technicians ($group_id);

$tech_id_arr=util_result_column_to_array($res_tech,0);
$tech_id_arr[]='0';  //this will be the 'any' row

$tech_name_arr=util_result_column_to_array($res_tech,1);
$tech_name_arr[]='Any';

$tech_box=html_build_select_box_from_arrays ($tech_id_arr,$tech_name_arr,'_assigned_to',$_assigned_to,true,'Unassigned');



/*
	Show the new pop-up boxes to select assigned to and/or status
*/
echo '<TABLE WIDTH="10%" BORDER="0"><FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
	<TR><TD COLSPAN="3" nowrap><b>Browse Requests by User/Status/Category:</b></TD></TR>
	<TR><TD><FONT SIZE="-1">'. $tech_box .'</TD><TD><FONT SIZE="-1">'. support_status_box('_status',$_status,'Any') .'</TD>'.
	'<TD><FONT SIZE="-1">'. support_category_box ($group_id,$name='_category',$_category,'Any') .'</TD>'.
'<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></TD></TR></FORM></TABLE>';

$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

	echo '
		<P>
		<h3>'.$statement.'</H3>
		<P>
		<B>You can use the Support Manager to coordinate tech support</B>
		<P>';

	//create a new $set string to be used for next/prev button
	if ($set=='custom') {
		$set .= '&_assigned_to='.$_assigned_to.'&_status='.$_status.'&_category='.$_category;
	}

	$url = "/support/?group_id=$group_id&set=$set&order=";
	echo '<P>Click a column heading to sort by that column, or <A HREF="'.$url.'priority"><b>Sort by Priority</b></A><p>';

	show_supportlist($result,$offset,$set);
	echo '<P>* Denotes Requests > 15 Days Old';
	show_priority_colors_key();

} else {

	echo '
		<P>
		<H3>'.$statement.'</H3>
		<P>
		<HR NoShade SIZE="1">
		<B>You can use the Support Manager to coordinate tech support</B>
		<P>';
	echo '
		<H1>No Support Requests Match Your Criteria</H1>';
	echo db_error();
	//echo "<!-- $sql -->";

}

support_footer(array());

?>
