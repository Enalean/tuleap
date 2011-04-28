<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Guerin 2004, Codendi Team, Xerox
//

// Redirect to showfiles.php when no script name is given.
// Avoid listing content of the directory!
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/valid/ValidFactory.class.php');

$request =& HTTPRequest::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
    $GLOBALS['Response']->redirect('/file/showfiles.php?group_id='.$group_id);
} else {
    exit_no_group();
}
?>
