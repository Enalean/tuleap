<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../patch_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

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

		} else if ($other_settings_update) {

		    group_add_history ('Changed Patch Mgr Settings','',$group_id);
		    //blank out any invalid email addresses
		    if ($new_patch_address && !validate_emails($new_patch_address)) { 
			$new_patch_address='';
			$feedback .= ' Email Address Appeared Invalid ';
		    }	    

		    // Update the Group  table now
		    $result=db_query('UPDATE groups SET '
		     ."send_all_patches='$send_all_patches', "
	             .($new_patch_address? "new_patch_address='$new_patch_address', " : "")
	             ."patch_preamble='".htmlspecialchars($form_preamble)."' "
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

	if ($patch_cat) {
		/*
			Show categories and blank row
		*/
		patch_header_admin(array ('title'=>'Add/Change Categories',
		    'help'=>'PatchManagerAdministration.html#DefiningCategories'));

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
		<B><span class="highlight">Once you add a patch category, it cannot be deleted</span></B>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>
		<?php

		patch_footer(array());

	} else if ($patch_cat_mod) {

		/*
			Show an interface to modify the description to $patch_cat_id
		*/

		patch_header_admin(array ('title'=>'Modify a Patch Category',
		    'help'=>'PatchManagerAdministration.html#DefiningCategories'));

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
			<B><span class="highlight">It is not recommended that you change the category name because other things are dependant upon it.</span></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
			<?php
		} else {
			echo '
				<H1>The patch category that you requested a change on was not found</H1>';
		}

		patch_footer(array());


	} else if ($other_settings) {
	    
	    /*     Show existing values    */
		patch_header_admin(array ('title'=>'Patch Manager Admin - Other Settings',
		    'help'=>'PatchManagerAdministration.html#PatchManagerOtherConfigurationSettings'));
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
<P><b>Introductory message showing at the top of  the Patch  submission form :</b>
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.
$row_grp['patch_preamble'].'</TEXTAREA>';


		echo '<h3>Email Notification Rules</h3>
              <P><B>If you wish, you can provide email addresses (separated by a comma) to which new Patch submissions will be sent.</B><BR>
              (Remark: Patch submission and updates are always sent to the patch submitter and assignee)<br>
	<BR><INPUT TYPE="TEXT" NAME="new_patch_address" VALUE="'.$row_grp['new_patch_address'].'" SIZE="55"> 
	&nbsp;&nbsp;&nbsp;(send on all updates) <INPUT TYPE="CHECKBOX" NAME="send_all_patches" VALUE="1" '. (($row_grp['send_all_patches'])?'CHECKED':'') .'><BR>';

		echo '<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';
		
		patch_footer(array());


	} else {
		/*
			Show main page
		*/

		patch_header_admin(array ('title'=>'Patch Administration',
					  'help'=>'PatchManagerAdministration.html'));

		echo '
			<H2>Patch Administration</H2>';

		echo '<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&patch_cat=1"><h3>Manage Patch Categories</h3></A>';
		echo "\nAdd/Update categories of patchs like, 'mail module','gant chart module','interface', etc<P>";
		echo '<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&other_settings=1"><h3>Other Configuration Settings</h3></A>';
		echo "\nDefine introductory messages for submission forms, email notification,...";

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
