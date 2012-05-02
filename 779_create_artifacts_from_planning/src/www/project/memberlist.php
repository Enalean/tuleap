<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    

$hp = Codendi_HTMLPurifier::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    $vFormGrp = new Valid_UInt('form_grp');
    $vFormGrp->required();
    if($request->valid($vFormGrp)) {
        $group_id = $request->get('form_grp');
    } else {
        exit_no_group();
    }
}

site_project_header(array('title'=>$Language->getText('project_memberlist','proj_member_list'),'group'=>$group_id,'toptab'=>'memberlist'));

print $Language->getText('project_memberlist','contact_to_become_member');

// list members
// LJ email column added 
$query =  "SELECT user.user_name AS user_name,user.user_id AS user_id,"
	. "user.realname AS realname, user.add_date AS add_date, "
	. "user.email AS email, "
	. "user_group.admin_flags AS admin_flags "
	. "FROM user,user_group "
	. "WHERE user.user_id=user_group.user_id AND user_group.group_id=".db_ei($group_id)." "
	. "ORDER BY user.user_name";


$title_arr=array();
$title_arr[]=$Language->getText('project_memberlist','developer');
$title_arr[]=$Language->getText('project_export_artifact_history_export','email');
$title_arr[]=$Language->getText('project_memberlist','skills');

$em = EventManager::instance();
$user_helper = new UserHelper();
$hp = Codendi_HTMLPurifier::instance();

echo html_build_list_table_top ($title_arr);

$res_memb = db_query($query);
while ( $row_memb=db_fetch_array($res_memb) ) {
    $display_name = '';
    $em->processEvent('get_user_display_name', array(
        'user_id'           => $row_memb['user_id'],
        'user_name'         => $row_memb['user_name'],
        'realname'          => $row_memb['realname'],
        'user_display_name' => &$display_name
    ));
    if (!$display_name) {
        $display_name = $hp->purify($user_helper->getDisplayName($row_memb['user_name'], $row_memb['realname']));
    }
	print "\t<tr>\n";
	print "\t\t";
	if ( $row_memb['admin_flags']=='A' ) {
		print '<td><b><A href="/users/'. $row_memb['user_name'] .'/">'. $display_name ."</A></b></td>\n";
	} else {
		print "\t\t<td>".  $display_name ."</td>\n";
	}

	print "\t\t<td align=\"center\"><A href=\"mailto:".$row_memb['email']."\">".$row_memb['email']."</A></td>\n";


	print "\t\t<td align=\"center\"><A href=\"/people/viewprofile.php?user_id=".
		$row_memb['user_id']."\">".$Language->getText('project_memberlist','view_skills')."</a></td>\n";
	print "\t<tr>\n";
}
print "\t</table>";

site_project_footer(array());

?>