<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/admin/admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

site_admin_header(array('title'=>$GLOBALS['sys_name']." Admin"));

$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

// Get the number of pending users and projects
db_query("SELECT count(*) AS count FROM groups WHERE status='P'");
$row = db_fetch_array();
$pending_projects = $row['count'];

db_query("SELECT count(*) AS count FROM user WHERE status='P'");
$row = db_fetch_array();
$pending_users = $row['count'];

?>
 
<h2>Administrative Functions</h2>
<p><i><b>Warning!</b> These functions currently have minimal error checking,
if any. They are fine to play with but may not act as expected if you leave
fields blank, etc... Also, navigating the admin functions with the 
<b>back</b> button is highly unadvised.</i>

<h3>User Administration</h3>
<ul>
<!--
<li><a href="userlist.php">Display Full User List/Edit Users</a>&nbsp;&nbsp;
-->
<li>Display Users Beginning with : 
<?php
	for ($i=0; $i < count($abc_array); $i++) {
		echo "<a href=\"userlist.php?user_name_search=$abc_array[$i]\">$abc_array[$i]</a>|";
	}
?>
<br>
Search (email,username,realname,userid):
<br>
<form name="usersrch" action="search.php" method="POST">
  <input type="text" name="search">
  <input type="hidden" name="usersearch" value="1">
  <input type="submit" value="get">
</form>
<ul>
<LI>Users in <a href="approve_pending_users.php"><B>P</B> (pending) Status</A>
<?php echo " <b>($pending_users";
if ($GLOBALS['sys_user_approval'] == 1 && $pending_users != 0) {
    print "&nbsp;-&nbsp; <a href=\"approve_pending_users.php\">approval needed</a>";
}
echo ")</b>";
?>
</ul>
</ul>

<h3>Group Administration</h3>
<ul>
<!-- 
<li><a href="grouplist.php">Display Full Group List/Edit Groups</a>
-->

<li>Display Groups Beginning with : 
<?php
	for ($i=0; $i < count($abc_array); $i++) {
		echo "<a href=\"grouplist.php?group_name_search=$abc_array[$i]\">$abc_array[$i]</a>|";
	}
?>

Search (groupid,groupunixname,groupname):
<br>
<form name="gpsrch" action="search.php" method="POST">
  <input type="text" name="search">
  <input type="hidden" name="groupsearch" value="1">
  <input type="submit" value="get">
</form>

<p>

<ul>
<LI>Groups in <a href="grouplist.php?status=I"><B>I</B> (incomplete) Status</A>
<LI>Groups in <a href="approve-pending.php"><B>P</B> (pending) Status</A><?php echo " <b>($pending_projects";
if ($pending_projects != 0) {
    print "&nbsp;-&nbsp; <a href=\"approve-pending.php\">approval needed</a>";
}
echo ")</b>";?>
<LI>Groups in <a href="grouplist.php?status=D"><B>D</B> (deleted) Status</A>
</ul>
</ul>

<h3>Service Administration</h3>
<ul>
<li><a href="/project/admin/servicebar.php?group_id=100">Configuration of predefined services</A>
</ul>

<h3>Trackers Administration</h3>
<ul>
<li><a href="/tracker/admin/restore.php">Pending Tracker Removals</A>
<li><a href="/tracker/admin/?group_id=100">Configuration of predefined CodeX templates</A>
</ul>

<h3>Statistics</h3>
<ul>
<li><a href="lastlogins.php">View Most Recent Logins</A>
<li><a href="/stats/">Generate Site/Projects/Users Statistics</A>
</ul>

<h3>Site Utilities</h3>
<UL>
<LI><A href="massmail.php">Mail Engine for <?php print $GLOBALS['sys_name']; ?> Subscribers (MESS)</A>
</UL>

<h3>Site Stats</h3>
<?php
        db_query("SELECT count(*) AS count FROM user WHERE status='A'");
        $row = db_fetch_array();
        print "<P>Registered active site users: <B>$row[count]</B>";

        db_query("SELECT count(*) AS count FROM groups");
        $row = db_fetch_array();
        print "<BR>Registered projects: <B>$row[count]</B>";

        db_query("SELECT count(*) AS count FROM groups WHERE status='A'");
        $row = db_fetch_array();
        print "<BR>Registered/hosted projects: <B>$row[count]</B>";

	print "<BR>Pending projects: <B>$pending_projects</B>";

        print "<BR>Pending users: <B>$pending_users</B>";

?>


<?php
site_admin_footer(array());
?>
