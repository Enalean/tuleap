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

$HTML->header(array(title=>$Language->getText('admin_trove_cat_add','title')));
?>

<H2><?php echo $Language->getText('admin_trove_cat_add','header'); ?></H2>

<form action="trove_cat_add.php" method="post">
<p><?php echo $Language->getText('admin_trove_cat_add','short_name'); ?>:
<br><input type="text" size="25" maxlen="80" name="form_shortname">
<?php echo $Language->getText('admin_trove_cat_add','short_name_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','full_name'); ?>:
<br><input type="text"  size="45" maxlen="80" name="form_fullname">
<?php echo $Language->getText('admin_trove_cat_add','full_name_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','description'); ?>:
<input type="text" size="80"  maxlen="255" name="form_description">
<?php echo $Language->getText('admin_trove_cat_add','description_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','parent'); ?>:
<br><SELECT name="form_parent">
<?php
// generate list of possible parents
// add root which is not in db
print ('<OPTION value="0">Root'."\n");
$res_cat = db_query("SELECT shortname,fullname,trove_cat_id FROM trove_cat");
while ($row_cat = db_fetch_array($res_cat)) {
	print ('<OPTION value="'.$row_cat["trove_cat_id"].'">'.$row_cat["fullname"]."\n");
}
?>
</SELECT>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</form>

<?php
$HTML->footer(array());

?>
