<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

if ((!$group_id) && $form_grp) 
	$group_id=$form_grp;

site_project_header(array('title'=>"Project Member List",'group'=>$group_id,'toptab'=>'memberlist'));

print '<P>If you would like to contribute to this project by becoming a developer,
contact one of the project admins, designated in bold text below.<br><br>';

// list members
// LJ email column added 
$query =  "SELECT user.user_name AS user_name,user.user_id AS user_id,"
	. "user.realname AS realname, user.add_date AS add_date, "
	. "user.email AS email, "
	. "user_group.admin_flags AS admin_flags "
	. "FROM user,user_group "
	. "WHERE user.user_id=user_group.user_id AND user_group.group_id=$group_id "
	. "ORDER BY user.user_name";


$title_arr=array();
$title_arr[]='Developer';
$title_arr[]='Username';
$title_arr[]='Email';
$title_arr[]='Skills';

echo html_build_list_table_top ($title_arr);

$res_memb = db_query($query);
while ( $row_memb=db_fetch_array($res_memb) ) {
	print "\t<tr>\n";
	print "\t\t";
	if ( $row_memb[admin_flags]=='A' ) {
		print "\t\t<td><b><A href=\"/users/$row_memb[user_name]/\">$row_memb[realname]</A></b></td>\n";
	} else {
		print "\t\t<td>$row_memb[realname]</td>\n";
	}
	print "\t\t<td align=\"center\"><A href=\"/users/$row_memb[user_name]/\">$row_memb[user_name]</A></td>\n";

/* LJ new version below
print "\t\t<td align=\"center\">
<A href=\"/sendmessage.php?touser=".$row_memb['user_id']."\">".$row_memb['user_name']." at ".$GLOBALS['sys_users_host']."</td>\n";
LJ */

	print "\t\t<td align=\"center\"><A href=\"/sendmessage.php?touser=".$row_memb['user_id']."\">".$row_memb['email']."</A></td>\n";


	print "\t\t<td align=\"center\"><A href=\"/people/viewprofile.php?user_id=".
		$row_memb['user_id']."\">View Skills</a></td>\n";
	print "\t<tr>\n";
}
print "\t</table>";

site_project_footer(array());

?>
