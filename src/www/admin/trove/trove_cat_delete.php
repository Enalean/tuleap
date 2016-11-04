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
$request =& HTTPRequest::instance();
// #######################################################

function listallchilds($nodeid, &$list) {
    // list current node and then all subnodes
    $res_child = db_query("SELECT trove_cat_id, parent, shortname FROM trove_cat "
		."WHERE parent='".db_ei($nodeid)."'");
    while ($row_child = db_fetch_array($res_child)) {
	$list[] = $row_child['trove_cat_id'];
	listallchilds($row_child['trove_cat_id'], $list);
    }

}

// ########################################################
// FORM SUBMISSION: Delete or Cancel
//
$trove_cat_id = (int)$request->get('trove_cat_id');
if ($request->get("Delete") && $trove_cat_id) {
    $res_cat = db_query("SELECT trove_cat_id, parent, root_parent, shortname "
		       ."FROM trove_cat "
		       ." WHERE trove_cat_id =". $trove_cat_id);
    if (!$res_cat || db_numrows($res_cat) < 1) {
	$feedback .= "**ERROR** Category could not be found in database";
	session_redirect("/admin/trove/trove_cat_list.php");
    }

    $row_cat = db_fetch_array($res_cat);

    // Determine the parent category. If it's a top category then simply delete
    // the rows for those category and this group_id in the group_link_table.
    // If the parent is not a root category then reassign the category of the group
    // to this parent
    if ($row_cat['parent'] == $row_cat['root_parent']) {
	$res_del = db_query('DELETE FROM trove_group_link '
			    .' WHERE trove_cat_id='.db_ei($row_cat['trove_cat_id']));
    } else {
	$res_upd = db_query('UPDATE trove_group_link '
			    .' SET trove_cat_id='.db_ei($row_cat['parent'])
			    .' WHERE trove_cat_id='.db_ei($row_cat['trove_cat_id']));
    }

    // Find all child categories
    $list_child = array();
    listallchilds($row_cat['trove_cat_id'], $list_child);
    $list_child[] = $row_cat['trove_cat_id'];

    // Delete the category and all childs
    $result = db_query('DELETE FROM trove_cat WHERE trove_cat_id IN ('.  db_ei_implode($list_child).')');
    if (!$result || db_affected_rows($result) < 1) {
	$feedback .= "**ERROR** Category could not be  deleted";
    } else {
	$feedback .= "Category (and childs) succesfully deleted";
    }
    session_redirect("/admin/trove/trove_cat_list.php");
}

if ($request->get("Cancel")) {
    session_redirect("/admin/trove/trove_cat_list.php");
}

// ########################################################
// MAIN PAGE
//
$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=".db_ei($request->getValidated('trove_cat_id', 'uint', 0)));
if (db_numrows($res_cat)<1) {
    exit_error("ERROR",$Language->getText('admin_trove_cat_delete','error_nocat'));
}
$row_cat = db_fetch_array($res_cat);

$HTML->header(array('title'=>$Language->getText('admin_trove_cat_delete','title'), 'main_classes' => array('tlp-framed')));
?>

<H2><?php echo $Language->getText('admin_trove_cat_delete','header').': '.$row_cat["fullname"]; ?>'</H2>

<P><b><?php echo $Language->getText('admin_trove_cat_delete','warning'); ?></b>
<form action="trove_cat_delete.php" method="post">
<input type="hidden" name="trove_cat_id" value="<?= $trove_cat_id; ?>">

<table class="tlp-table" style="width: auto;">
<tbody>
<tr><th><?php echo $Language->getText('admin_trove_cat_add','short_name'); ?></th><td> <?php print $row_cat["shortname"]; ?></td></tr>
<tr><th><?php echo $Language->getText('admin_trove_cat_add','full_name'); ?></th><td> <?php print $row_cat["fullname"]; ?></td></tr>
<tr><th><?php echo $Language->getText('admin_trove_cat_add','description'); ?></th><td> <?php print $row_cat["description"]; ?></td></tr>
</tbody>
</table>

<?php
// See if there are childs
$child_list = array();
listallchilds($request->get('trove_cat_id', 'uint', 0), $child_list);

if (($nb_child = count($child_list)) > 0) {
    echo "<p>".$Language->getText('admin_trove_cat_delete','caution_child',array($nb_child));
} else {
    echo "<p>".$Language->getText('admin_trove_cat_delete','no_child');
}

// See if projects are using this category or one of his child
$child_list[] = $trove_cat_id;
$res_proj = db_query("SELECT DISTINCT group_id FROM trove_group_link "
		     ."WHERE trove_cat_id IN (".join(',',$child_list).")");
$nb_proj = db_numrows($res_proj);

if ($nb_proj > 0) {
    echo "<p>".$Language->getText('admin_trove_cat_delete','caution_proj',array($nb_proj));
} else {
    echo "<p>".$Language->getText('admin_trove_cat_delete','no_proj');
}
?>

<p>
<br><input type="submit" name="Delete" class="tlp-button-danger" value="<?php echo $Language->getText('global','btn_delete'); ?>">
<input type="submit" name="Cancel" class="tlp-button-secondary" value="<?php echo $Language->getText('global','btn_cancel'); ?>">
</form>

<?php
$HTML->footer(array());

?>
