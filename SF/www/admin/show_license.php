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

require($DOCUMENT_ROOT.'/include/pre.php');

$Language->loadLanguageMsg('admin/admin');

$HTML->header(array('title'=>$Language->getText('admin_show_license','title')));

// display the license
include(util_get_content('admin/codex_license_terms'));

$HTML->footer(array());

?>
