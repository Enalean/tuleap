<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../support_utils.php');
require_once('www/project/admin/project_admin_utils.php');

if ($group_id && user_ismember($group_id,'S2')) {

	if ($post_changes) {
		/*
			Update the database
		*/

		if ($support_cat) {

			$sql="INSERT INTO support_category (group_id,category_name) VALUES ('$group_id','$cat_name')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting value ';
				echo db_error();
			} else {
				$feedback .= ' Support Request Category Inserted ';
			}

		} else if ($create_canned) {

			$sql="INSERT INTO support_canned_responses (group_id,title,body) VALUES ('$group_id','". addslashes(htmlspecialchars($title)). "','". addslashes(htmlspecialchars($body)). "')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error inserting value ';
				echo db_error();
			} else {
				$feedback .= ' Canned Response Inserted ';
			}

		} else if ($update_canned) {

			$sql="UPDATE support_canned_responses SET title='". addslashes(htmlspecialchars($title)). "', body='". addslashes(htmlspecialchars($body)). "' ".
				"WHERE group_id='$group_id' AND support_canned_id='$support_canned_id'";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' Error updating value ';
				echo db_error();
			} else {
				$feedback .= ' Canned Response Updated ';
			}

		} else if ($support_cat_mod) {

			/*
				Update a support category name
			*/
			$sql="UPDATE support_category SET category_name = '$support_cat_name' WHERE support_category_id='$support_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Error modifying support category name ';
			} else {
				$feedback .= ' Support Category Name Modified ';
			}

		} else if ($other_settings_update) {

		    group_add_history ('Changed Support Request Settings','',$group_id);
		    //blank out any invalid email addresses
		    if ($new_support_address && !validate_emails($new_support_address)) { 
			$new_support_address='';
			$feedback .= ' Email Address Appeared Invalid ';
		    }	    

		    // Update the Group  table now
		    $result=db_query('UPDATE groups SET '
		     ."send_all_support='$send_all_support', "
	             .($new_support_address? "new_support_address='$new_support_address', " : "")
	             ."support_preamble='".htmlspecialchars($form_preamble)."' "
	             ."WHERE group_id=$group_id");

		    if (!$result) {
			$feedback .= ' UPDATE FAILED! '.db_error();
		    } else if (db_affected_rows($result) < 1) {
			$feedback .= ' NO DATA CHANGED! ';
		    } else {
			$feedback .= ' SUCCESSFUL UPDATE';
		    }
		}

	} 
	/*
		Show UI forms
	*/

	if ($support_cat) {
		/*
			Show categories and blank row
		*/
		support_header_admin(array ('title'=>'Manage SR Categories',
		      'help' => 'SupportRequestManagerAdministration.html#DefiningSupportRequestCategories'));

		echo "<H2>Add Support Request Categories</H2>";

		/*
			List of possible categories for this group
		*/
		$sql="select support_category_id,category_name from support_category WHERE group_id='$group_id'";
		$result=db_query($sql);
		echo "<P>";
		if ($result && db_numrows($result) > 0) {
			ShowResultSet($result,"Existing Categories","support_cat");
		} else {
			echo "\n<H3>No support categories in this group</H3>";
		}
		?>
		<P>
		Add a new support category:
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="support_cat" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<H3>New Category Name:</H3>
		<P>
		<INPUT TYPE="TEXT" NAME="cat_name" VALUE="" SIZE="15" MAXLENGTH="30"><BR>
		<P>
		<B><span class="highlight">Once you add a support category, it cannot be deleted or modified</span></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		support_footer(array());

	} else if ($create_canned) {
		/*
			Show categories and blank row
		*/
		support_header_admin(array ('title'=>'Manage Canned Responses',
		   'help' => 'SupportRequestManagerAdministration.html#DefiningCannedResponses'));

		echo "<H2>Add Canned Responses</H2>";

		/*
			List of possible categories for this group
		*/
		$sql="SELECT support_canned_id,title FROM support_canned_responses WHERE group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		echo "<P>";

		if ($result && $rows > 0) {
			//code to show existing responses and link to update page
			echo '
			<H3>Existing Responses:</H3>
			<P>';
			$title_arr=array();
			$title_arr[]='ID';
			$title_arr[]='Title';

			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i < $rows; $i++) {
				echo '<TR class="'. html_get_alt_row_color($i) .'">'.
					'<TD>'.db_result($result, $i, 'support_canned_id').'</TD>'.
					'<TD><A HREF="'.$PHP_SELF.'?update_canned=1&support_canned_id='.
						db_result($result, $i, 'support_canned_id').'&group_id='.$group_id.'">'.
						db_result($result, $i, 'title').'</A></TD></TR>';
			}
			echo '</TABLE>';

		} else {
			echo "\n<H3>No responses set up in this group</H3>";
		}
		?>
		<P>
		Creating useful generic messages can save you a lot of time when 
		handling common support requests.
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="create_canned" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<b>Title:</b><BR>
		<INPUT TYPE="TEXT" NAME="title" VALUE="" SIZE="15" MAXLENGTH="30">
		<P>
		<B>Message Body:</B><BR>
		<TEXTAREA NAME="body" ROWS="30" COLS="65"></TEXTAREA>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		support_footer(array());

	} else if ($update_canned) {
		/*
			Show categories and blank row
		*/
		support_header_admin(array ('title'=>'Update Canned Responses',
					    'help' => 'SupportRequestManagerAdministration.html#DefiningCannedResponses'));

		echo "<H2>Update Canned Responses</H2>";

		/*
			List of possible categories for this group
		*/
		$sql="SELECT support_canned_id,title,body ".
			"FROM support_canned_responses ".
			"WHERE group_id='$group_id' ".
			"AND support_canned_id='$support_canned_id'";

		$result=db_query($sql);
		echo "<P>";
		if (!$result || db_numrows($result) < 1) {
			echo "\n<H3>No responses set up in this group</H3>";
		} else {
			?>
			<P>
			Creating useful generic messages can save you a lot of time when
			handling common support requests.
			<P>
			<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="update_canned" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="support_canned_id" VALUE="<?php echo $support_canned_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<b>Title:</b><BR>
			<INPUT TYPE="TEXT" NAME="title" VALUE="<?php echo stripslashes(db_result($result,0,'title')); ?>" SIZE="15" MAXLENGTH="30">
			<P>
			<B>Message Body:</B><BR>
			<TEXTAREA NAME="body" ROWS="30" COLS="65" WRAP="HARD"><?php echo stripslashes(db_result($result,0,'body')); ?></TEXTAREA>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		}
		support_footer(array());

	} else if ($support_cat_mod) {

		/*
			Allow modification of a support category
		*/
		support_header_admin(array('title'=>'Change a Support Manager Category',
					   'help' => 'SupportRequestManagerAdministration.html#DefiningSupportRequestCategories'));

		echo '
			<H2>Modify a Support Category</H2>';

		$sql="SELECT support_category_id,category_name FROM support_category WHERE support_category_id='$support_cat_id' AND group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if($result && $rows > 0) {
			?>
			<P>
			<FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="support_cat_mod" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="support_cat_id" VALUE="<?php echo $support_cat_id; ?>">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
			<H3>Old Support Category Name: <?php echo db_result($result, 0, 'category_name'); ?></H3>
			<P>
			<H3>New Support Category Name:</H3>
			<P>
			<INPUT TYPE="TEXT" NAME="support_cat_name" VALUE="<?php 
				echo db_result($result, 0, 'category_name'); ?>">
			<P>
			<B><span class="highlight">It is not recommended that you change the support category name because other things are dependent upon it.</span></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		} else {
			echo '
			<H3>The support category that you requested a modification on was not found.</H3>';
		}

		support_footer(array());

	} else if ($other_settings) {
	    
	    /*     Show existing values    */
		support_header_admin(array ('title'=>'Support Request Admin - Other Settings',
		      'help' => 'SupportRequestManagerAdministration.html#SupportRequestManagerOtherConfigurationSettings'));
		$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
		if (db_numrows($res_grp) < 1) {
		    exit_no_group();
		}
		$row_grp = db_fetch_array($res_grp);

		echo '<H2>Other Configuration Settings</h2>';

		echo '<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<INPUT type="hidden" name="other_settings" value="y">
<INPUT type="hidden" name="other_settings_update" value="y">
<INPUT type="hidden" name="post_changes" value="y">
<h3>Submission Form Preamble</h3>
<P><b>Introductory message showing at the top of  the Support Request submission form :</b>
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.
$row_grp['support_preamble'].'</TEXTAREA>';


echo '<h3>Email Notification Rules</h3>
              <P><B>If you wish, you can provide email addresses (separated by a comma) to which new Support Request (SR) submissions will be sent .</B><BR>
              (Remark: SR submission and updates are always sent to the SR submitter and assignee as well as all people who have posted a follow-up comment)<br>
	<BR><INPUT TYPE="TEXT" NAME="new_support_address" VALUE="'.$row_grp['new_support_address'].'" SIZE="55"> 
	&nbsp;&nbsp;&nbsp;(send on all updates) <INPUT TYPE="CHECKBOX" NAME="send_all_support" VALUE="1" '. (($row_grp['send_all_support'])?'CHECKED':'') .'><BR>';

echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

		
		support_footer(array());


	} else {
		/*
			Show main page
		*/

		support_header_admin(array ('title'=>'Support Manager Administration',
					    'help' => 'SupportRequestManagerAdministration.html'));

		echo '
			<H2>Support Manager Administration</H2>';

		echo '<h3>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&support_cat=1">Manage Support Request Categories</A></h3>';
		echo "\nCreate/Modify categories of support like, 'mail module','gant chart module','cvs', etc";
		echo '<h3>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&create_canned=1">Manage Canned Responses</A></h3>';
		echo "\nCreate/Update generic response messages for the support tool.";
		echo '<h3>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&other_settings=1">Other Configuration Settings</A></h3>';
		echo "\nDefine introductory messages for submission forms, email notification,...";

		support_footer(array());
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
