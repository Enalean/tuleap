<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

require('pre.php');

$HTML->header(array('title'=>'CodeX License Terms and Conditions'));

// display the license
include(util_get_content('admin/codex_license_terms'));

$HTML->footer(array());

?>
