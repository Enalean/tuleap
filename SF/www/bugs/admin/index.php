<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../bug_utils.php');
$is_admin_page='y';

if ($group_id && (user_ismember($group_id,'B2') || user_ismember($group_id,'A'))) {

	if ($post_changes) {
		/*
			Update the database
		*/

		if ($mod_admins) {

			/*
				Post changes for admins
			*/

			$sql="SELECT user_id FROM user_group WHERE group_id='$group_id'";
			$result=db_query($sql);
			$rows=db_numrows($result);

			if ($result && $rows > 0) {
				/*
					Begin iterating and setting the values in the db
				*/
				for ($i=0; $i<$rows; $i++) {
					$user_id=db_result($result,$i,"user_id");
					eval("\$val=\"\$_$user_id\";");
					$sql="UPDATE user_group SET bug_flags='$val' WHERE user_id='$user_id' AND group_id='$group_id'";
					$result2=db_query($sql);
					if (!$result2) {
						$feedback .= " Error Updating User ID $user_id ";
					}
				}
				$feedback .= ' Members Updated ';

			} else {
				bug_header(array ('title'=>'Add/Remove Administrators'));
// LJ				echo '<H1>No members in this group</H1>';
				echo '<H2>No members in this group</H2>';

				bug_footer(array());
				exit;
			}

		} else if ($bug_cat) {

			$sql="INSERT INTO bug_category VALUES ('', '$group_id','$cat_name')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting value ';
			}

			$feedback .= ' Bug Category Inserted ';

		} else if ($bug_group) {

			$sql="INSERT INTO bug_group VALUES ('', '$group_id','$bug_group_name')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting value ';
			}

			$feedback .= ' Bug Group Inserted ';

		} else if ($bug_cat_mod) {

			$sql="UPDATE bug_category SET category_name='$cat_name' WHERE bug_category_id='$bug_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying bug category ';
				echo db_error();
			} else {
				$feedback .= ' Bug Category Modified ';
			}

		} else if ($bug_group_mod) {

			$sql="UPDATE bug_group SET group_name = '$group_name' WHERE bug_group_id='$bug_group_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying bug group ';
				echo db_error();
			} else {
				$feedback .= ' Bug Group Modified ';
			}

	       } else if ($create_canned) {

			$sql="INSERT INTO bug_canned_responses (group_id,title,body) VALUES ('$group_id','". htmlspecialchars($title) . "','". htmlspecialchars($body) ."')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting canned bug response! ';
				echo db_error();
			} else {
				$feedback .= ' Canned bug response inserted ';
			}
 
		} else if ($update_canned) {
 
			$sql="UPDATE bug_canned_responses SET title='". htmlspecialchars($title) ."', body='". htmlspecialchars($body). "' WHERE group_id='$group_id' AND bug_canned_id='$bug_canned_id'";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error updating canned bug response! ';
				echo db_error();
			} else {
				$feedback .= ' Canned bug response updated ';
			}
		}

	} 
	/*
		Show UI forms
	*/

	if ($bug_cat) {
		/*
			Show categories and blank row
		*/
		bug_header(array ('title'=>'Add/Change Categories'));

//LJ		echo "<H1>Add Bug Categories</H1>";
		echo "<H2>Add Bug Categories</H2>";

		/*
			List of possible categories for this group
		*/
		$sql="select bug_category_id,category_name from bug_category WHERE group_id='$group_id'";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,"Existing Categories","bug_cat");
		} else {
//LJ			echo "\n<H1>No bug categories in this group</H1>";
			echo "\n<H2>No bug categories in this group</H2>";
		}
		?>
		<P>
		Add a new bug category:
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="bug_cat" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H3>New Category Name:</H3>
		<INPUT TYPE="TEXT" NAME="cat_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><FONT COLOR="RED">Once you add a bug category, it cannot be deleted</FONT></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		bug_footer(array());

	} else if ($bug_group) {
		/*
			Show bug_groups and blank row
		*/
		bug_header(array ('title'=>'Add/Change Groups'));

// LJ		echo '<H1>Add Bug Groups</H1>';
		echo '<H2>Add Bug Groups</H2>';

		/*
			List of possible bug_groups for this group
		*/
		$sql="select bug_group_id,group_name from bug_group WHERE group_id='$group_id'";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,"Existing Bug Groups","bug_group");
		} else {
//LJ			echo "\n<H1>No bug groups in this project group</H1>";
			echo "\n<H2>No bug groups in this project group</H2>";
		}
		?>
		<P>
		Add a new bug group:
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="bug_group" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H3>New Bug Group Name:</H3>
		<INPUT TYPE="TEXT" NAME="bug_group_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><FONT COLOR="RED">Once you add a bug group, it cannot be deleted</FONT></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		bug_footer(array());

	} else if($bug_cat_mod) {
		/*
			Allow the modification of bug category
		*/
		bug_header(array ('title'=>'Modify A Bug Category'));

//LJ		echo '<H1>Modify A Bug Category</H1>';
		echo '<H2>Modify A Bug Category</H2>';

		$sql="SELECT bug_category_id, category_name FROM bug_category WHERE bug_category_id='$bug_cat_id' AND group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if ($result && $rows > 0) {
			?>
			<P>
			<FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="bug_cat_mod" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="bug_cat_id" VALUE="<?php echo $bug_cat_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<H3>Old Bug Category Name: &nbsp; &nbsp; <?php echo db_result($result, 0, 'category_name'); ?></H3>
			<P>
			<H3>New Bug Category Name:</H3>
			<P>
			<INPUT TYPE="TEXT" NAME="cat_name" VALUE="<?php 
				echo db_result($result, 0, 'category_name'); ?>">
			<P>
			<B><FONT COLOR="RED">It is not recommended that you change the category name because other things are dependant upon it.</FONT></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		} else {
//LJ			<H1>The bug category that you requested a change on was not found.</H1>';
			echo '
			<H2>The bug category that you requested a change on was not found.</H2>';
			echo db_error();
		}

		bug_footer(array());

	} else if($bug_group_mod) {
		/*
			Allow the modification of bug group
		*/
		bug_header(array ('title'=>'Add/Change Groups'));

//LJ		echo '<H1>Modify A Bug Group</H1>';
		echo '<H2>Modify A Bug Group</H2>';

		$sql="SELECT bug_group_id,group_name FROM bug_group WHERE bug_group_id='$bug_group_id' AND group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if ($result && $rows > 0) {
			?>
			<P>
			<FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="bug_group_mod" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="bug_group_id" VALUE="<?php echo $bug_group_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<H3>Old Bug Group Name: &nbsp; &nbsp; <?php echo db_result($result, 0, 'group_name'); ?></H3>
			<P>
			<H3>New Bug Group Name:</H3>
			<P>
			<INPUT TYPE="TEXT" NAME="group_name" VALUE="<?php 
				echo db_result($result, 0, 'group_name'); ?>">
			<P>
			<B><FONT COLOR="RED">It is not recommended that you change the group name because other things are dependant upon it.</FONT></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		} else {
//LJ			<H1>The bug group that you requested a change on was not found</H1>';
			echo '
			<H2>The bug group that you requested a change on was not found</H2>';
			echo db_error();
		}

		bug_footer(array());

	} else if ($create_canned) {
		/*
			Show existing responses and UI form
		*/
		bug_header(array ('title'=>'Create/Modify Canned Responses'));

//LJ		echo "<H1>Create/Modify Canned Responses</H1>";
		echo "<H2>Create/Modify Canned Responses</H2>";

		$sql="SELECT bug_canned_id,title FROM bug_canned_responses WHERE group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		echo "<P>";

		if($result && $rows > 0) {
			/*
				Links to update pages
			*/
			echo '
			<H2>Existing Responses:</H2>
			<P>';

			$title_arr=array();
			$title_arr[]='ID';
			$title_arr[]='Title';
		
			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i < $rows; $i++) {
				echo '<TR BGCOLOR="'. util_get_alt_row_color($i) .'">'.
				'<TD>'.db_result($result, $i, 'bug_canned_id').'</TD>'.
				'<TD><A HREF="'.$PHP_SELF.'?update_canned=1&bug_canned_id='.
				db_result($result, $i, 'bug_canned_id').'&group_id='.$group_id.'">'.
				db_result($result, $i, 'title').'</A></TD></TR>';
			}
			echo '</TABLE>';

		} else {
//LJ			echo "\n<H1>No canned bug responses set up yet</H1>";
			echo "\n<H2>No canned bug responses set up yet</H2>";
		}
		/*
			Escape to print the add response form
		*/
		?>
		<P>
		Creating generic quick responses can save a lot of time when giving common responses.
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="create_canned" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<B>Title:</B><BR>
		<INPUT TYPE="TEXT" NAME="title" VALUE="" SIZE="15" MAXLENGTH="30">
		<P>
		<B>Message Body:</B><BR>
		<TEXTAREA NAME="body" ROWS="30" COLS="65" WRAP="HARD"></TEXTAREA>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		bug_footer(array());

	} else if ($update_canned) {
		/*
			Allow change of canned responses
		*/
		bug_header(array ('title'=>'Modify Canned Response'));

//LJ		echo "<H1>Modify Canned Response</H1>";
		echo "<H2>Modify Canned Response</H2>";

		$sql="SELECT bug_canned_id,title,body FROM bug_canned_responses WHERE ".
		"group_id='$group_id' AND bug_canned_id='$bug_canned_id'";

		$result=db_query($sql);
		echo "<P>";
		if (!$result || db_numrows($result) < 1) {
//LJ			echo "\n<H1>No such response!</H1>";
			echo "\n<H2>No such response!</H2>";
		} else {
			/*
				Escape to print update form
			*/
			?>
			<P>
			Creating generic messages can save you a lot of time when giving common responses.
			<P>
			<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="update_canned" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="bug_canned_id" VALUE="<?php echo $bug_canned_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<B>Title:</B><BR>
			<INPUT TYPE="TEXT" NAME="title" VALUE="<?php echo db_result($result,0,'title'); ?>" SIZE="15" MAXLENGTH="30">
			<P>
			<B>Message Body:</B><BR>
			<TEXTAREA NAME="body" ROWS="30" COLS="65" WRAP="HARD"><?php echo db_result($result,0,'body'); ?></TEXTAREA>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		}

		bug_footer(array());


	} else {
		/*
			Show main page
		*/

		bug_header(array ('title'=>'Bug Administration'));

//LJ			<H1>Bug Administration</H1>';
		echo '<H2>Bug Administration</H2>';

		echo '<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&bug_cat=1">Add Bug Categories</A><BR>';
		echo "\nBug Categories generally correspond to high level modules or functionalities of your software. You can add/define categories of bugs like, 'User interface','Configuration Manager','Programming Interface', etc. <P>";
		echo "\n<A HREF=\"$PHP_SELF?group_id=$group_id&bug_group=1\">Add Bug Groups</A><BR>";
		echo "\nBug groups can help the bug submitter to characterize the nature of the bug. You can add Groups of bugs like 'Feature Request','Crash Error', 'Documentation Typos', 'Installation Problem, etc.<P>";
		echo "\n<A HREF=\"$PHP_SELF?group_id=$group_id&create_canned=1\">Add Canned Responses</A><BR>";
		echo "\nCreate or Change generic quick response messages for the bug tracking tool. This pre-written messages can then be used to quickly reply to bug submission. <P>";

		bug_footer(array());
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
