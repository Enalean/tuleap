<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../pm_utils.php');

/*

	Project / Task Manager Admin
	By Tim Perdue Nov. 1999

*/

Function  ShowResultsSubProjects($result) {
	global $group_id;
	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);

	$title_arr=array();
	$title_arr[]='SubProject ID';
	$title_arr[]='Project name';
	$title_arr[]='Rank on screen';

	echo html_build_list_table_top ($title_arr);

	for($j=0; $j<$rows; $j++)  {

		echo "<tr class=\"". html_get_alt_row_color($j) ."\">\n";

		for ($i=0; $i<$cols; $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; 
}

if ($group_id && user_ismember($group_id,'P2')) {

	if ($post_changes) {
		/*
			Update the database
		*/

        // Default value for $place
        if ( ($place == '')||(!isset($place)) ) {
            $place = 0;
        }
        
		if ($projects) {
			/*
				Insert a new project
			*/
			$sql="INSERT INTO project_group_list (group_id,project_name,is_public,description,order_id) ".
				"VALUES ('$group_id','". htmlspecialchars($project_name) ."','$is_public','". htmlspecialchars($description) ."',$place)";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= " Error inserting value ";
				echo db_error();
			}

			$feedback .= " Subproject Inserted ";

	       } else if ($change_status) {
			/*
				Change a project to public/private
			*/
		       $sql="UPDATE project_group_list SET is_public='$is_public',project_name='". htmlspecialchars($project_name) ."', ".
				"description='". htmlspecialchars($description) ."', order_id = $place ".
				"WHERE group_id='$group_id' AND group_project_id='$group_project_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= " Error Updating Status ";
				echo db_error();
			} else {
				$feedback .= " Status Updated Successfully ";
			}
		}
	} 
	/*
		Show UI forms
	*/

	if ($projects) {
		/*
			Show categories and blank row
		*/

		pm_header_admin(array ('title'=>'Add Projects',
		    'help'=>'TaskManagerAdministration.html#TaskManagerAddaSubproject'));

		echo '<H2>Add Subprojects to the Project/Task Manager</H2>';

		/*
			List of possible categories for this group
		*/
		$sql="SELECT group_project_id,project_name,order_id FROM project_group_list WHERE group_id='$group_id' order by order_id";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
		    echo "<H3>Existing Subprojects</H3>";
			ShowResultsSubProjects($result);
		} else {
			echo "\n<H2>No Subprojects in this group</H2>";
		}
		?>
		<P>
		Add a new subproject to the Project/Task Manager. <B>This is different than
		 adding a task to a project.</B>
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="projects" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<P>
		<B>New Project Name:</B><BR>
		<INPUT TYPE="TEXT" NAME="project_name" VALUE="" SIZE="30" MAXLENGTH="60">
		<P>
		<B>Description:</B><BR>
		<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="60" MAXLENGTH="100">
		<BR><BR>
		<TABLE cellpadding=0 cellspacing=0 border=0>
		<TR>
		  <TD valign=top>
    	    <B>Is Public?</B><BR>
	        <INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> Yes&nbsp;&nbsp;&nbsp;
		    <INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> No<P>
		  </TD>
		  <TD width=30>&nbsp;</TD>
		  <TD valign=top>
		    <B>Rank on screen:</B>
            <INPUT TYPE="TEXT" NAME="place" VALUE="10" SIZE="6" MAXLENGTH="6">
		  </TD>
		</TR>
		</TABLE> 
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php
		pm_footer(array());

	} else if ($change_status) {
		/*
			Change a project to public/private
		*/
		pm_header_admin(array('title'=>'Change Project/Task Manager Status',
				      'help'=>'TaskManagerAdministration.html#TaskManagerUpdateaSubproject'));

		$sql="SELECT project_name,group_project_id,is_public,description,order_id ".
			"FROM project_group_list ".
			"WHERE group_id='$group_id' order by order_id";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if (!$result || $rows < 1) {
			echo '
				<H2>No Subprojects Found</H2>
				<P>
				None found for this project';
			echo db_error();
		} else {
			echo '
			<H2>Update Project/Task Manager</H2>
			<P>
			You can make subprojects in the Project/Task Manager private from here. Please note that private subprojects
			can still be viewed by members of your project, but not the general public.<P>';

			$title_arr=array();
			$title_arr[]='Status';
			$title_arr[]='Name';
			$title_arr[]='Rank on screen';
			$title_arr[]='Update';

			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i<$rows; $i++) {
				echo '
					<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_project_id" VALUE="'.db_result($result,$i,'group_project_id').'">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">';
				echo '
					<TR class="'. util_get_alt_row_color($i) .'"><TD>
						<FONT SIZE="-1">
						<B>Is Public?</B><BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"'.((db_result($result,$i,'is_public')=='1')?' CHECKED':'').'> Yes<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"'.((db_result($result,$i,'is_public')=='0')?' CHECKED':'').'> No<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="9"'.((db_result($result,$i,'is_public')=='9')?' CHECKED':'').'> Deleted<BR>
					</TD><TD>
						<INPUT TYPE="TEXT" NAME="project_name" VALUE="'. db_result($result, $i, 'project_name') .'"SIZE="30" MAXLENGTH="60">
					</TD><TD>
						<INPUT TYPE="TEXT" NAME="place" VALUE="'. db_result($result, $i, 'order_id') .'" SIZE="6" MAXLENGTH="6">
					</TD><TD>
						<FONT SIZE="-1">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Update">
					</TD></TR>
					<TR class="'.util_get_alt_row_color($i) .'">
					  <TD COLSPAN="4">
						<B>Description:</B>&nbsp;
						<INPUT TYPE="TEXT" NAME="description" VALUE="'.
						db_result($result,$i,'description') .'" SIZE="60" MAXLENGTH="100"><BR>
					  </TD>
					</TR>
					</FORM>';
			}
			echo '</TABLE>';
		}

		pm_footer(array());

	} else {

		/*
			Show main page
		*/
		pm_header_admin(array('title'=>'Project/Task Manager Administration',
				      'help'=>'TaskManagerAdministration.html'));

		echo '
			<H2>Project/Task Manager Administration</H2>
			<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&projects=1"><h3>Add a Subproject</h3></A>
			Add a project, which can contain a set of tasks. This is different than creating a new task.
			<BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&change_status=1"><h3>Update Subprojects</h3></A>
			Determine whether non-project-members can view Subprojects in the Project/Task Manager, update name and description
		                 <BR>';
	    if (user_ismember($group_id,'P2') || user_ismember($group_id,'A')) {
		 echo '<H3><a href="/pm/admin/field_usage.php?group_id='.$group_id.'">Manage Field Usage</a></H3>';
		 echo 'Define what task fields you want to use in the task manager of this project. (remark: some of the fields like status, assignee, severity&hellip; are mandatory and cannot be removed).<P>';
		 echo '<H3><a href="/pm/admin/field_values.php?group_id='.$group_id.'">Manage Field Values</a></H3>';
		 echo 'Define the set of values for the task fields you have decided to use in your task manager for this specific project. <P>';
	    }
	    echo '
			<A HREF="/pm/admin/personal_settings.php?group_id='.$group_id.'"><h3>Personal Configuration Settings </A> (for user '.user_getname(user_getid()).')</h3>
			Define Task Manager personal configuration parameters<BR>

			<A HREF="/pm/admin/notification_settings.php?group_id='.$group_id.'"><h3>Email Notification Settings</h3></A>
			Users can define when they want to be notified of a task update via email. Project Administrators can also define global email notification rules.
		                 <BR>
			<A HREF="/pm/admin/other_settings.php?group_id='.$group_id.'"><h3>Global  Configuration Settings</h3></A>
			Define Task Manager global configuration parameters';

		pm_footer(array());
	}

} else {

	//browse for group first message

	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}

}
?>
