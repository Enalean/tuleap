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

$trove_cat_dao         = new TroveCatDao();
$trove_cat_factory     = new TroveCatFactory($trove_cat_dao);
$list_of_top_level_category_ids = array_keys($trove_cat_factory->getMandatoryParentCategoriesUnderRoot());

// ########################################################
$request =& HTTPRequest::instance();
if ($request->exist('Submit')) {
	$newroot   = trove_getrootcat($request->get('form_parent'));
        $mandatory = $request->get('form_mandatory');

        if ($newroot !== '0') {
            $mandatory = 0;
        }

        if (! in_array($newroot, $list_of_top_level_category_ids)) {
            $display = 0;
        } else {
            $display = $request->get('form_display');
        }

	if (db_escape_string($request->get('form_shortname'))) {
            db_query('UPDATE trove_cat '
			.'SET '
			.'shortname=\''.db_escape_string($request->get('form_shortname'))
			.'\',fullname=\''.db_escape_string($request->get('form_fullname'))
			.'\',description=\''.db_escape_string($request->get('form_description'))
			.'\',parent=\''.db_escape_string($request->get('form_parent'))
			.'\',version='.date("Ymd",time()).'01'
			.',root_parent=\''.$newroot
            .'\',mandatory='.db_escape_int($mandatory)
            .',display_during_project_creation='.db_escape_int($display)
			.' WHERE trove_cat_id='.$request->getValidated('form_trove_cat_id', 'uint', 0));
	}
	// update full paths now
	trove_genfullpaths($newroot,trove_getfullname($newroot),$newroot);

	session_redirect("/admin/trove/trove_cat_list.php");
}

$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=".$request->getValidated('trove_cat_id', 'uint', 0));
if (db_numrows($res_cat)<1) {
    exit_error("ERROR",$Language->getText('admin_trove_cat_delete','error_nocat'));
}
$row_cat = db_fetch_array($res_cat);

$purifier = Codendi_HTMLPurifier::instance();
$HTML->header(array('title'=>$Language->getText('admin_trove_cat_edit','title'), 'main_classes' => array('tlp-framed')));

$list_of_top_level_category_ids_purified = $purifier->purify(json_encode ($list_of_top_level_category_ids));

?>

<H2><?php echo $Language->getText('admin_trove_cat_edit','header'); ?></H2>
<form action="trove_cat_edit.php" method="post" data-top-level-ids="<?php echo $list_of_top_level_category_ids_purified ;?>" name="form_trove_cat_edit">
<input type="hidden" name="form_trove_cat_id" value="<?php
  print $row_cat['trove_cat_id']; ?>">
<p><?php echo $Language->getText('admin_trove_cat_add','short_name'); ?>
<br><input type="text"  size="25" maxlen="80" name="form_shortname" value="<?php print $purifier->purify($row_cat["shortname"]); ?>">
<?php echo $Language->getText('admin_trove_cat_add','short_name_note'); ?>
</p><p><?php echo $Language->getText('admin_trove_cat_add','full_name'); ?>
<br><input type="text"  size="45" maxlen="80" name="form_fullname" value="<?php print $purifier->purify($row_cat["fullname"]); ?>">
<?php echo $Language->getText('admin_trove_cat_add','full_name_note'); ?>
</p><p><?php echo $Language->getText('admin_trove_cat_add','description'); ?>
<br><input type="text" name="form_description" size="80"  maxlen="255" value="<?php print $purifier->purify($row_cat["description"]); ?>">
<?php echo $Language->getText('admin_trove_cat_add','description_note'); ?>
</p><p><?php echo $Language->getText('admin_trove_cat_add','parent'); ?>:
<?php echo trove_get_html_cat_select_parent($row_cat["parent"], $row_cat["fullpath"]); ?>
</p><label class="trove-mandatory">
<input type="checkbox" value="1" name="form_mandatory" <?php if ($row_cat["mandatory"]) { echo ("checked=checked"); } ?>>
<?php echo $Language->getText('admin_trove_cat_add','mandatory'); ?>
</label>
<span class="help-block"><?php echo $Language->getText('admin_trove_cat_add','mandatory_note'); ?></span>

<label class="trove-mandatory">
<input type="checkbox" value="1" name="form_display" <?php if ($row_cat["display_during_project_creation"]) { echo ("checked=checked"); } ?>>
<?php echo $Language->getText('admin_trove_cat_add','display_at_project_creation'); ?>
</label>
<span class="help-block"><?php echo $Language->getText('admin_trove_cat_add','display_note'); ?></span>

<p><input type="submit" name="Submit" class="tlp-button-primary" value="<?php echo $Language->getText('global','btn_submit'); ?>">
</p></form>

<?php
$HTML->footer(array());
