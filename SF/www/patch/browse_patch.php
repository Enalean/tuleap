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
	if ($order=='patch_id' || $order=='summary' || $order=='date' || $order=='assigned_to_user' || $order=='submitted_by') {
		if(user_isloggedin()) {
			user_set_preference('patch_browse_order', $order);
		}
	} else {
		$order = false;
	}
} else {
	if(user_isloggedin()) {
		$order = user_get_preference('patch_browse_order');
	}
}

if ($order) {
	$order_by = " ORDER BY $order ".(($set=='closed' && $order=='date') ? ' DESC ':'');
} else {
	$order_by = "";
}

if (!$set) {
	/*
		if no set is passed in, see if a preference was set
		if no preference or not logged in, use open set
	*/
	if (user_isloggedin()) {
		$custom_pref=user_get_preference('patch_browcust'.$group_id);
		if ($custom_pref) {
			$pref_arr=explode('|',$custom_pref);
			$_assigned_to=$pref_arr[0];
			$_status=$pref_arr[1];
			$_category=$pref_arr[2];
			$set='custom';
		} else {
			$set='open';
			$_assigned_to=0;
		}
	} else {
		$_assigned_to=0;
		$set='open';
	}
}

if ($set=='my') {
	/*
		My patches - backwards compat can be removed 9/10
	*/
	$_status=1;
	$_assigned_to=user_getid();

} else if ($set=='custom') {
	/*
		if this custom set is different than the stored one, reset preference
	*/
	$pref_=$_assigned_to.'|'.$_status.'|'.$_category;
	if ($pref_ != user_get_preference('patch_browcust'.$group_id)) {
		//echo 'setting pref';
		user_set_preference('patch_browcust'.$group_id,$pref_);
	}
} else if ($set=='postponed') {
	/*
		postponed patches
	*/

	$_assigned_to=0;
	$_status='4';

} else if ($set=='closed') {
	/*
		Closed patches - backwards compat can be removed 9/10
	*/
	$_assigned_to=0;
	$_status='2';
} else {
	/*
		Open patches - backwards compat can be removed 9/10
	*/
	$_assigned_to=0;
	$_status='1';
}

/*
	Display patches based on the form post - by user or status or both
*/

//if status selected, and more to where clause
if ($_status && ($_status != 100)) {
	//for open tasks, add status=100 to make sure we show all
	$status_str="AND patch.patch_status_id IN ($_status".(($_status==1)?',100':'').")";
} else {
	//no status was chosen, so don't add it to where clause
	$status_str='';
}

//if status selected, and more to where clause
if ($_category && ($_category != 100)) {
	//for open tasks, add status=100 to make sure we show all
	$category_str="AND patch.patch_category_id ='$_category'";
} else {
	//no status was chosen, so don't add it to where clause
	$category_str='';
}

//if assigned to selected, and more to where clause
if ($_assigned_to) {
	$assigned_str="AND patch.assigned_to='$_assigned_to'";
} else {
	//no assigned to was chosen, so don't add it to where clause
	$assigned_str='';
}

//build page title to make bookmarking easier
//if a user was selected, add the user_name to the title
//same for status
patch_header(array('title'=>'Browse Patches'.
	(($_assigned_to)?' For: '.user_getname($_assigned_to):'').
	(($_status && ($_status != 100))?' By Status: '. get_patch_status_name($_status):'')));


$sql="SELECT patch.group_id,patch.patch_id,patch.summary,patch.filename, patch.filesize,".
	"patch.open_date AS date,user.user_name AS submitted_by,user2.user_name AS assigned_to_user ".
	"FROM patch,user,user user2 ".
	"WHERE user.user_id=patch.submitted_by ".
	" $category_str ".
	" $status_str ".
	"AND user2.user_id=patch.assigned_to ".
	" $assigned_str ".
	"AND group_id='$group_id'".
	$order_by .
	" LIMIT $offset,50";

	$statement='Viewing custom patches';

$result=db_query($sql);


/*
	creating a custom technician box which includes "any" and "unassigned"
*/

$res_tech=patch_data_get_technicians ($group_id);

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
	<TR><TD COLSPAN="3" nowrap>Browse Patches by:</TD></TR>
              <TR align="center"><TD><b>Category</b></TD><TD><b>Assignee</b></TD><TD><b>Status</b></TD><TR>'.
        '<TR><TD><FONT SIZE="-1">'. patch_category_box($group_id,'_category',$_category,'Any') .'</TD>
	<TD><FONT SIZE="-1">'. $tech_box .
        '</TD><TD><FONT SIZE="-1">'. patch_status_box('_status',$_status,'Any') .'</TD>'.
	
'<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></TD></TR></FORM></TABLE>';


if ($result && db_numrows($result) > 0) {

	//create a new $set string to be used for next/prev button
	if ($set=='custom') {
		$set .= '&_assigned_to='.$_assigned_to.'&_status='.$_status;
	}

	echo '
		<P>
		<h3>'.$statement.'</H3>
		<P>
		<B>You can use the Patch Manager to control/faciliate code contributions from the user community</B>
		<P>';

	show_patchlist($result,$offset,$set);

} else {
	echo '
		<P>
		<H3>'.$statement.'</H3>
		<P>
		<B>You can use the Patch Manager to control/faciliate code contributions from the user community</B>
		<P>';
	echo '
		<H1>No Patches Match Your Criteria</H1>';
	echo db_error();
}

patch_footer(array());

?>
