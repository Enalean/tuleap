<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id: 
//
//	Originally written by Stephane Bouhet 2002, CodeX Team, Xerox
//

require "pre.php";

// Default language
$lang = $GLOBALS['sys_lang'];

// Retrieve the user language if not guest
// TODO

// if section param not given then defaults to index.html
if ( !isset($section) ) {
    $section = "index.html";
}

if (session_issecure())
    $help_url = "https://".$GLOBALS['sys_https_host'];
else
    $help_url = "http://".$GLOBALS['sys_default_domain'];

$help_url .= '/documentation/user_guide/html/'.$lang."/".$section;

// Check if the file exist - Don't use fopen because it doesn't
// understand the https protocol
$cl = apache_lookup_uri($help_url);

if ( $cl->status == 200) {
    // The file exists. Fine! Redirect to the help page
    header("location: ".$help_url);
} else {
    // Display error message ...
    echo help_header($GLOBALS['sys_name'].' Help System - Error: page not found');
    echo '
   <h4>Sorry, the help page you have requested (section "'.$section.'") is not available.<br>
   Please inform the <a href="mailto:'.$GLOBALS['sys_email_admin'].'">'.
 $GLOBALS['sys_name'].' Site Administrator</a></h4>';
    echo help_footer();
}

?>
