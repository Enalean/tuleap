<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id: index.php 1226 2004-10-26 16:55:18Z guerin $
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

// Redirect to showfiles.php when no script name is given.
// Avoid listing content of the directory!

header ("Location: /file/showfiles.php?group_id=$group_id");

?>
