<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../people_utils.php');

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
				$feedback .= ' Error inserting value ';
			}

			$feedback .= ' Category Inserted ';

		} else if ($people_skills) {

			$sql="INSERT INTO people_skill (name) VALUES ('$skill_name')";
			$result=db_query($sql);
			if (!$result) {
				echo db_error();
				$feedback .= ' Error inserting value ';
			}

			$feedback .= ' Skill Inserted ';
/*
		} else if ($people_cat_mod) {

			$sql="UPDATE people_category SET category_name='$cat_name' WHERE people_category_id='$people_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying bug category ';
				echo db_error();
			} else {
				$feedback .= ' Bug Category Modified ';
			}

		} else if ($people_group_mod) {

			$sql="UPDATE people_group SET group_name = '$group_name' WHERE people_group_id='$people_group_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying bug cateogry ';
				echo db_error();
			} else {
				$feedback .= ' Bug Category Modified ';
			}
*/
		}

	} 
	/*
		Show UI forms
	*/

	if ($people_cat) {
		/*
			Show categories and blank row
		*/
		people_header(array ('title'=>'Add/Change Categories'));

		echo "<H2>Add Job Categories</H2>";

		/*
			List of possible categories for this group
		*/
		$sql="select category_id,name from people_job_category";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,'Existing Categories','people_cat');
		} else {
			echo '
				<H1>No job categories</H1>';
			echo db_error();
		}
		?>
		<P>
		<H3>Add a new job category:</H3>
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="people_cat" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H4>New Category Name:</H4>
		<INPUT TYPE="TEXT" NAME="cat_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><FONT COLOR="RED">Once you add a category, it cannot be deleted</FONT></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		people_footer(array());

	} else if ($people_skills) {
		/*
			Show people_groups and blank row
		*/
		people_header(array ('title'=>'Add/Change People Skills'));

		echo '<H2>Add Job Skills</H2>';

		/*
			List of possible people_groups for this group
		*/
		$sql="select skill_id,name from people_skill";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,"Existing Skills","people_skills");
		} else {
			echo db_error();
			echo "\n<H2>No Skills Found</H2>";
		}
		?>
		<P>
		<H3>Add a new skill:</H3>
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="people_skills" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H4>New Skill Name:</H4>
		<INPUT TYPE="TEXT" NAME="skill_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><FONT COLOR="RED">Once you add a skill, it cannot be deleted</FONT></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		people_footer(array());

	} else {
		/*
			Show main page
		*/

		people_header(array ('title'=>'People Administration'));

		echo '
			<H2>Help Wanted Administration</H2>';

		echo '<P>
			<A HREF="'.$PHP_SELF.'?people_cat=1">Add Job Categories</A><BR>';
	//	echo "\nAdd categories of bugs like, 'mail module','gant chart module','interface', etc<P>";

		echo "\n<A HREF=\"$PHP_SELF?people_skills=1\">Add Job Skills</A><BR>";
	//	echo "\nAdd Groups of bugs like 'future requests','unreproducible', etc<P>";

		people_footer(array());
	}

} else {
	exit_permission_denied();
}
?>
