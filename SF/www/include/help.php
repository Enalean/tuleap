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
* @param        string    The button type
* @param        int        The trove category ID
*/
function help_button($type,$helpid) {
	if ($type == 'trove_cat') {
		return ('<A href="javascript:help_window(\'/help/trove_cat.php'
			.'?trove_cat_id='.$helpid.'\')"><B>(?)</B></A>');
	} else {
	    // Generic processing derives the script name from the help type
	    $script = '/help/'.$type.'.php';
	    return ('<A href="javascript:help_window(\''.$script.
		    '?helpid='.$helpid.'\')"><B>(?)</B></A>');
	}
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
<LINK rel="stylesheet" href="/sourceforge.css" type="text/css">
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
