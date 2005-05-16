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

$there_are_specific_permissions = true;
if (isset($_REQUEST['group_id'])) {
    $obj                            = group_get_object($_REQUEST['group_id']);
    $group_name                     = $obj->getUnixName();
    $there_are_specific_permissions = svn_utils_is_there_specific_permission($group_name);
 }



if (isset($_REQUEST['func']) && $_REQUEST['func'] === 'detailrevision') {

    require('./detail_revision.php');

 } else if (                                                                  //We'll browse
            (
             (isset($_REQUEST['func']) && $_REQUEST['func'] === 'browse')     //if user ask for it
             || (isset($_REQUEST['rev_id']) && $_REQUEST['rev_id'] != '')     //or if user set rev_id
             )){
    if (isset($_REQUEST['rev_id']) && $_REQUEST['rev_id'] != '') {
        $_rev_id = $_REQUEST['rev_id'];
    }

    require('./browse_revision.php');

 } else {

    require('./svn_intro.php');

 }
?>
