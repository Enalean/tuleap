<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/**
* help_button() - Show a help button.
*
* @param        type      the php script or html page that contains/generates help
* @param        helpid   if specified this is an argument passed to the PHP script
*                                      if false then it is a static HTML page
* @param        prompt what to display to point to the  help 
*/
function help_button($type,$helpid=false,$prompt='[?]') {

    // Generic processing derives the script name from the help type
    if ($helpid == false) {
	// $type is a static HTML page from the CodeX User Guide
	$script = '/help/show_help.php?section='.$type;
    } else {
	// $type is a php script - the invoker probably wants to customize 
	// the help display somehow
	$script = '/help/'.$type.'.php?helpid='.urlencode($helpid);
    }	

    return ('<A href="javascript:help_window(\''.$script.'\')"><B>'.$prompt.'</B></A>');
}


/**
* help_header() - Show a help page header
*
* @param        string    Header title
*/
function help_header($title, $help_banner=true) {
?>
<HTML>
<HEAD>
<TITLE><?php print $title; ?></TITLE>
<LINK rel="stylesheet" href="<? echo util_get_css_theme(); ?>" type="text/css">
</HEAD>
<BODY bgcolor="#bcbcad">
<?php print ($help_banner ? '<H4>'.$GLOBALS['sys_name'].' Site Help System</H4>' : ''); ?>
<H2><?php print $title; ?></H2>
<HR>
<?php
}

/**
* help_footer() - Show a help page footer
*/
function help_footer() {
?>
</BODY>
</HTML>
<?php
}

?>
