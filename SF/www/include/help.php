<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function help_button($type,$helpid) {
	if ($type == 'trove_cat') {
		return ('<A href="javascript:help_window(\'/help/trove_cat.php'
			.'?trove_cat_id='.$helpid.'\')"><B>(?)</B></A>');
	}
}

function help_header($title) {
?>
<HTML>
<HEAD>
<TITLE><?php print $title; ?></TITLE>
<LINK rel="stylesheet" href="/sourceforge.css" type="text/css">
</HEAD>
<BODY bgcolor="#FFFFFF">
<H4>SourceForge Site Help System:</H4>
<H2><?php print $title; ?></H2>
<HR>
<?php
}

function help_footer() {
?>
</BODY>
</HTML>
<?php
}

?>
