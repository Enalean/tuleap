<?php
//
// Codendi
// Copyright (c) Enalean, 2015. All Rights Reserved.
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
require_once('pre.php');
require_once('common/reference/ReferenceManager.class.php');
require_once('www/project/admin/include/ReferenceAdministrationViews.class.php');

$hp = Codendi_HTMLPurifier::instance();

function getReferenceRow($ref, $row_num) {
    $html = '';
    
    if ($ref->isActive() && $ref->getId() != 100) {
        $purifier = Codendi_HTMLPurifier::instance();
        $html .= '<TR class="'. util_get_alt_row_color($row_num) .'">';
        $html .= '<TD>'. $purifier->purify($ref->getKeyword()) .'</TD>';
        $html .= '<TD>'. $purifier->purify(ReferenceAdministrationViews::getReferenceDescription($ref)) .'</TD>';
        $html .= '<TD>'. $purifier->purify($ref->getLink()) .'</TD>';
        $html .= '</TR>';
    }
    
    return $html;
}
    
function getReferencesTable($groupId) {
    $html = '';
    $html .= '<h3>'.$GLOBALS['Language']->getText('project_showdetails','references').'</h3>';
    
    $title_arr[]=$GLOBALS['Language']->getText('project_reference','r_keyword');
    $title_arr[]=$GLOBALS['Language']->getText('project_reference','r_desc');
    $title_arr[]=$GLOBALS['Language']->getText('project_reference','r_link');
    $html .= html_build_list_table_top($title_arr, false, false, true);
    
    $referenceManager =& ReferenceManager::instance();
    $references =& $referenceManager->getReferencesByGroupId($groupId); // References are sorted by scope first
    $row_num = 0;
    foreach ($references as $ref) {
        $html .= getReferenceRow($ref, $row_num);
        $row_num++;
    }
        
    $html .= '</table>';
    return $html;
}

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

$currentproject->displayProjectsDescFieldsValue();	

if ($license_other != '') {
	print '<P>';
	print '<b><u>'.$Language->getText('project_admin_editgroupinfo','license_comment').'</u></b>';
	print '<P>'.$hp->purify(util_unconvert_htmlspecialchars($license_other), CODENDI_PURIFIER_BASIC, $group_id);
}

echo getReferencesTable($group_id);

print '<P><a href="/project/?group_id='.$group_id .'"> '.$Language->getText('project_showdetails','back_main').' </a>';

site_project_footer(array());
