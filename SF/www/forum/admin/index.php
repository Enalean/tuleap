<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../forum_utils.php');
$is_admin_page='y';

if ($group_id && (user_ismember($group_id, 'F2'))) {

	if ($post_changes) {
		/*
			Update the DB to reflect the changes
		*/

		if ($delete) {
			/*
				Deleting messages or threads
			*/

			/*
				Get this forum_id, checking to make sure this forum is in this group
			*/
			$sql="SELECT forum.group_forum_id FROM forum,forum_group_list WHERE forum.group_forum_id=forum_group_list.group_forum_id ".
				"AND forum_group_list.group_id='$group_id' AND forum.msg_id='$msg_id'";

			$result=db_query($sql);

			if (db_numrows($result) > 0) {
				$feedback .= recursive_delete($msg_id,db_result($result,0,'group_forum_id'))." messages deleted ";
			} else {
				$feedback .= " Message not found or message is not in your group ";
			}

		} else if ($add_forum) {
			/*
				Adding forums to this group
			*/
			forum_create_forum($group_id,$forum_name,$is_public,1,$description);

		} else if ($change_status) {
			/*
				Change a forum to public/private
			*/
			$sql="UPDATE forum_group_list SET is_public='$is_public',forum_name='". htmlspecialchars($forum_name) ."',".
				"description='". htmlspecialchars($description) ."' ".
				"WHERE group_forum_id='$group_forum_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= " Error Updating Forum Info ";
			} else {
				$feedback .= " Forum Info Updated Successfully ";
			}
		}

	} 

	if ($delete) {
		/*
			Show page for deleting messages
		*/
		forum_header(array('title'=>'Delete a message'));

		echo '
			<H2>Delete a message</H2>

			<FONT COLOR="RED" SIZE="3">WARNING! You are about to permanently delete a 
			message and all of its followups!!</FONT>
			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="delete" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<B>Enter the Message ID</B><BR>
			<INPUT TYPE="TEXT" NAME="msg_id" VALUE="">
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>';

		forum_footer(array());

	} else if ($add_forum) {
		/*
			Show the form for adding forums
		*/
		forum_header(array('title'=>'Add a Forum'));

		$sql="SELECT forum_name FROM forum_group_list WHERE group_id='$group_id'";
		$result=db_query($sql);
		ShowResultSet($result,'Existing Forums');

		echo '
			<P>
			<H2>Add a Forum</H2>

			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="add_forum" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<B>Forum Name:</B><BR>
			<INPUT TYPE="TEXT" NAME="forum_name" VALUE="" SIZE="30" MAXLENGTH="50"><BR>
			<B>Description:</B><BR>
			<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="60" MAXLENGTH="255"><BR>
			<B>Is Public?</B><BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> Yes<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> No<P>
			<P>
			<B><FONT COLOR="RED">Once you add a forum, it cannot be modified or deleted!</FONT></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This Forum">
			</FORM>';

		forum_footer(array());

	} else if ($change_status) {
		/*
			Change a forum to public/private
		*/
		forum_header(array('title'=>'Change Forum Status'));

		$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if (!$result || $rows < 1) {
			echo '
				<H2>No Forums Found</H2>
				<P>
				None found for this project';
		} else {
			echo '
			<H2>Update Forum Status</H2>
			<P>
			You can make forums private from here. Please note that private forums 
			can still be viewed by members of your project, not the general public.<P>';

			$title_arr=array();
			$title_arr[]='Forum';
			$title_arr[]='Status';
			$title_arr[]='Update';
		
			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i<$rows; $i++) {
				echo '
					<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'forum_name').'</TD>';
				echo '
					<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_forum_id" VALUE="'.db_result($result,$i,'group_forum_id').'">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
					<TD>
						<FONT SIZE="-1">
						<B>Is Public?</B><BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"'.((db_result($result,$i,'is_public')=='1')?' CHECKED':'').'> Yes<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"'.((db_result($result,$i,'is_public')=='0')?' CHECKED':'').'> No<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="9"'.((db_result($result,$i,'is_public')=='9')?' CHECKED':'').'> Deleted<BR>
					</TD><TD>
						<FONT SIZE="-1">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Update Status">
					</TD></TR>
					<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD COLSPAN="3">
						<B>Forum Name:</B><BR>
						<INPUT TYPE="TEXT" NAME="forum_name" VALUE="'. db_result($result,$i,'forum_name').'" SIZE="30" MAXLENGTH="50"><BR>
						<B>Description:</B><BR>
						<INPUT TYPE="TEXT" NAME="description" VALUE="'. db_result($result,$i,'description') .'" SIZE="60" MAXLENGTH="255"><BR>
					</TD></TR></FORM>';
			}
			echo '</TABLE>';
		}

		forum_footer(array());

	} else {
		/*
			Show main page for choosing 
			either moderotor or delete
		*/
		forum_header(array('title'=>'Forum Administration'));

		echo '
			<H2>Forum Administration</H2>
			<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&add_forum=1">Add Forum</A><BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&delete=1">Delete Message</A><BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&change_status=1">Update Forum Info/Status</A>';

		forum_footer(array());
	}

} else {
	/*
		Not logged in or insufficient privileges
	*/
	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}
}
?>
