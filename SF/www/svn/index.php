<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ('../svn/svn_utils.php');    

// ######################## table for summary info


switch ($func) {

 case 'browse' : {
   include '../svn/browse_revision.php';
   break;
 }

 case 'detailrevision' : {
   include '../svn/detail_revision.php';
   break;
 }


 case 'admin' : {
   include '../svn/admin_svn.php';
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
   $query = 'update groups set svn_tracker="'.$tracked.'", svn_events_mailing_list="'.$mailing_list.'", svn_events_mailing_header="'.$mailing_header.'", svn_preamble="'.htmlspecialchars($form_preamble).'" where group_id='.$group_id;
   $result=db_query($query);
   include '../svn/admin_svn.php';
   break;
 }


 default : {

   // ############################ developer access
   if ($rev_id) {
       $_rev_id = $rev_id;
       include './browse_revision.php';
   } else {
       include './svn_intro.php';
   }

   break;
 }
}


?>
