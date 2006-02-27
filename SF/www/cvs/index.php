<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require('../cvs/commit_utils.php');    

$Language->loadLanguageMsg('cvs/cvs');

// ######################## table for summary info

if (!isset($func)) $func="";
switch ($func) {

 case 'browse' : {
   require('../cvs/browse_commit.php');
   break;
 }

 case 'detailcommit' : {
   require('../cvs/detail_commit.php');
   break;
 }


 case 'admin' : {
   require('../cvs/admin_commit.php');
   break;
 }

 case 'setAdmin' : {
   $feedback .= $Language->getText('cvs_index', 'config_updated');
   $status = $Language->getText('cvs_index', 'full_success');

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
       $status = $Language->getText('cvs_index', 'partial_success');
     }
   }
   $feedback = $feedback.' '.$status;
   $query = 'update groups set cvs_tracker="'.$tracked.'", cvs_events_mailing_list="'.$mailing_list.'", cvs_events_mailing_header="'.$mailing_header.'", cvs_preamble="'.htmlspecialchars($form_preamble).'" where group_id='.$group_id;
   $result=db_query($query);
   require('../cvs/admin_commit.php');
   break;
 }


 default : {

   // ############################ developer access
     if (isset($commit_id)) {
       $_commit_id = $commit_id;
       require('../cvs/browse_commit.php');
   } else {
       // cvs_intro depends on the user shell access
       $shell=get_user_shell(user_getid());
       require('../cvs/cvs_intro.php');
   }

   break;
 }
}


?>
