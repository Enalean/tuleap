<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require('../svn/svn_data.php');    
require('../svn/svn_utils.php');    


//{{{ define undefined variables
$func = "";
if (isset($_REQUEST['func'])) {
    $func = $_REQUEST['func'];
}
$rev_id = "";
if (isset($_REQUEST['rev_id'])) {
    $rev_id = $_REQUEST['rev_id'];
}
//}}}


// ######################## table for summary info

switch ($func) {

 case 'browse' : {
   require('../svn/browse_revision.php');
   break;
 }

 case 'detailrevision' : {
   require('../svn/detail_revision.php');
   break;
 }

 default : {

   // ############################ developer access
   if ($rev_id) {
       $_rev_id = $rev_id;
       require('./browse_revision.php');
   } else {
       require('./svn_intro.php');
   }

   break;
 }
}


?>
