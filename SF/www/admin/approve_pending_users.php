<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('account.php');
require ('proj_email.php');
require($DOCUMENT_ROOT.'/admin/admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

// group public choice
if ($action=='activate') {

    // update the user status flag to active
    db_query("UPDATE user SET status='A'"
	     . " WHERE user_id IN ($list_of_users)");

    // Now send the user verification emails
    $res_user = db_query("SELECT email, confirm_hash FROM user "
			 . " WHERE user_id IN ($list_of_users)");
	
    while ($row_user = db_fetch_array($res_user)) {
	send_new_user_email($row_user['email'],$row_user['confirm_hash']);
	usleep(250000);
    }

} else if ($action=='delete') {
    db_query("UPDATE user SET status='D' WHERE user_id='$user_id'");
}

//
// No action - First time in this script 
// Show the list of pending user waiting for approval
//
$res = db_query("SELECT * FROM user WHERE status='P'");

if (db_numrows($res) < 1) {
	exit_error("None Found","No Pending User Registration to Approve");
}

site_admin_header(array('title'=>'Approving Pending User Registration'));

while ($row = db_fetch_array($res)) {

	?>
	<H2><?php echo $row['realname'].' ('.$row['user_name'].')'; ?></H2>

	<p>
	<A href="/users/<?php echo $row['user_name']; ?>"><H3>[Show User Info / Send a mail note]</H3></A>

	<p>
	<A href="/admin/usergroup.php?user_id=<?php echo $row['user_id']; ?>"><H3>[Edit User/Group Settings]</H3></A>

	<p>
        <TABLE WIDTH="70%">
        <TR>
        <TD>
	<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
	<INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="<?php print $row['user_id']; ?>">
	<INPUT type="submit" name="submit" value="Approve User">
	</FORM>
 	</TD>

        <TD> 
	<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="delete">
	<INPUT TYPE="HIDDEN" NAME="user_id" VALUE="<?php print $row['user_id']; ?>">
	<INPUT type="submit" name="submit" value="Delete User">
	</FORM>
        </TD>
        </TR>
        </TABLE>
	<P>
	<B>Purpose:</B><br> <?php echo $row['register_purpose']; ?>

	<br>
	&nbsp;
	<?php

	// ########################## OTHER INFO

	print "<P><B>Other Information</B>";
	print "<br>&nbsp;&nbsp;User Name: $row[user_name]";

	print "<br>&nbsp;&nbsp;User ID:  $row[user_id]";

	print "<br>&nbsp;&nbsp;User Email:  <a href=\"mailto:$row[email]\">$row[email]</a>";
	print "<br>&nbsp;&nbsp;Registration Date:  ".format_date($sys_datefmt,$row[add_date]);
	echo "<P><HR><P>";

}

//list of user_id's of pending projects
$arr=result_column_to_array($res,0);
$user_list=implode($arr,',');

echo '
	<CENTER>
	<FORM action="'.$PHP_SELF.'" method="POST">
	<INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
	<INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$user_list.'">
	<INPUT type="submit" name="submit" value="Approve All On This Page">
	</FORM>
	';
	
site_admin_footer(array());

?>
