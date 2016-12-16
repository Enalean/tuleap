<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// $Id: 
//
//	Originally written by Stephane Bouhet 2002, Codendi Team, Xerox
//

require_once('pre.php');

$lang    = $current_user->getShortLocale();
$request = HTTPRequest::instance();

$section = $request->get('section');
if (! $section) {
    $section = "index.html";
}

$help_url = get_server_url().'/doc/'.$lang."/user-guide/".$section;

// Check if the file exist - Don't use fopen because it doesn't
// understand the https protocol
$cl = apache_lookup_uri($help_url);

if ( $cl->status == 200) {
    // The file exists. Fine! Redirect to the help page
    header("location: ".$help_url);
} else {
    // Display error message ...
    echo help_header($Language->getText('help_show_help','page_not_found',$GLOBALS['sys_name']));

    $purifier         = Codendi_HTMLPurifier::instance();
    $purified_section = $purifier->purify($section);
    echo $Language->getText('help_show_help','page_not_available',array($purified_section,$GLOBALS['sys_email_admin'],$GLOBALS['sys_name']));

    echo help_footer();
}
