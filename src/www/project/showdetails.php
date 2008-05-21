<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 
require_once('pre.php');

$Language->loadLanguageMsg('project/project');

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$currentproject= new project($group_id);

site_project_header(array('title'=>$Language->getText('project_showdetails','proj_details'),'group'=>$group_id,'toptab'=>'summary'));

print '<P><h3>'.$Language->getText('project_showdetails','proj_details').'</h3>';

// Now fetch the project details

$result=db_query("SELECT license_other ".
		"FROM groups ".
		"WHERE group_id=".db_ei($group_id));

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error($Language->getText('project_showdetails','proj_not_found'),$Language->getText('project_showdetails','no_detail'));
}

$license_other = db_result($result,0,'license_other');

$descfields=getProjectsDescFieldsInfos();
$descfieldsvalue=$currentproject->getProjectsDescFieldsValue();

$hp = CodeX_HTMLPurifier::instance();

for($i=0;$i<sizeof($descfields);$i++){
	
	
	$displayfieldname[$i]=$descfields[$i]['desc_name'];
	$displayfieldvalue[$i]='';
	for($j=0;$j<sizeof($descfieldsvalue);$j++){
		
		if($descfieldsvalue[$j]['group_desc_id']==$descfields[$i]['group_desc_id']){
			$displayfieldvalue[$i]=$descfieldsvalue[$j]['value'];
		}	
	}
	
	echo "<P><b><u>".$hp->purify($displayfieldname[$i],CODEX_PURIFIER_LIGHT,$group_id)."</u></b></P>";
	echo "<P>";
	echo ($displayfieldvalue[$i] == '') ? $Language->getText('global','none') : $hp->purify($displayfieldvalue[$i], CODEX_PURIFIER_LIGHT, $group_id)  ;
	echo "</P>";
}
	
	
?>

<?php

if ($license_other != '') {
	print '<P>';
	print '<b><u>'.$Language->getText('project_admin_editgroupinfo','license_comment').'</u></b>';
	print '<P>'.$hp->purify(util_unconvert_htmlspecialchars($license_other), CODEX_PURIFIER_BASIC, $group_id);
}

print '<P><a href="/project/?group_id='.$group_id .'"> '.$Language->getText('project_showdetails','back_main').' </a>';

site_project_footer(array());

?>
