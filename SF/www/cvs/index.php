<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ('../cvs/commit_utils.php');    

// ######################## table for summary info


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
       $status = 'partly<br> Email Address Appears Invalid, e-mail notification is off. ';
     }
   }
   $feedback = $feedback.$status;
   $query = 'update groups set cvs_tracker="'.$tracked.'", cvs_events_mailing_list="'.$mailing_list.'", cvs_events_mailing_header="'.$mailing_header.'", cvs_preamble="'.htmlspecialchars($form_preamble).'" where group_id='.$group_id;
   $result=db_query($query);
   include '../cvs/admin_commit.php';
   break;
 }


 default : {

   // ############################ developer access
   if ($commit_id) {
       $_commit_id = $commit_id;
       include '../cvs/browse_commit.php';
   } else {
       include '../cvs/cvs_intro.php';
   }

   break;
 }
}


?>
