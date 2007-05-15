<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('trove.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

// #######################################################

function printnode ($nodeid,$text,$delete_ok=false) {
    global $Language;

	// print current node, then all subnodes
	print ('<BR>');
	for ($i=0;$i<$GLOBALS['depth'];$i++) { print "&nbsp; &nbsp; "; }
	html_image('ic/cfolder15.png',array());
	print ('&nbsp; '.$text." ");
	if ($nodeid != 0) {
	  print ('&nbsp; <A href="trove_cat_edit.php?trove_cat_id='.$nodeid.'">['.$Language->getText('admin_trove_cat_list','edit').']</A> ');
	}
	if ($delete_ok) {
	    print ('&nbsp; <A href="trove_cat_delete.php?trove_cat_id='.$nodeid.'">['.$Language->getText('admin_trove_cat_list','delete').']</A> ');
	}
	if ($nodeid != 0) {
	  print ('&nbsp;'.help_button('trove_cat',$nodeid)."\n");
	}
	$GLOBALS["depth"]++;
	$res_child = db_query("SELECT trove_cat_id,fullname,parent FROM trove_cat "
		."WHERE parent='$nodeid'");
	while ($row_child = db_fetch_array($res_child)) {
	    $delete_ok = ($row_child["parent"] != 0);
	    printnode($row_child["trove_cat_id"],$row_child["fullname"],$delete_ok);
	}
	$GLOBALS["depth"]--;
}

// ########################################################

$HTML->header(array('title'=>$Language->getText('admin_trove_cat_list','title')));

echo "<H2>".$Language->getText('admin_trove_cat_list','header')."</H2>";

$depth = 0;
printnode(0,$Language->getText('admin_trove_cat_edit','root'));

echo "<p>";

$HTML->footer(array());

?>
