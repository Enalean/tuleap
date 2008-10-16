<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');


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

$GLOBALS['Response']->redirect('/projects/'.group_getunixname($group_id));

?>
