<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*


	Nightly internal-use script - adds the projects from trove to the foundries


*/

//REQUIRES PHP4

require ('squal_pre.php');    

echo $REMOTE_ADDR;

if (!strstr($REMOTE_ADDR,'192.168.1.')) {
	exit_permission_denied();
}

//list the foundries we're interested in
#$foundries   =array('6772','1872','6770','6771',    '10234', '10679', '10893');
#$foundry_cats=array('80',  '154',  '198','109,110', '107',    '90',    '142');

$result=db_query("SELECT foundry_id,trove_categories FROM foundry_data");

//array of foundries
$foundries=util_result_column_to_array($result,0);

//array of trove_categories for each of those foundries
$foundry_cats=util_result_column_to_array($result,1);


$count=count($foundries);

function get_trove_sub_projects($cat_id) {
	echo '<P>IN SUBPROJECT'.$cat_id;
	//return an array of trove categories under $cat_id
	$sql="SELECT trove_cat_id FROM trove_cat WHERE parent IN ($cat_id)";
	$result=db_query($sql);
	echo db_error();
	$rows=db_numrows($result);
	for ($i=0; $i<$rows; $i++) {
		$trove_list= array_merge( get_trove_sub_projects(db_result($result,$i,0)),$trove_list );
	}
	return array_merge( util_result_column_to_array($result),$trove_list );
}

for ($i=0; $i<$count; $i++) {
	$trove_list=array();
	$trove_list= get_trove_sub_projects($foundry_cats[$i]);
	$trove_list[]=$foundry_cats[$i];

	$trove_cats=implode(',',$trove_list);
	db_query("DELETE FROM foundry_projects WHERE foundry_id='$foundries[$i]'");

	$sql="INSERT INTO foundry_projects (foundry_id,project_id) SELECT DISTINCT $foundries[$i],groups.group_id FROM groups,trove_group_link ".
		"WHERE trove_group_link.trove_cat_id IN ($trove_cats) ".
		"AND groups.group_id=trove_group_link.group_id ".
		"AND groups.is_public=1 ".
		"AND groups.status='A' ";
//	echo $sql;
	$result=db_query($sql);
	echo db_error();
	//add this project to the foundry so it can submit news
	db_query("INSERT INTO foundry_projects (foundry_id,project_id) VALUES ($foundries[$i],$foundries[$i])");
}

?>
