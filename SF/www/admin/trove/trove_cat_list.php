<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";
require "trove.php";
session_require(array('group'=>'1','admin_flags'=>'A'));

// #######################################################

function printnode ($nodeid,$text) {
	// print current node, then all subnodes
	print ('<BR>');
	for ($i=0;$i<$GLOBALS[depth];$i++) { print "&nbsp; &nbsp; "; }
	html_image('ic/cfolder15.png',array());
	print ('&nbsp; '.$text." ");
	print ('<A href="trove_cat_edit.php?trove_cat_id='.$nodeid.'">[Edit]</A> ');
	print (help_button('trove_cat',$nodeid)."\n");

	$GLOBALS["depth"]++;
	$res_child = db_query("SELECT trove_cat_id,fullname FROM trove_cat "
		."WHERE parent='$nodeid'");
	while ($row_child = db_fetch_array($res_child)) {
		printnode($row_child["trove_cat_id"],$row_child["fullname"]);
	}
	$GLOBALS["depth"]--;
}

// ########################################################

$HTML->header(array(title=>"Trove - Category List"));

printnode(0,"root");

$HTML->footer(array());

?>
