<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../people_utils.php');

$Language->loadLanguageMsg('people/people');

if (user_ismember(1,'A')) {

	if ($post_changes) {
		/*
			Update the database
		*/

		if ($people_cat) {

			$sql="INSERT INTO people_job_category (name) VALUES ('$cat_name')";
			$result=db_query($sql);
			if (!$result) {
				echo db_error();
				$feedback .= ' '.$Language->getText('people_admin_index','insert_error').' ';
			}

			$feedback .= ' '.$Language->getText('people_admin_index','category_inserted').' ';

		} else if ($people_skills) {

			$sql="INSERT INTO people_skill (name) VALUES ('$skill_name')";
			$result=db_query($sql);
			if (!$result) {
				echo db_error();
				$feedback .= ' '.$Language->getText('people_admin_index','insert_error').' ';
			}

			$feedback .= ' '.$Language->getText('people_admin_index','skill_inserted').' ';
/*
		} else if ($people_cat_mod) {

			$sql="UPDATE people_category SET category_name='$cat_name' WHERE people_category_id='$people_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' '.$Language->getText('people_admin_index','bug_cat_modif_error').' ';
				echo db_error();
			} else {
				$feedback .= ' '.$Language->getText('people_admin_index','bug_cat_modified').' ';
			}

		} else if ($people_group_mod) {

			$sql="UPDATE people_group SET group_name = '$group_name' WHERE people_group_id='$people_group_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' '.$Language->getText('people_admin_index','bug_cat_modif_error').' ';
				echo db_error();
			} else {
				$feedback .= ' '.$Language->getText('people_admin_index','bug_cat_modified').' ';
			}
*/
		}

	} 
	/*
		Show UI forms
	*/

	 if ($people_skills) {
		/*
			Show people_groups and blank row
		*/
		people_header(array ('title'=>$Language->getText('people_admin_index','add_people_skills')));

		echo '<H2>'.$Language->getText('people_admin_people_skills','title').'</H2>';

		/*
			List of possible people_groups for this group
		*/
		$sql="select skill_id,name from people_skill";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,$Language->getText('people_admin_index','existing_skills'),"people_skills");
		} else {
			echo db_error();
			echo "\n<H2>".$Language->getText('people_admin_index','no_skills_found')."</H2>";
		}
		echo '
		<P>
		<H3>'.$Language->getText('people_editprofile','add_new_skill').':</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="people_skills" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H4>'.$Language->getText('people_admin_index','new_skill_name').':</H4>
		<INPUT TYPE="TEXT" NAME="skill_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><span class="highlight">'.$Language->getText('people_admin_index','once_added_no_delete').'</span></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>';
		

		people_footer(array());

	} else {
		/*
			Show main page
		*/

		people_header(array ('title'=>$Language->getText('people_admin_index','people_skills_admin')));

		echo '
			<H2>'.$Language->getText('people_admin_index','people_skills_admin').'</H2>';

		echo "\n<h3><A HREF=\"$PHP_SELF?people_skills=1\">".$Language->getText('people_admin_index','add_skills')."</A></h3>";
		echo "<p>".$Language->getText('people_admin_index','add_new_skill_to_list')."</p>";
		people_footer(array());
	}

} else {
	exit_permission_denied();
}
?>
