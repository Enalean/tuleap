<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	Job/People finder 
	By Tim Perdue, Sourceforge, March 2000
*/
function people_header($params) {
	global $group_id,$job_id,$DOCUMENT_ROOT,$HTML;

	if ($group_id) {
		$params['toptab']='people';
		$params['group']=$group_id;
		echo site_project_header($params);
	} else {
		echo $HTML->header($params);
	}
	echo '
		<H2>Project Help Wanted</H2>
		<P><B>
	<A HREF="/people/admin/">Admin</A>';
	if ($group_id && $job_id) {
		echo ' | <A HREF="/people/editjob.php?group_id='. $group_id .'&job_id='. $job_id .'">Edit Job</A>';
	}
	echo '</B>';
}

function people_footer($params) {
	global $feedback, $HTML;
	html_feedback_bottom($feedback);
	$HTML->footer($params);
}

function people_skill_box($name='skill_id',$checked='xyxy') {
	global $PEOPLE_SKILL;
	if (!$PEOPLE_SKILL) {
		//will be used many times potentially on a single page
		$sql="SELECT * FROM people_skill ORDER BY skill_id ASC";
		$PEOPLE_SKILL=db_query($sql);
	}
	return html_build_select_box ($PEOPLE_SKILL,$name,$checked);
}

function people_skill_level_box($name='skill_level_id',$checked='xyxy') {
	global $PEOPLE_SKILL_LEVEL;
	if (!$PEOPLE_SKILL_LEVEL) {
		//will be used many times potentially on a single page
		$sql="SELECT * FROM people_skill_level";
		$PEOPLE_SKILL_LEVEL=db_query($sql);
	}
	return html_build_select_box ($PEOPLE_SKILL_LEVEL,$name,$checked);
}

function people_skill_year_box($name='skill_year_id',$checked='xyxy') {
	global $PEOPLE_SKILL_YEAR;
	if (!$PEOPLE_SKILL_YEAR) {
		//will be used many times potentially on a single page
		$sql="SELECT * FROM people_skill_year";
		$PEOPLE_SKILL_YEAR=db_query($sql);
	}
	return html_build_select_box ($PEOPLE_SKILL_YEAR,$name,$checked);
}

function people_job_status_box($name='status_id',$checked='xyxy') {
	$sql="SELECT * FROM people_job_status";
	$result=db_query($sql);
	return html_build_select_box ($result,$name,$checked);
}

function people_job_category_box($name='category_id',$checked='xyxy') {
	$sql="SELECT * FROM people_job_category";
	$result=db_query($sql);
	return html_build_select_box ($result,$name,$checked);
}

function people_add_to_skill_inventory($skill_id,$skill_level_id,$skill_year_id) {
	global $feedback;
	if (user_isloggedin()) {
		//check if they've already added this skill
		$sql="SELECT * FROM people_skill_inventory WHERE user_id='". user_getid() ."' AND skill_id='$skill_id'";
		$result=db_query($sql);
		if (!$result || db_numrows($result) < 1) {
			//skill not already in inventory
			$sql="INSERT INTO people_skill_inventory (user_id,skill_id,skill_level_id,skill_year_id) ".
				"VALUES ('". user_getid() ."','$skill_id','$skill_level_id','$skill_year_id')";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' ERROR inserting into skill inventory ';
				echo db_error();
			} else {
				$feedback .= ' Added to skill inventory ';
			}
		} else {
			$feedback .= ' ERROR - skill already in your inventory ';
		}
	} else {
		echo '<H1>You must be logged in first</H1>';
	}
}

function people_show_skill_inventory($user_id) {
	$sql="SELECT people_skill.name AS skill_name, people_skill_level.name AS level_name, people_skill_year.name AS year_name ".
		"FROM people_skill_year,people_skill_level,people_skill,people_skill_inventory ".
		"WHERE people_skill_year.skill_year_id=people_skill_inventory.skill_year_id ".
		"AND people_skill_level.skill_level_id=people_skill_inventory.skill_level_id ".
		"AND people_skill.skill_id=people_skill_inventory.skill_id ".
		"AND people_skill_inventory.user_id='$user_id'";
	$result=db_query($sql);

	$title_arr=array();
	$title_arr[]='Skill';
	$title_arr[]='Level';
	$title_arr[]='Experience';

	echo html_build_list_table_top ($title_arr);

	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<H2>No Skill Inventory Set Up</H2>';
		echo db_error();
	} else {
		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
				<TD>'.db_result($result,$i,'skill_name').'</TD>
				<TD>'.db_result($result,$i,'level_name').'</TD>
				<TD>'.db_result($result,$i,'year_name').'</TD></TR>';

		}
	}
	echo '
		</TABLE>';
}

function people_edit_skill_inventory($user_id) {
	global $PHP_SELF;
	$sql="SELECT * FROM people_skill_inventory WHERE user_id='$user_id'";
	$result=db_query($sql);

	$title_arr=array();
	$title_arr[]='Skill';
	$title_arr[]='Level';
	$title_arr[]='Experience';
	$title_arr[]='Action';

	echo html_build_list_table_top ($title_arr);

	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<TR><TD COLSPAN="4"><H2>No Skill Inventory Set Up</H2></TD></TR>';
		echo db_error();
	} else {
		for ($i=0; $i < $rows; $i++) {
			echo '
			<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="skill_inventory_id" VALUE="'.db_result($result,$i,'skill_inventory_id').'">
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">'. people_get_skill_name(db_result($result,$i,'skill_id')) .'</TD>
				<TD><FONT SIZE="-1">'. people_skill_level_box('skill_level_id',db_result($result,$i,'skill_level_id')). '</TD>
				<TD><FONT SIZE="-1">'. people_skill_year_box('skill_year_id',db_result($result,$i,'skill_year_id')). '</TD>
				<TD NOWRAP><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="update_skill_inventory" VALUE="Update"> &nbsp; 
					<INPUT TYPE="SUBMIT" NAME="delete_from_skill_inventory" VALUE="Delete"></TD>
				</TR></FORM>';
		}

	}
	//add a new skill
	$i++; //for row coloring
	
	echo '
	<TR><TD COLSPAN="4"><H3>Add A New Skill</H3></TD></TR>
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
		<TD><FONT SIZE="-1">'. people_skill_box('skill_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_level_box('skill_level_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_year_box('skill_year_id'). '</TD>
		<TD NOWRAP><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="add_to_skill_inventory" VALUE="Add Skill"></TD>
	</TR></FORM>';

	echo '
		</TABLE>';
}


function people_add_to_job_inventory($job_id,$skill_id,$skill_level_id,$skill_year_id) {
	global $feedback;
	if (user_isloggedin()) {
		//check if they've already added this skill
		$sql="SELECT * FROM people_job_inventory WHERE job_id='$job_id' AND skill_id='$skill_id'";
		$result=db_query($sql);
		if (!$result || db_numrows($result) < 1) {
			//skill isn't already in this inventory
			$sql="INSERT INTO people_job_inventory (job_id,skill_id,skill_level_id,skill_year_id) ".
				"VALUES ('$job_id','$skill_id','$skill_level_id','$skill_year_id')";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' ERROR inserting into skill inventory ';
				echo db_error();
			} else {
				$feedback .= ' Added to skill inventory ';
			}
		} else {
			$feedback .= ' ERROR - skill already in your inventory ';
		}

	} else {
		echo '<H1>You must be logged in first</H1>';
	}
}

function people_show_job_inventory($job_id) {
	$sql="SELECT people_skill.name AS skill_name, people_skill_level.name AS level_name, people_skill_year.name AS year_name ".
		"FROM people_skill_year,people_skill_level,people_skill,people_job_inventory ".
		"WHERE people_skill_year.skill_year_id=people_job_inventory.skill_year_id ".
		"AND people_skill_level.skill_level_id=people_job_inventory.skill_level_id ".
		"AND people_skill.skill_id=people_job_inventory.skill_id ".
		"AND people_job_inventory.job_id='$job_id'";
	$result=db_query($sql);

	$title_arr=array();
	$title_arr[]='Skill';
	$title_arr[]='Level';
	$title_arr[]='Experience';
			
	echo html_build_list_table_top ($title_arr);

	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<H2>No Skill Inventory Set Up</H2>';
		echo db_error();
	} else {
		for ($i=0; $i < $rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
				<TD>'.db_result($result,$i,'skill_name').'</TD>
				<TD>'.db_result($result,$i,'level_name').'</TD>
				<TD>'.db_result($result,$i,'year_name').'</TD></TR>';

		}
	}
	echo '
		</TABLE>';
}

function people_verify_job_group($job_id,$group_id) {
	$sql="SELECT * FROM people_job WHERE job_id='$job_id' AND group_id='$group_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return false;
	} else {
		return true;
	}
}

function people_get_skill_name($skill_id) {
	$sql="SELECT name FROM people_skill WHERE skill_id='$skill_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return 'Invalid ID';
	} else {
		return db_result($result,0,'name');
	}
}

function people_get_category_name($category_id) {
	$sql="SELECT name FROM people_job_category WHERE category_id='$category_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return 'Invalid ID';
	} else {
		return db_result($result,0,'name');
	}
}

function people_edit_job_inventory($job_id,$group_id) {
	global $PHP_SELF;
	$sql="SELECT * FROM people_job_inventory WHERE job_id='$job_id'";
	$result=db_query($sql);

	$title_arr=array();
	$title_arr[]='Skill';
	$title_arr[]='Level';
	$title_arr[]='Experience';
	$title_arr[]='Action';
			
	echo html_build_list_table_top ($title_arr);

	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '
			<TR><TD COLSPAN="4"><H2>No Skill Inventory Set Up</H2></TD></TR>';
		echo db_error();
	} else {
		for ($i=0; $i < $rows; $i++) {
			echo '
			<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="job_inventory_id" VALUE="'. db_result($result,$i,'job_inventory_id') .'">
			<INPUT TYPE="HIDDEN" NAME="job_id" VALUE="'. db_result($result,$i,'job_id') .'">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">'. people_get_skill_name(db_result($result,$i,'skill_id')) . '</TD>
				<TD><FONT SIZE="-1">'. people_skill_level_box('skill_level_id',db_result($result,$i,'skill_level_id')). '</TD>
				<TD><FONT SIZE="-1">'. people_skill_year_box('skill_year_id',db_result($result,$i,'skill_year_id')). '</TD>
				<TD NOWRAP><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="update_job_inventory" VALUE="Update"> &nbsp; 
					<INPUT TYPE="SUBMIT" NAME="delete_from_job_inventory" VALUE="Delete"></TD>
				</TR></FORM>';
		}

	}
	//add a new skill
	$i++; //for row coloring

	echo '
	<TR><TD COLSPAN="4"><H3>Add A New Skill</H3></TD></TR>
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="job_id" VALUE="'. $job_id .'">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TR BGCOLOR="'. util_get_alt_row_color($i) .'">
		<TD><FONT SIZE="-1">'. people_skill_box('skill_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_level_box('skill_level_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_year_box('skill_year_id'). '</TD>
		<TD NOWRAP><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="add_to_job_inventory" VALUE="Add Skill"></TD>
	</TR></FORM>';

	echo '
		</TABLE>';
}

function people_show_category_table() {

	//show a list of categories in a table
	//provide links to drill into a detail page that shows these categories

	$title_arr=array();
	$title_arr[]='Category';

	$return .= html_build_list_table_top ($title_arr);

	$sql="SELECT * FROM people_job_category ORDER BY category_id";
	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		$return .= '<TR><TD><H2>No Categories Found</H2></TD></TR>';
	} else {
		for ($i=0; $i<$rows; $i++) {
			$count_res=db_query("SELECT count(*) AS count FROM people_job WHERE category_id='". db_result($result,$i,'category_id') ."' AND status_id='1'");
			echo db_error();
			$return .= '<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD><A HREF="/people/?category_id='. 
				db_result($result,$i,'category_id') .'">'. 
				db_result($result,$i,'name') .'</A> ('. db_result($count_res,0,'count') .')</TD></TR>';
		}
	}
	$return .= '</TABLE>';
	return $return;
}

function people_show_project_jobs($group_id) {
	//show open jobs for this project
	$sql="SELECT people_job.group_id,people_job.job_id,groups.group_name,people_job.title,people_job.date,people_job_category.name AS category_name ".
		"FROM people_job,people_job_category,groups ".
		"WHERE people_job.group_id='$group_id' ".
		"AND people_job.group_id=groups.group_id ".
		"AND people_job.category_id=people_job_category.category_id ".
		"AND people_job.status_id=1 ORDER BY date DESC";
	$result=db_query($sql);

	return people_show_job_list($result);
}

function people_show_category_jobs($category_id) {
	//show open jobs for this category
	$sql="SELECT people_job.group_id,people_job.job_id,groups.unix_group_name,groups.group_name,people_job.title,people_job.date,people_job_category.name AS category_name ".
		"FROM people_job,people_job_category,groups ".
		"WHERE people_job.category_id='$category_id' ".
		"AND people_job.group_id=groups.group_id ".
		"AND people_job.category_id=people_job_category.category_id ".
		"AND people_job.status_id=1 ORDER BY date DESC";
	$result=db_query($sql);

	return people_show_job_list($result);
}

function people_show_job_list($result) {
	global $sys_datefmt;
	//takes a result set from a query and shows the jobs

	//query must contain 'group_id', 'job_id', 'title', 'category_name' and 'status_name'

	$title_arr=array();
	$title_arr[]='Title';
	$title_arr[]='Category';
	$title_arr[]='Date Opened';
	$title_arr[]='SF Project';

	$return .= html_build_list_table_top ($title_arr);

	$rows=db_numrows($result);
	if ($rows < 1) {
		$return .= '<TR><TD COLSPAN="3"><H2>None Found</H2>'. db_error() .'</TD></TR>';
	} else {
		for ($i=0; $i < $rows; $i++) {	
			$return .= '
				<TR BGCOLOR="'. util_get_alt_row_color($i) .
					'"><TD><A HREF="/people/viewjob.php?group_id='. 
					db_result($result,$i,'group_id') .'&job_id='. 
					db_result($result,$i,'job_id') .'">'. 
					db_result($result,$i,'title') .'</A></TD><TD>'. 
					db_result($result,$i,'category_name') .'</TD><TD>'. 
					date($sys_datefmt,db_result($result,$i,'date')) .
					'</TD><TD><a href="/projects/'.strtolower(db_result($result,$i,'unix_group_name')).'/">'.
					db_result($result,$i,'group_name') .'</a></TD></TR>';
		}
	}

	$return .= '</TABLE>';

	return $return;
}

?>
