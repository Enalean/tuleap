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
		db_query('INSERT INTO trove_cat '
			.'(shortname,fullname,description,parent,version,root_parent) values ('
			.'\''.$GLOBALS[form_shortname] 
			.'\',\''.$GLOBALS[form_fullname]
			.'\',\''.$GLOBALS[form_description]
			.'\',\''.$GLOBALS[form_parent]
			.'\','.date("Ymd",time()).'01'
			.',\''.$newroot.'\')');
	} 

	// update full paths now
        trove_genfullpaths($newroot,trove_getfullname($newroot),$newroot);

	session_redirect("/admin/trove/trove_cat_list.php");
} 

$HTML->header(array(title=>"Trove - Add Node"));
?>

<form action="trove_cat_add.php" method="post">
<p>New category short name (no spaces, unix-like):
<br><input type="text" name="form_shortname">
<p>New category full name (VARCHAR 80):
<br><input type="text" name="form_fullname">
<p>New category description (VARCHAR 255):
<br><input type="text" size="80" name="form_description">
<p>Parent Category:
<br><SELECT name="form_parent">
<?php
// generate list of possible parents
$res_cat = db_query("SELECT shortname,fullname,trove_cat_id FROM trove_cat");
while ($row_cat = db_fetch_array($res_cat)) {
	print ('<OPTION value="'.$row_cat["trove_cat_id"].'">'.$row_cat["fullname"]."\n");
}
?>
</SELECT>
<br><input type="submit" name="Submit" value="Submit">
</form>

<?php
$HTML->footer(array());

?>
