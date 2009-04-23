<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Laurent Julliard 2004, Codendi Team, Xerox
//

require_once('pre.php');


$HTML->header(array('title'=>$Language->getText('admin_show_license','title')));

// display the license
include($Language->getContent('admin/license_terms'));

$HTML->footer(array());

?>
