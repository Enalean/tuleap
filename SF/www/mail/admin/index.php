<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../mail_utils.php');

if ($group_id && user_ismember($group_id,'A')) {

	if ($post_changes) {
		/*
			Update the DB to reflect the changes
		*/

		if ($add_list) {
			$list_password = substr(md5($GLOBALS['session_hash'] . time() . rand(0,40000)),0,16);
			if (!$list_name || strlen($list_name) < 4) {
				exit_error('Error','Must Provide List Name That Is 4 or More Characters Long');
			}
			$new_list_name=strtolower(group_getunixname($group_id).'-'.$list_name);

			//see if that's a valid email address
			if (validate_email($new_list_name.'@'.$GLOBALS['sys_lists_host'])) {

				$result=db_query("SELECT * FROM mail_group_list WHERE lower(list_name)='$new_list_name'");

				if (db_numrows($result) > 0) {

					$feedback .= " ERROR - List Already Exists ";

				} else {
					$sql = "INSERT INTO mail_group_list "
					. "(group_id,list_name,is_public,password,list_admin,status,description) VALUES ("
					. "$group_id,"
					. "'$new_list_name',"
					. "'$is_public',"
					. "'$list_password',"
					. "'".user_getid()."',"
					. "1,"
					. "'". htmlspecialchars($description) ."')";


					$result=db_query($sql);
					if (!$result) {
						$feedback .= " Error Adding List ";
						echo db_error();
					} else {
						$feedback .= " List Added ";
					}
			
					// get email addr
					$res_email = db_query("SELECT email FROM user WHERE user_id='".user_getid()."'");
					if (db_numrows($res_email) < 1) {
						exit_error("Invalid userid","Does not compute.");
					}
					$row_email = db_fetch_array($res_email);

					// mail password to admin
					$message = "A mailing list will be created on ".$GLOBALS['sys_name']." in less than ".$GLOBALS['sys_crondelay']." hours \n"
					. "and you are the list administrator.\n\n"
					. "This list is: $new_list_name@" .$GLOBALS['sys_lists_host'] ."\n\n"
					. "Your mailing list info is at:\n"
					. "http://".$GLOBALS['sys_lists_host']."/mailman/listinfo/$new_list_name\n\n"
					. "List administration can be found at:\n"
					. "http://".$GLOBALS['sys_lists_host']."/mailman/admin/$new_list_name\n\n"
					. "Your list password is: $list_password\n"
					. "You are encouraged to change this password as soon as possible.\n\n"
					. "Thank you for registering your project with ".$GLOBALS['sys_name']."\n\n"
					. " -- The ".$GLOBALS['sys_name']." Team\n";

					mail ($row_email['email'],$GLOBALS['sys_name']." New Mailing List",$message,"From: ".$GLOBALS['sys_email_admin']);
 
					$feedback .= " Email sent with details to: $row_email[email] ";
				}
			} else {

				$feedback .= " Invalid List Name ";

			}

		} else if ($change_status) {
			/*
				Change a list to public/private and description
			*/
			$sql="UPDATE mail_group_list SET is_public='$is_public', ".
				"description='". htmlspecialchars($description) ."' ".
				"WHERE group_list_id='$group_list_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= " Error Updating Status ";
				echo db_error();
			} else {
				$feedback .= " Status Updated Successfully ";
			}
		}

	} 

	if ($add_list) {
		/*
			Show the form for adding forums
		*/
		mail_header(array('title'=>'Add a Mailing List'));

		echo '
			<H3>Add a Mailing List</H3>
			<P>Lists are named in this manner: <em>projectname-listname@'. $GLOBALS['sys_lists_host'] .'</em>
<P> In order to harmonize mailing lists names on '.$GLOBALS['sys_name'].' we advise you to create (at least) the following mailing lists for your project:<BR>
<ul>
<li><b>'.group_getunixname($group_id).'-interest</b>: for general purpose discussion especially at user level.
<li><b>'.group_getunixname($group_id).'-devel</b>: for developement questions and debates.
<li><b>'.group_getunixname($group_id).'-announce</b>: for annoucement of new releases or any new event in the life of the project.
</ul>
			<P>It will take <B><FONT COLOR="RED">'.$GLOBALS['sys_crondelay'].' Hours</FONT></B>  maximum for your list(s)
			to be created.
			<P>';
		$result=db_query("SELECT list_name FROM mail_group_list WHERE group_id='$group_id'");
		ShowResultSet($result,'Existing Mailing Lists');

		echo 	'<P>
			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="add_list" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<B>Mailing List Name:</B><BR>
			<B>'.group_getunixname($group_id).'-<INPUT TYPE="TEXT" NAME="list_name" VALUE="" SIZE="10" MAXLENGTH="12">@'.$GLOBALS['sys_lists_host'].'</B><BR>
			<P>
			<B>Is Public? </B>(Public means subscription right is granted to any Xerox employee)<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> Yes<BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> No<P>
			<B>Description:</B><BR>
			<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="40" MAXLENGTH="80"><BR>
			<P>
			<B><FONT COLOR="RED">Once created, this list will ALWAYS be attached to your project 
			and cannot be deleted!</FONT></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This List">
			</FORM>';

		mail_footer(array());

	} else if ($change_status) {
		/*
			Change a forum to public/private
		*/
		mail_header(array('title'=>'Update Mailing Lists'));

		$sql="SELECT list_name,group_list_id,is_public,description ".
			"FROM mail_group_list ".
			"WHERE group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if (!$result || $rows < 1) {
			echo '
				<H2>No Lists Found</H2>
				<P>
				None found for this project';
			echo db_error();
		} else {
			echo '
			<H2>Update Mailing Lists</H2>
			<P>
			You can administrate lists from here. Please note that private lists
			can still be viewed by members of your project, but are not listed on '.$GLOBALS['sys_name'].'<P>';

			$title_arr=array();
			$title_arr[]='List';
			$title_arr[]='Status';
			$title_arr[]='Update';
			$title_arr[]='List Admin';

			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i<$rows; $i++) {
				echo '
					<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'list_name').'</TD>';
				echo '
					<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_list_id" VALUE="'.db_result($result,$i,'group_list_id').'">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
					<TD>
						<FONT SIZE="-1">
						<B>Is Public?</B><BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"'.((db_result($result,$i,'is_public')=='1')?' CHECKED':'').'> Yes<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"'.((db_result($result,$i,'is_public')=='0')?' CHECKED':'').'> No<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="9"'.((db_result($result,$i,'is_public')=='9')?' CHECKED':'').'> Deleted<BR>
					</TD><TD>
						<FONT SIZE="-1">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Update">
					</TD>
					<TD><A href="http://'. $GLOBALS['sys_lists_host'] .'/mailman/admin/'
					.db_result($result,$i,'list_name').'">[Administrate this list in GNU Mailman]</A>
				       </TD></TR>
				       <TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD COLSPAN="3">
				       		<B>Description:</B><BR>
						<INPUT TYPE="TEXT" NAME="description" VALUE="'.
						db_result($result,$i,'description') .'" SIZE="40" MAXLENGTH="80"><BR>
					</TD></TR></FORM>';
			}
			echo '</TABLE>';
		}

		mail_footer(array());


	} else {
		/*
			Show main page for choosing 
			either moderotor or delete
		*/
		mail_header(array('title'=>'Mailing List Administration'));

		echo '
			<H2>Mailing List Administration</H2>
			<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&add_list=1">Add Mailing List</A><BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&change_status=1">Administrate/Update Lists</A>';
		mail_footer(array());
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
