<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('vote_function.php');
require ('./my_utils.php');
if (user_isloggedin()) {

        // Make sure this page is not cached because
        // it uses the exact same URL for all user's
        // personal page
        header("Cache-Control: no-cache, must-revalidate"); // for HTTP 1.1
        header("Pragma: no-cache");  // for HTTP 1.0
	
	$HTML->header(array('title'=>'My Personal Page'));
	?>

    <span class="small">
	<H3>Personal Page for: <?php print user_getname(); ?>
	     <?php echo help_button('LoginAndPersonalPage.html'); ?></H3>
    <? util_get_content('my/intro'); ?>
	<TABLE width="100%" border="0">
	<TR><TD VALIGN="TOP" WIDTH="50%">
	<?php

	/*
		Bugs assigned to or submitted by this person
	*/
	echo $HTML->box1_top('My Bugs');

	$sql='SELECT group_id,COUNT(bug_id) '.
		'FROM bug '.
		'WHERE status_id <> 3 '.
		'AND (assigned_to='.user_getid().
		' OR submitted_by='.user_getid().') GROUP BY group_id ORDER BY group_id ASC LIMIT 100';

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
	    echo '
			<B>No Open Bugs are assigned to you or were submitted by you</B>';
	} else {

	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2='SELECT bug_id,severity,assigned_to,submitted_by,date AS open_date,summary '.
		'FROM bug '.
		'WHERE group_id='.$group_id.' AND status_id <> 3 '.
		'AND (assigned_to='.user_getid().
		' OR submitted_by='.user_getid().') LIMIT 100';

		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('bug',$group_id,$hide_item_id,$rows2,$hide_bug);
		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/bugs/?group_id='.$group_id.'"><B>'.
		    group_getname($group_id).'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
		$html = '';
		$count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {

		    if (!$hide_now) {
			// Form the 'Submitted by/Assigned to flag' for marking
			$AS_flag = my_format_as_flag(db_result($result2,$i,'assigned_to'), db_result($result2,$i,'submitted_by'));

			$html .= '
			
			<TR class="'.get_priority_color(db_result($result2,$i,'severity')).
			'"><TD class="small"><A HREF="/bugs/?func=detailbug&group_id='.
			$group_id.'&bug_id='.db_result($result2,$i,'bug_id').
			'">'.db_result($result2,$i,'bug_id').'</A></TD>'.
			'<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'&nbsp;'.$AS_flag.'</TD></TR>';

		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		echo $html_hdr.$html;
	    }


	    echo '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';

	}
	echo $HTML->box1_bottom();

	/*
		SRs assigned to or submitted by this person
	*/
	echo $HTML->box1_top('My Support Requests');

	$sql='SELECT group_id FROM support '.
	    'WHERE support_status_id = 1 '.
	    'AND (assigned_to='.user_getid().
	    ' OR submitted_by='.user_getid().') GROUP BY group_id ORDER BY group_id ASC LIMIT 100';

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
	    echo '
			<B>No Open SR are assigned to you or were submitted by you</B>';
	} else {

	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2="SELECT support_id,priority,assigned_to,submitted_by,open_date,summary ".
		    "FROM support ".
		    "WHERE group_id='$group_id' AND support_status_id <> '2' ".
		    "AND (assigned_to='".user_getid()."' ".
		    "OR submitted_by='".user_getid()."') LIMIT 100";
		    
		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('sr',$group_id,$hide_item_id,$rows2,$hide_sr);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/support/?group_id='.$group_id.'"><B>'.
		    group_getname($group_id).'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';

		$html = ''; $count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {
			
		    if (!$hide_now) {
			// Form the 'Submitted by/Assigned to flag' for marking
			$AS_flag = my_format_as_flag(db_result($result2,$i,'assigned_to'), db_result($result2,$i,'submitted_by'));

			$html .= '
			<TR class="'.get_priority_color(db_result($result2,$i,'priority')).
			'"><TD class="small"><A HREF="/support/?func=detailsupport&group_id='.
			$group_id.'&support_id='.db_result($result2,$i,'support_id').
			'">'.db_result($result2,$i,'support_id').'</A></TD>'.
			'<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'&nbsp;'.$AS_flag.'</TD></TR>';
		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		echo $html_hdr.$html;
	    }


	    echo '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	}
	echo $HTML->box1_bottom();

	/*
		Forums that are actively monitored
	*/
	echo $HTML->box1_top('Monitored Forums');

	$sql="SELECT groups.group_id, groups.group_name ".
		"FROM groups,forum_group_list,forum_monitored_forums ".
		"WHERE groups.group_id=forum_group_list.group_id ".
		"AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
		"AND forum_monitored_forums.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<b>You are not monitoring any forums</b>
			<P>
			If you monitor forums, you will be sent new posts in 
			the form of an email, with a link to the new message.
			<P>
			You can monitor forums by clicking &quot;Monitor Forum&quot; in 
			any given discussion forum.
			<BR>&nbsp;';
		echo db_error();
	} else {

	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2="SELECT forum_group_list.group_forum_id,forum_group_list.forum_name ".
		    "FROM groups,forum_group_list,forum_monitored_forums ".
		    "WHERE groups.group_id=forum_group_list.group_id ".
		    "AND groups.group_id=$group_id ".
		    "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
		    "AND forum_monitored_forums.user_id='".user_getid()."' LIMIT 100";

		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('forum',$group_id,$hide_item_id,$rows2,$hide_forum);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/forum/?group_id='.$group_id.'"><B>'.
		    db_result($result,$j,'group_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';

		$html = '';
		$count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {

		    if (!$hide_now) {

			$group_forum_id = db_result($result2,$i,'group_forum_id');
			$html .= '
			<TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
			    '&nbsp;&nbsp;&nbsp;-&nbsp;<A HREF="/forum/forum.php?forum_id='.$group_forum_id.'">'.
			    stripslashes(db_result($result2,$i,'forum_name')).'</A></TD>'.
			    '<TD ALIGN="center"><A HREF="/forum/monitor.php?forum_id='.$group_forum_id.
			    '" onClick="return confirm(\'Stop monitoring this Forum?\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
			    'BORDER=0 ALT="STOP MONITORING""></A></TD></TR>';
		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		echo $html_hdr.$html;
	    }

	    echo '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	}
	echo $HTML->box1_bottom();

	/*
		Filemodules that are actively monitored
	*/

	echo $HTML->box1_top('Monitored File Packages');
	$sql="SELECT groups.group_name,groups.group_id ".
		"FROM groups,filemodule_monitor,frs_package ".
		"WHERE groups.group_id=frs_package.group_id ".
		"AND frs_package.package_id=filemodule_monitor.filemodule_id ".
		"AND filemodule_monitor.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<b>You are not monitoring any files</b>
			<P>
			If you monitor files, you will be sent new release notices via
			email, with a link to the new file on our download server.
			<P>
			You can monitor files by visiting a project\'s &quot;Summary Page&quot; 
			and clicking on the check box in the files section.
			<BR>&nbsp;';
		echo db_error();
	} else {
	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2="SELECT frs_package.name,filemodule_monitor.filemodule_id ".
		    "FROM groups,filemodule_monitor,frs_package ".
		    "WHERE groups.group_id=frs_package.group_id ".
		    "AND groups.group_id=$group_id ".
		    "AND frs_package.package_id=filemodule_monitor.filemodule_id ".
		    "AND filemodule_monitor.user_id='".user_getid()."'  LIMIT 100";
		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('frs',$group_id,$hide_item_id,$rows2,$hide_frs);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/project/?group_id='.$group_id.'"><B>'.
		    db_result($result,$j,'group_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';

		$html = '';
		$count_new = max(0, $count_diff);		
		for ($i=0; $i<$rows; $i++) {

		    if (!$hide_now) {

			$html .='
			<TR class="'. util_get_alt_row_color($i) .'">'.
			    '<TD WIDTH="99%">-&nbsp;&nbsp;<A HREF="/project/filelist.php?group_id='.$group_id.'">'.
			    db_result($result2,$i,'name').'</A></TD>'.
			    '<TD><A HREF="/project/filemodule_monitor.php?filemodule_id='.
			    db_result($result2,$i,'filemodule_id').
			    '" onClick="return confirm(\'Stop Monitoring this Package?\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
			    'BORDER=0" ALT="STOP MONITORING"></A></TD></TR>';
		    }
		}
		
		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		echo $html_hdr.$html;
	    }

	    echo '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	}
	echo $HTML->box1_bottom();

	?>
	</TD><TD VALIGN="TOP" WIDTH="50%">
	<?php
	/*
		Tasks assigned to me
	*/
	$last_group=0;
	echo $HTML->box1_top('My Tasks',1,'',3);

	$sql = 'SELECT groups.group_id, groups.group_name, project_group_list.group_project_id, project_group_list.project_name '.
	    'FROM groups,project_group_list,project_task,project_assigned_to '.
	    'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
	    'AND project_assigned_to.assigned_to_id='.user_getid().
	    ' AND project_task.status_id=1 AND project_group_list.group_id=groups.group_id '.
	    "AND project_group_list.is_public!='9' ".
	  'AND project_group_list.group_project_id=project_task.group_project_id GROUP BY group_id,group_project_id';


	$result=db_query($sql);
	$rows=db_numrows($result);

	if (!$result || $rows < 1) {
	    echo '
			<b>You have no open tasks assigned to you</b>';
		echo db_error();
	} else {

	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');
		$group_project_id = db_result($result,$j,'group_project_id');

		$sql2 = 'SELECT project_task.project_task_id, project_task.priority, project_task.summary,project_task.percent_complete '.
		    'FROM groups,project_group_list,project_task,project_assigned_to '.
		    'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
		    "AND project_assigned_to.assigned_to_id='".user_getid()."' AND project_task.status_id='1'  ".
		    'AND project_group_list.group_id=groups.group_id '.
		    "AND groups.group_id=$group_id ".
		    'AND project_group_list.group_project_id=project_task.group_project_id '.
		    "AND project_group_list.is_public!='9' ".
		   "AND project_group_list.group_project_id= $group_project_id LIMIT 100";

		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('pm',$group_project_id,$hide_item_id,$rows2,$hide_pm);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
		    $hide_url.'<A HREF="/pm/task.php?group_id='.$group_id.
		    '&group_project_id='.$group_project_id.'"><B>'.
		    db_result($result,$j,'group_name').' - '.
		    db_result($result,$j,'project_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
		$html = '';
		$count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {
			
		    if (!$hide_now) {

			$html .= '
			<TR class="'.get_priority_color(db_result($result2,$i,'priority')).
			    '"><TD class="small"><A HREF="/pm/task.php/?func=detailtask&project_task_id='.
			    db_result($result2, $i, 'project_task_id').'&group_id='.
			    $group_id.'&group_project_id='.$group_project_id.
			    '">'.db_result($result2,$i,'project_task_id').'</A></TD>'.
			    '<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'</TD>'.
			    '<TD class="small">'.(db_result($result2,$i,'percent_complete')-1000).'%</TD></TR>';

		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		echo $html_hdr.$html;
	    }


	    echo '<TR><TD COLSPAN="3">&nbsp;</TD></TR>';
	}
	echo $HTML->box1_bottom();


	/*
		DEVELOPER SURVEYS

		This needs to be updated manually to display any given survey
	*/

	$sql="SELECT * from survey_responses ".
		"WHERE survey_id='1' AND user_id='".user_getid()."' AND group_id='1'";

	$result=db_query($sql);

	echo $HTML->box1_top('Quick Survey');

	if (db_numrows($result) < 1) {
		show_survey(1,1);
	} else {
		echo 'You have taken your developer survey';
	}
	echo '<TR align=left><TD COLSPAN="2">&nbsp;</TD></TR>
';
	echo $HTML->box1_bottom();


	/*
	       Personal bookmarks
	*/
	echo $HTML->box1_top('My Bookmarks');

	$result = db_query("SELECT bookmark_url, bookmark_title, bookmark_id from user_bookmarks where ".
		"user_id='". user_getid() ."' ORDER BY bookmark_title");
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<H3>You currently do not have any bookmarks saved</H3>';
		echo db_error();
	} else {

		for ($i=0; $i<$rows; $i++) {
		    echo '<TR class="'. util_get_alt_row_color($i) .'"><TD>';
		    echo '
                                           <B><A HREF="'. db_result($result,$i,'bookmark_url') .'">'.
			db_result($result,$i,'bookmark_title') .'</A></B> '.
			'<SMALL><A HREF="/my/bookmark_edit.php?bookmark_id='. db_result($result,$i,'bookmark_id') .'">[Edit]</A></SMALL></TD>'.
			'<td><A HREF="/my/bookmark_delete.php?bookmark_id='. db_result($result,$i,'bookmark_id') .
			'" onClick="return confirm(\'Delete this bookmark?\')">'.
			'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A>	</td></tr>';
			}
	}
	echo '<TR align=left><TD COLSPAN="2">&nbsp;</TD></TR>
';
	echo $HTML->box1_bottom();

	/*
		PROJECT LIST
	*/

	echo $HTML->box1_top('My Projects');
	$result = db_query("SELECT groups.group_name,"
		. "groups.group_id,"
		. "groups.unix_group_name,"
		. "groups.status,"
		. "groups.is_public,"
		. "user_group.admin_flags "
		. "FROM groups,user_group "
		. "WHERE groups.group_id=user_group.group_id "
		. "AND user_group.user_id='". user_getid() ."' "
		. "AND groups.type='1' AND groups.status='A'");
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo "You're not a member of any public projects";
		echo db_error();
	} else {

		for ($i=0; $i<$rows; $i++) {
			echo '
			       <TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
			    '<A href="/projects/'. db_result($result,$i,'unix_group_name') .'/"><b>'.
			    db_result($result,$i,'group_name') .'</b></A>';
			if ( db_result($result,$i,'admin_flags') == 'A' ) {
			    echo ' <small><A HREF="/project/admin/?group_id='.db_result($result,$i,'group_id').'">[Admin]</A></small>';
			}
			if ( db_result($result,$i,'is_public') == 0 ) {
			    echo ' (*)';
			    $private_shown = true;
			}
			echo '</TD>'.
			    '<td><A href="rmproject.php?group_id='. db_result($result,$i,'group_id').
			    '" onClick="return confirm(\'Quit this project?\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD></TR>';
		}
		
		if ($private_shown) {
		  echo '
			       <TR class="'. util_get_alt_row_color($i) .'"><TD colspan="2" class="small">'.
		      '(*) <em>Private projects</em></td></tr>';
		}
	}
	echo $HTML->box1_bottom();

	echo '</TD></TR><TR><TD COLSPAN=2>';

	echo show_priority_colors_key();

	?>
	</TD></TR>
	</TABLE>
	</span>
	<?php
	$HTML->footer(array());

} else {

	exit_not_logged_in();

}

?>
