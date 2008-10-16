<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('trove.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

// ########################################################
$request =& HTTPRequest::instance();
if ($request->exist('Submit')) {
	$newroot = trove_getrootcat($request->get('form_parent'));

	if ($request->get('form_shortname')) {
		db_query('INSERT INTO trove_cat '
			.'(shortname,fullname,description,parent,version,root_parent) values ('
			.'\''.db_escape_string($request->get('form_shortname'))
			.'\',\''.db_escape_string($request->get('form_fullname'))
			.'\',\''.db_escape_string($request->get('form_description'))
			.'\',\''.db_escape_string($request->get('form_parent'))
			.'\','.date("Ymd",time()).'01'
			.',\''.$newroot.'\')');
	} 

	// update full paths now
        trove_genfullpaths($newroot,trove_getfullname($newroot),$newroot);

	session_redirect("/admin/trove/trove_cat_list.php");
} 

$HTML->header(array('title'=>$Language->getText('admin_trove_cat_add','title')));
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
<br><input type="text" size="80"  maxlen="255" name="form_description">
<?php echo $Language->getText('admin_trove_cat_add','description_note'); ?>
<p><?php echo $Language->getText('admin_trove_cat_add','parent'); ?>:
<?php echo trove_get_html_cat_select_parent(); ?>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</form>

<?php
$HTML->footer(array());

?>
