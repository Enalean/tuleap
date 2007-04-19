<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: trove_cat_edit.php 1405 2005-03-21 14:41:41Z guerin $

require_once('pre.php');
require_once('trove.php');

$Language->loadLanguageMsg('admin/admin');

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
if (db_numrows($res_cat)<1) {
    exit_error("ERROR",$Language->getText('admin_trove_cat_delete','error_nocat'));
}
$row_cat = db_fetch_array($res_cat);

$HTML->header(array(title=>$Language->getText('admin_trove_cat_edit','title')));
?>

<H2><?php echo $Language->getText('admin_trove_cat_edit','header'); ?></H2>
<form action="trove_cat_edit.php" method="post">
<input type="hidden" name="form_trove_cat_id" value="<?php
  print $GLOBALS['trove_cat_id']; ?>">
<p><?php echo $Language->getText('admin_trove_cat_add','short_name'); ?>
<br><input type="text"  size="25" maxlen="80" name="form_shortname" value="<?php print $row_cat["shortname"]; ?>">
<?php echo $Language->getText('admin_trove_cat_add','short_name_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','full_name'); ?>
<br><input type="text"  size="45" maxlen="80" name="form_fullname" value="<?php print $row_cat["fullname"]; ?>">
<?php echo $Language->getText('admin_trove_cat_add','full_name_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','description'); ?>
<br><input type="text" name="form_description" size="80"  maxlen="255" value="<?php print $row_cat["description"]; ?>">
<?php echo $Language->getText('admin_trove_cat_add','description_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','parent'); ?>:
<br><SELECT name="form_parent">
<?php
// generate list of possible parents
// add root which is not in db
print ('<OPTION value="0"');
	if ($row_cat["parent"] == 0) print ' selected';
	print ('>'.$Language->getText('admin_trove_cat_edit','root')."\n");
$res_parent = db_query("SELECT shortname,fullname,trove_cat_id FROM trove_cat where fullpath not like '".addslashes($row_cat["fullpath"])." ::%' and fullpath not like '".addslashes($row_cat["fullpath"])."'");
while ($row_parent = db_fetch_array($res_parent)) {
	print ('<OPTION value="'.$row_parent["trove_cat_id"].'"');
	if ($row_cat["parent"] == $row_parent["trove_cat_id"]) print ' selected';
	print ('>'.$row_parent["fullname"]."\n");
}
?>
</SELECT>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</form>

<?php
$HTML->footer(array());

?>
