<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	People Skills finder 
	By Tim Perdue, Sourceforge, March 2000
	Simplified by Laurent Julliard, Xerox Corporation, June 2004 (no job posting)
*/

function people_header($params) {
    global $group_id,$DOCUMENT_ROOT,$HTML;

    echo $HTML->header($params);
    echo '
	   <H2>'.$GLOBALS['sys_name'].' - People Skills</H2>
		<P><B>
	<A HREF="/people/admin/">Admin</A>';
    if ($params['help']) {
	echo ' | '.help_button($params['help'],false,'Help');
    }
    echo '</B>';
    echo '<HR NoShade SIZE="1" SIZE="90%">';
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
		// Order by Skill Level id to have them in a consistent order
		$sql="SELECT * FROM people_skill_level ORDER BY skill_level_id ASC";
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
			<TR class="'. util_get_alt_row_color($i) .'">
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
			<TR class="'. util_get_alt_row_color($i) .'">
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
	<TR class="'. util_get_alt_row_color($i) .'">
		<TD><FONT SIZE="-1">'. people_skill_box('skill_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_level_box('skill_level_id'). '</TD>
		<TD><FONT SIZE="-1">'. people_skill_year_box('skill_year_id'). '</TD>
		<TD NOWRAP><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="add_to_skill_inventory" VALUE="Add Skill"></TD>
	</TR></FORM>';

	echo '
		</TABLE>';
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


?>
