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

/* ==================================================
   Make sure all URL arguments are captured as array. For simple
   search they'll be arrays with only one element at index 0 (this
   will avoid to deal with scalar in simple search and array in 
   advanced which would greatly complexifies the code)
 ================================================== */

if (isset($group_project_id)) {
    if (!is_array($group_project_id)) {
	$temp = $group_project_id;
	unset($group_project_id);
	$group_project_id[] = $temp;
    }
}

if (isset($_assigned_to)) {
    if (!is_array($_assigned_to)) {
	$temp = $_assigned_to;
	unset($_assigned_to);
	$_assigned_to[] = $temp;
    }
}

if (isset($_status)) {
    if (!is_array($_status)) {
	$temp = $_status;
	unset($_status);
	$_status[] = $temp;
    }
}


/* ==================================================
  Memorize order by field as a user preference if explicitly specified.
  Automatically discard invalid field names.
    - if ordering by priority or effort then sort DESCending
    - if ordering by user assigned to then use the user table
    - if ordering by status assigned to then use the project_status table
    - all others sort cirteria are in the project_task table
 ================================================== */
if ($order) {
	if ($order=='project_task_id' || $order=='percent_complete' || $order=='summary' || $order=='start_date' || $order=='end_date' || $order=='priority' || $order=='user_name' || $order=='status_name'|| $order=='project_name' || $order=='hours') {
		if(user_isloggedin() &&
		   ($order != user_get_preference('pm_task_order')) ) {
			user_set_preference('pm_task_order', $order);
		}
	} else {
		$order = false;
	}
} else {
	if(user_isloggedin()) {
		$order = user_get_preference('pm_task_order');
	}
}

if ($order) {
    $tbl = 'project_task';

    switch ($order) {

    case 'project_task_id':
	$order_lbl = 'Task ID';
	break;
    case 'summary':
	$order_lbl = 'Summary';
	break;
    case 'project_name':
	$tbl = 'project_group_list';
	$order_lbl = 'Sub-project';
	break;	
    case 'start_date':
	$order_lbl = 'Start Date';
	break;	
    case 'end_date':
	$order_lbl = 'End Date';
	break;	
    case 'hours':
	$way = 'DESC';
	$order_lbl = 'Effort';
	break;	
    case 'user_name':
	$tbl = 'user';
	$order_lbl = 'Assignee';
	break;
    case 'percent_complete':
	$order_lbl = 'Completion';
	break;	
    case 'status_name':
	$tbl = 'project_status';
	$order_lbl = 'Status';
	break;
    case 'priority':
	$way = 'DESC';
	$order_lbl = 'Priority';
	break;
    default: /* should not happen */
	$order_lbl = $order;
	break;
    }

    $order_by = ' ORDER BY '.$tbl.'.'.$order.' '.$way;
} else {
    $order_by = "";
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

	    $custom_pref=user_get_preference('pm_brow_cust'.$group_id);

	    if ($custom_pref) {
		$pref_arr = explode('&',substr($custom_pref,1));
		while (list(,$expr) = each($pref_arr)) {
		    // because eval is dangerous when data comes from
		    // the Web browser make sure it is safe
		    list($param,$val) = explode('=',$expr);
		    if (ereg("_assigned_to|_status|advsrch", $param)
			&& ereg("[0-9]+",$val)) {
			//echo "DBG evaluating : \$$expr<br>";
			eval('$'.$expr.';');
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

/* ==================================================
  Once the set type is defined make sure  that all form selection criteria
  have a value. If they don't then give them the Any value (0).
  I like this better than having an ambiguity between 0 and not defined
  ================================================== */

if ($set=='my') {
	/*
		My tasks - backwards compat can be removed 9/10
	*/
    $_status[]=1;
    $_assigned_to[]=user_getid();

} else if ($set=='custom') {
    /*
      if this custom set is different than the stored one, reset preference
    */
    $pref_stg .= pm_convert_to_url_arg('_assigned_to', $_assigned_to);
    $pref_stg .= pm_convert_to_url_arg('_status', $_status);

    $pref_stg .= '&advsrch='.$advsrch;
    if ($pref_stg != user_get_preference('pm_brow_cust'.$group_id)) {
	//echo "DBG setting pref = $pref_stg";
	user_set_preference('pm_brow_cust'.$group_id,$pref_stg);
    }

} else {
	/*
		Open tasks - backwards compat can be removed 9/10
	*/
    $_assigned_to[]=0;
    $_status[]=1;
}

/* ==================================================
   At this point make sure that all form variables are correctly defined
   (group_project_id, advsrch, status, assigned_to). If they are not then
   set them to 0 (means ANY for selection criteria)
   (It's a bit paranoid but it doesn't do any harm)
  ================================================== */

if (!isset($advsrch)) { $advsrch = 0; }
if (!isset($group_project_id)) { $group_project_id[] = 0; }
if (!isset($_assigned_to)) { $_assigned_to[] = 0; }
if (!isset($_status)) { $_status[] = 0; }


/* ==================================================
  Start building the SQL query
 ================================================== */

//if status selected, add condition to where clause
// if not selected it means Any status(0)
if (!pm_isvarany($_status)) {
    $status_str='AND project_task.status_id IN ('.implode(",",$_status).')'; 
} else {
    //no status (or any status) was chosen, so don't add it to where clause
    $status_str='';
}

//if assigned to selected, add condition to where clause
// if not selected it means Any assignee (0)
if (!pm_isvarany($_assigned_to)) {
    $assigned_str='AND project_assigned_to.assigned_to_id IN ('.implode(",",$_assigned_to).')';
} else {
    //no assigned to was chosen, so don't add it to where clause
    $assigned_str='';
}


//if sub_project selected, add more to where and from clauses
// if not selected it means any sub_project (0)

if (!pm_isvarany($group_project_id)) {
    $subproj_where = ' project_task.group_project_id IN ('.implode(",",$group_project_id).') ';
    $subproj_where .= ' AND project_group_list.group_project_id=project_task.group_project_id AND ';

} else {
    //no subproj was chosen so make sur it belongs to the
    //right group_id and make a join on sub projects
    $subproj_where = " project_group_list.group_id='$group_id' AND project_group_list.group_project_id=project_task.group_project_id AND project_group_list.is_public IN ($public_flag) AND ";
}

$sql='SELECT project_task.priority,project_task.group_project_id,project_task.project_task_id,'.
	'project_task.start_date,project_task.end_date,project_task.percent_complete,'.
        'project_task.summary,user.user_name,project_status.status_name,'.
        'project_group_list.project_name, project_task.hours '.
	'FROM project_group_list, project_task, project_assigned_to, user,project_status '.
	'WHERE '.$subproj_where.' project_task.project_task_id=project_assigned_to.project_task_id '.
        'AND user.user_id=project_assigned_to.assigned_to_id '.
        'AND project_status.status_id=project_task.status_id '.
	" $assigned_str $status_str ".
	$order_by .
	" LIMIT $offset,50";

// Also get all tasks that depend on other tasks
$sql_taskdeps = 'SELECT project_dependencies.project_task_id, is_dependent_on_task_id,project_task.group_project_id '.
        'FROM project_task, project_dependencies, project_group_list '.
        'WHERE '.$subproj_where. 
        ' project_dependencies.project_task_id=project_task.project_task_id '.
        ' AND project_dependencies.is_dependent_on_task_id <> 100';


//echo "DBG -- $sql <BR>";
$result=db_query($sql);
$result_taskdeps = db_query($sql_taskdeps);



/* ==================================================
  Build and display the selection form 
     - Header with information summarizing the query in the
       HTML title (for easy bookmarking)
     - Suproject box, assignee box and status box 
  If advanced search display multiple select box
 ================================================== */

// Build HTML title first
reset($group_project_id);
while (list(,$v) = each($group_project_id)) {
    if ($v == 0) { 
	$subhdr = ''; break;
    } else {
	$subhdr .= pm_data_get_group_name($group_project_id).' ';
    }
}
$hdr .= ' In: '.$subhdr;

reset($_assigned_to);
while (list(,$v) = each($_assigned_to)) {
    if ($v == 0) { 
	$subhdr = ''; break;
    } else {
	$subhdr .= user_getname($_assigned_to).' ';
    }
}
$hdr .= ' For: '.$subhdr;

reset($_status);
while (list(,$v) = each($_status)) {
    if ($v == 0) { 
	$subhdr = ''; break;
    } else {
	$subhdr .= pm_data_get_status_name($_status).' ';
    }
}
$hdr .= ' Status: '.$subhdr;

if ($order) {
    $hdr .= ' Sorted by: '.$order_lbl;
}

// Build the selection box for the various criteria
if ($advsrch) {
    // These are mutliple selection boxes
    $subproj_box = pm_multiple_subprojects_box('group_project_id[]',$group_id,$group_project_id,false,'',true,'Any');
    $tech_box = pm_multiple_tech_box('_assigned_to[]',$group_id,$_assigned_to,true,'Unassigned',true,'Any');
    $status_box = pm_multiple_status_box('_status[]',$_status,true,'None',true,'Any');
} else {
    // This are simple pull down menus with one possible choice
    $subproj_box = pm_subprojects_box('group_project_id',$group_id,$group_project_id[0],false,'',true,'Any');
    $tech_box = pm_tech_box('_assigned_to',$group_id,$_assigned_to[0],true,'Unassigned',true,'Any');

    $status_box = pm_status_box('_status',$_status[0],true,'None',true,'Any');
}


/* ==================================================
   Display the HTML form
  ================================================== */

pm_header(array('title'=>'Browse Tasks '.$hdr));

echo '<TABLE WIDTH="10%" BORDER="0" CELLPADDING="0" CELLSPACING="3">
              <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
	<INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="'.$advsrch.'">
	<TR><TD COLSPAN="4" nowrap>Browse Tasks by:&nbsp;&nbsp;&nbsp; ';

// Start building the URL that we use to for hyperlink in the form
$url = "/pm/task.php?group_id=$group_id&func=browse&set=$set";
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
echo '(or use <a href="'.$url_alternate_search.'&group_project_id[]='.$group_project_id[0].'">'.$text.')</a>';
echo '</TD></TR>';

echo '<TR align="center" valign="bottom"><TH><b>Sub-Project</b></TH><TH><b>Assignee</b></TH><TH><b>Status</b></TH></TR>
	<TR><TD><FONT SIZE="-1">'. $subproj_box .'</FONT></TD>'.
		'<TD><FONT SIZE="-1">'. $tech_box .'</FONT></TD><TD><FONT SIZE="-1">'.$status_box .'</FONT></TD>'.
		'<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Browse"></FONT></TD></TR></FORM></TABLE>';



/* ==================================================
  Finally display the result set 
 ================================================== */
if (db_numrows($result) < 1) {

	echo '
		<H2>No Matching Tasks found</H2>
		<P>
		<B>Add tasks using the link above</B>';
	echo db_error();
	echo '

<!-- '. $sql .' -->';
} else {

	//create a a $url string to be used for next/prev button
        //and sort by column
	$url .= pm_convert_to_url_arg('group_project_id',$group_project_id);

	/*
		Now display the tasks in a table with priority colors
	*/
	
	$out = pm_format_tasklist($result,$result_taskdeps,$offset,$url, $count);

	echo '
	       <br>
	       <H3>'.$count.' matching tasks'.
	    (isset($order_lbl)?' sorted by '.$order_lbl:'').'</H3>';

	echo '<P>Click a column heading to sort by that column, or <A HREF="'.$url.'&order=priority"><b>Sort by Priority</b></A><p>';

	echo $out;
	echo '<P><b>* Denotes overdue tasks</b>';
	show_priority_colors_key();

}

pm_footer(array());

?>
