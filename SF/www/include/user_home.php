<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	Developer Info Page
	Written by dtype Oct 1999
*/


/*


	Assumes $res_user result handle is present


*/

$HTML->header(array('title'=>'Developer Profile'));

if (!$res_user || db_numrows($res_user) < 1) {
	exit_error('No Such User','No Such User');
}

?>

<H3>Developer Profile</H3>
<P>
<TABLE width=100% cellpadding=2 cellspacing=2 border=0><TR valign=top>
<TD width=50%>

<?php $HTML->box1_top("Personal Information"); ?>
&nbsp;
<BR>
<TABLE width=100% cellpadding=0 cellspacing=0 border=0>
<TR valign=top>
	<TD>User ID: </TD>
	<TD><B><?php print db_result($res_user,0,'user_id'); ?></B></TD>
</TR>
<TR valign=top>
	<TD>Login Name: </TD>
	<TD><B><?php print db_result($res_user,0,'user_name'); ?></B></TD>
</TR>
<TR valign=top>
	<TD>Real Name: </TD>
	<TD><B><?php print db_result($res_user,0,'realname'); ?></B></TD>
</TR>
<TR valign=top>
	<TD>Email Addr: </TD>
	<TD>
	<B>
	<A HREF="/sendmessage.php?touser=<?php print db_result($res_user,0,'user_id'); ?>">
	<?php print db_result($res_user,0,'email'); ?>	
	</A></B>
	</TD>
</TR>
<TR valign=top>
	<TD>User Profile: </TD>
        <TD>
        <A HREF="/people/viewprofile.php?user_id=<?php print db_result($res_user,0,'user_id'); ?>"><B>See Skills Profile</B></A></TD>
</TR>

<TR>
	<TD>
	Site Member Since: 
	</TD>
	<TD><B><?php print date("M d, Y",db_result($res_user,0,'add_date')); ?></B></TD>
</TR>


<?php
// Some more information on the user from the LDAP server if available
if ($sys_ldap_server) {
    if (!$showdir) {
	echo '<td colspan="2" align="center"><a href="'.$PHP_SELF.'/?showdir=1"><hr>[ More from the '.$GLOBALS['sys_org_name'].' Directory... ]</a><td>';
	
    } else {
	$ds=ldap_connect($sys_ldap_server);
	if ($ds) {
	    $r=ldap_bind($ds);
	    include(util_get_content('include/user_home'));
	    // Build the LDAP filter for the search
	    if ($GLOBALS['sys_ldap_filter']) {
		$ldap_filter = $GLOBALS['sys_ldap_filter'];
	    } else {
		$ldap_filter = "mail=%email%";
	    }
	    preg_match_all("/%([\w\d\-\_]+)%/", $ldap_filter, $match);
	    while (list(,$v) = each($match[1])) {
		$ldap_filter = str_replace("%$v%", db_result($res_user,0,$v),$ldap_filter);
	    }

	    // Now run the ldap search
	    $sr=ldap_search($ds,$GLOBALS['sys_ldap_dn'],$ldap_filter);
	    if ($sr) {
		// Ideally we should only have one reply from the LDAP server
		$info = ldap_get_entries($ds, $sr);
		if ($info['count'] > 0) {

		    // Format LDAP output based on templates given in user_home.txt
		    if ( $my_html_ldap_format ) {
			preg_match_all("/%([\w\d\-\_]+)%/", $my_html_ldap_format, $match);
			$html = $my_html_ldap_format;
			while (list(,$v) = each($match[1])) {
			    $value = (isset($info[0][$v]) ? $info[0][$v][0] : "-");
			    $value = $info[0][$v][0];
			    $html = str_replace("%$v%", $value, $html);
			}
			print $html;
		    } else {
			// if no html template then produce a raw output
			print '<td colspan="2" align="center"><hr><td>';
			print '<tr valign="top"><td colspan="2">Total number of entries: '.$info["count"]."</td></tr>";

			for ($i=0; $i<$info["count"]; $i++) {
			    print '<tr valign="top"><td colspan="2"><b>Entry # '.$i."</b></td></tr>";
			    print '<tr valign="top"><td>&nbsp;&nbsp;Entry dn </td><td>'.$info[$i]["dn"]."</td></tr>";
			    print '<tr valign="top"><td>&nbsp;&nbsp;# attributes </td><td>'.$info[$i]["count"]."</td></tr>";
			   
			    for ($j=0; $j<$info[$i]["count"]; $j++) {
				$attrib_name = $info[$i][$j];
				$nb_values = $info[$i][$attrib_name]["count"];
				unset($info[$i][$attrib_name]["count"]);
				print '<tr valign="top"><td>&nbsp;&nbsp;'.$attrib_name.'</td><td>'.join('<br>',$info[$i][$attrib_name])."</td></tr>";
			    }
			}
		    }
		} else
		    $feedback = $GLOBALS['sys_org_name']." Directory: unkown user";
	    } else
		$feedback = $GLOBALS['sys_org_name']." Directory: search failed";
	} else
	    $feedback = "LDAP server not responding";

	if ($feedback)
	    echo '<td colspan="2" align="center"><hr><b>'.$feedback.'</b></td>';
    }
}
?>

</TR>

<?php 
/*
<TR><TD VALIGN=TOP>
Rating:
</TD><TD>
<P>&nbsp;
<?php echo vote_show_release_radios (db_result($res_user,0,'user_id'],4); ? >
</TD></TR>
*/ 
?>
</TABLE>
<?php $HTML->box1_bottom(); ?>

</TD>
<TD>&nbsp;</TD>
<TD width=50%>
<?php $HTML->box1_top("Group Info"); 
// now get listing of groups for that user
$res_cat = db_query("SELECT groups.group_name, "
	. "groups.unix_group_name, "
	. "groups.group_id, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags FROM "
	. "groups,user_group WHERE user_group.user_id='$user_id' AND "
	. "groups.group_id=user_group.group_id AND groups.is_public='1' AND groups.status='A' AND groups.type='1'");

// see if there were any groups
if (db_numrows($res_cat) < 1) {
	?>
	<p>This developer is not a member of any projects.
	<?php
} else { // endif no groups
	print "<p>This developer is a member of the following groups:<BR>&nbsp;";
	while ($row_cat = db_fetch_array($res_cat)) {
		print ("<BR>" . "<A href=\"/projects/$row_cat[unix_group_name]/\">$row_cat[group_name]</A>\n");
	}
	print "</ul>";
} // end if groups

$HTML->box1_bottom(); ?>
</TD></TR>

<TR><TD COLSPAN="3">

<?php 

if (user_isloggedin()) {

	?>
	&nbsp;
	<P>
	<H3>Send a Message to <?php echo db_result($res_user,0,'realname'); ?></H3>
	<P>
	<FORM ACTION="/sendmessage.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="touser" VALUE="<?php echo $user_id; ?>">



	<B>Your Email Address:</B><!-- LJ<BR> -->
	<B><?php $my_email=user_getemail(user_getid());
	         echo $my_email; ?></B>

        <? // LJ echo user_getname().'@'.$GLOBALS['sys_users_host']; ?></B>
	<!-- LJ INPUT TYPE="HIDDEN" NAME="email" VALUE="<?php echo user_getname().'@'.$GLOBALS['sys_users_host']; ?>" -->

        <INPUT TYPE="HIDDEN" NAME="email" VALUE="<?php echo $my_email; ?>">	<P>
	<B>Your Name:</B><!-- <BR> -->
	<B><?php 

	$my_name=user_getrealname(user_getid());

	echo $my_name; ?></B>
	<INPUT TYPE="HIDDEN" NAME="name" VALUE="<?php echo $my_name; ?>">
	<P>
	<B>Subject:</B><BR>
	<INPUT TYPE="TEXT" NAME="subject" SIZE="30" MAXLENGTH="40" VALUE="">
	<P>
	<B>Message:</B><BR>
	<TEXTAREA NAME="body" ROWS="15" COLS="60" WRAP="HARD"></TEXTAREA>
	<P>
	<CENTER>
	<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="Send Message">
	</CENTER>
	</FORM>
	<?php

} else {

	echo '<H3>You Could Send a Message if you were logged in</H3>';

}

?>

</TD></TR>
</TABLE>

<?php
$HTML->footer(array());

?>
