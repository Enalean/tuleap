<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ('../cvs/commit_utils.php');    

//only projects can use the bug tracker, and only if they have it turned on
$project=project_get_object($group_id);

if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use CVS');
}
if (!$project->usesCVS()) {
	exit_error('Error','This Project Has Turned Off CVS');
}


site_project_header(array('title'=>'CVS Repository','group'=>$group_id,'toptab'=>'cvs'));

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

$row_grp = db_fetch_array($res_grp);

// ######################## table for summary info


// LJ No anonymous access anymore on CodeX
// LJ if ($row_grp['is_public']) {

commits_header($params);
switch ($func) {

 case 'browse' : {
   include '../cvs/browse_commit.php';
   break;
 }

 case 'detailcommit' : {
   include '../cvs/detail_commit.php';
   break;
 }


 case 'admin' : {
   include '../cvs/admin_commit.php';
   break;
 }

 case 'setAdmin' : {
   $feedback = 'Configuration updated ';
   $status = 'successfuly';

   if (trim($custom_mailing_header) == '') {
     $mailing_header = 'NULL';
   } else {
     $mailing_header = $custom_mailing_header;
   }
   if (trim($mailing_list) == '') {
     $mailing_list = 'NULL';
   } else {
     if (!validate_emails ($mailing_list)) {
       $mailing_list = 'NULL';
       $status = 'partly<br> Email Address Appeared Invalid, e-mail notification is off. ';
     }
   }
   $feedback = $feedback.$status;
   $query = 'update groups set cvs_tracker="'.$tracked.'", cvs_events_mailing_list="'.$mailing_list.'", cvs_events_mailing_header="'.$mailing_header.'" where group_id='.$group_id;
   $result=db_query($query);
   include '../cvs/admin_commit.php';
   break;
 }


 default : {

   // ############################ developer access
   if ($commit_id) {
     $_commit_id = $commit_id;
     include '../cvs/browse_commit.php';
   }
   else
     util_get_content('cvs/intro');

   break;
 }
}





site_project_footer(array()); 

?>
