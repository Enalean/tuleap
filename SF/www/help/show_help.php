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

require_once('pre.php');
$Language->loadLanguageMsg('help/help');

// Default language
// Should be: $lang = $Language->getLanguageCode(); -> switch when documentation is available in several languages... //XXX
//$lang = 'en_US';
$lang = $Language->getLanguageCode();

// Retrieve the user language if not guest
// TODO

// if section param not given then defaults to index.html
if ( !isset($section) ) {
    $section = "index.html";
}

$help_url = get_server_url().'/documentation/user_guide/html/'.$lang."/".$section;

// Check if the file exist - Don't use fopen because it doesn't
// understand the https protocol
$cl = apache_lookup_uri($help_url);

if ( $cl->status == 200) {
    // The file exists. Fine! Redirect to the help page
    header("location: ".$help_url);
} else {
    // Display error message ...
  echo help_header($Language->getText('help_show_help','page_not_found',$GLOBALS['sys_name']));
  echo $Language->getText('help_show_help','page_not_available',array($section,$GLOBALS['sys_email_admin'],$GLOBALS['sys_name']));
    echo help_footer();
}

?>
