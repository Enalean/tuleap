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

// ########################################################

if ($GLOBALS["Submit"]) {
	$newroot = trove_getrootcat($GLOBALS['form_parent']);
	if ($GLOBALS[form_shortname]) {
		db_query('UPDATE trove_cat '
			.'SET '
			.'shortname=\''.$GLOBALS[form_shortname] 
			.'\',fullname=\''.$GLOBALS[form_fullname]
			.'\',description=\''.$GLOBALS[form_description]
			.'\',parent=\''.$GLOBALS[form_parent]
			.'\',version='.date("Ymd",time()).'01'
			.',root_parent=\''.$newroot
			.'\' WHERE trove_cat_id='.$GLOBALS["form_trove_cat_id"]);
	} 
	// update full paths now
	trove_genfullpaths($newroot,trove_getfullname($newroot),$newroot);

	session_redirect("/admin/trove/trove_cat_list.php");
} 

$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=$trove_cat_id");
if (db_numrows($res_cat)<1) 
	{ exit_error("No Suck Category","That trove cat does not exist"); }
$row_cat = db_fetch_array($res_cat);

$HTML->header(array(title=>"Trove - Edit Category"));
?>

<form action="trove_cat_edit.php" method="post">
<input type="hidden" name="form_trove_cat_id" value="<?php
  print $GLOBALS['trove_cat_id']; ?>">
<p>New category short name (no spaces, unix-like):
<br><input type="text" name="form_shortname" value="<?php print $row_cat["shortname"]; ?>">
<p>New category full name (VARCHAR 80):
<br><input type="text" name="form_fullname" value="<?php print $row_cat["fullname"]; ?>">
<p>New category description (VARCHAR 255):
<br><input type="text" name="form_description" size="80" value="<?php print $row_cat["description"]; ?>">
<p>Parent Category:
<br><SELECT name="form_parent">
<?php
// generate list of possible parents
$res_parent = db_query("SELECT shortname,fullname,trove_cat_id FROM trove_cat");
while ($row_parent = db_fetch_array($res_parent)) {
	print ('<OPTION value="'.$row_parent["trove_cat_id"].'"');
	if ($row_cat["parent"] == $row_parent["trove_cat_id"]) print ' selected';
	print ('>'.$row_parent["fullname"]."\n");
}
?>
</SELECT>
<br><input type="submit" name="Submit" value="Submit">
</form>

<?php
$HTML->footer(array());

?>
