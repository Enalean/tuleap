<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../patch_utils.php');

if ($group_id && user_ismember($group_id,'C2')) {

	if ($post_changes) {
		/*
			Update the database
		*/

		if ($patch_cat) {

			$sql="INSERT INTO patch_category VALUES ('', '$group_id','$cat_name')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting value ';
				echo db_error();
			}

			$feedback .= ' Patch Category Inserted ';

		} else if ($patch_cat_mod) {

			$sql="UPDATE patch_category SET category_name='$cat_name' WHERE patch_category_id='$patch_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying patch category ';
			} else {
				$feedback .= ' Successfully modified patch category ';
				echo db_error();
			}

		}

	} 
	/*
		Show UI forms
	*/

	if ($patch_cat) {
		/*
			Show categories and blank row
		*/
		patch_header(array ('title'=>'Add/Change Categories'));

		echo "<H1>Add Patch Categories</H1>";

		/*
			List of possible categories for this group
		*/
		$sql="select patch_category_id,category_name from patch_category WHERE group_id='$group_id'";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,"Existing Categories","patch_cat");
		} else {
			echo "\n<H1>No patch categories in this group</H1>";
		}
		?>
		<P>
		Add a new patch category:
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="patch_cat" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H3>New Category Name:</H3>
		<INPUT TYPE="TEXT" NAME="cat_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><FONT COLOR="RED">Once you add a patch category, it cannot be deleted</FONT></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		patch_footer(array());

	} else if ($patch_cat_mod) {

		/*
			Show an interface to modify the description to $patch_cat_id
		*/

		patch_header(array ('title'=>'Modify a Patch Category'));

		echo '
			<H1>Patch Category Modification</H1>';

		$sql="SELECT patch_category_id,category_name from patch_category WHERE patch_category_id='$patch_cat_id' AND group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if ($result && $rows > 0) {
			?>
			<FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="patch_cat_mod" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="patch_cat_id" VALUE="<?php echo $patch_cat_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<H3>Old Patch Category Name: &nbsp; &nbsp; <?php echo db_result($result, 0, 'category_name'); ?></H3>
			<H3>New Patch Category Name:</H3>
			<INPUT TYPE="TEXT" NAME="cat_name" VALUE="<?php 
				echo db_result($result, 0, 'category_name'); ?>">
			<P>
			<B><FONT COLOR="RED">It is not recommended that you change the category name because other things are dependant upon it.</FONT></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		} else {
			echo '
				<H1>The patch category that you requested a change on was not found</H1>';
		}

		patch_footer(array());

	} else {
		/*
			Show main page
		*/

		patch_header(array ('title'=>'Patch Administration'));

		echo '
			<H1>Patch Administration</H1>';

		echo '<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&patch_cat=1">Add Patch Categories</A><BR>';
		echo "\nAdd categories of patchs like, 'mail module','gant chart module','interface', etc<P>";

		patch_footer(array());
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
