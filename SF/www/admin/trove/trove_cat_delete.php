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

function listallchilds ($nodeid, $text, &$list) {
    // list current node and then all subnodes
    $res_child = db_query("SELECT trove_cat_id, parent, shortname FROM trove_cat "
		."WHERE parent='$nodeid'");
    while ($row_child = db_fetch_array($res_child)) {
	$list[] = $row_child['trove_cat_id'];
	listallchilds ($row_child['trove_cat_id'], $row_child['shortname'], &$list);
    }
    
}

// ########################################################
// FORM SUBMISSION: Delete or Cancel
//
if ($GLOBALS["Delete"] && $GLOBALS['form_trove_cat_id']) {
    $res_cat = db_query("SELECT trove_cat_id, parent, root_parent, shortname "
		       ."FROM trove_cat "
		       ." WHERE trove_cat_id =".$GLOBALS['form_trove_cat_id']);
    if (!$res_cat || db_numrows($res_cat) < 1) {
	$feedback .= "**ERROR** Category '".$GLOBALS['form_shortname']."' could not be found in database";
	session_redirect("/admin/trove/trove_cat_list.php");
    }

    $row_cat = db_fetch_array($res_cat);

    // Determine the parent category. If it's a top category then simply delete
    // the rows for those category and this group_id in the group_link_table.
    // If the parent is not a root category then reassign the category of the group
    // to this parent
    if ($row_cat['parent'] == $row_cat['root_parent']) {
	$res_del = db_query('DELETE FROM trove_group_link '
			    .' WHERE trove_cat_id='.$row_cat['trove_cat_id']);
    } else {
	$res_upd = db_query('UPDATE trove_group_link '
			    .' SET trove_cat_id='.$row_cat['parent']
			    .' WHERE trove_cat_id='.$row_cat['trove_cat_id']);
    }

    // Find all child categories
    $list_child = array();
    listallchilds ($row_cat['trove_cat_id'], $row_cat['shortname'], &$list_child);
    $list_child[] = $row_cat['trove_cat_id'];

    // Delete the category and all childs
    $result = db_query('DELETE FROM trove_cat '
		       .'WHERE trove_cat_id IN ('.join(',',$list_child).')');
    if (!$result || db_affected_rows($result) < 1) {
	$feedback .= "**ERROR** Category '".$GLOBALS['form_shortname']."' could not be  deleted";
    } else {
	$feedback .= "Category '".$GLOBALS['form_shortname']."' (and childs) succesfully deleted";
    }
    session_redirect("/admin/trove/trove_cat_list.php");
} 

if ($GLOBALS["Cancel"]) {
    session_redirect("/admin/trove/trove_cat_list.php");
}

// ########################################################
// MAIN PAGE
//
$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=$trove_cat_id");
if (db_numrows($res_cat)<1) 
	{ exit_error("No Suck Category","That trove cat does not exist"); }
$row_cat = db_fetch_array($res_cat);

$HTML->header(array(title=>"Trove - Delete Category"));
?>

<H2>Delete a Trove Category - '<?php print $row_cat["fullname"]; ?>'</H2>

<P><span class="feedback">**WARNING**</span>
<b> You are about to delete a trove category of the software map.</b>
<form action="trove_cat_delete.php" method="post">
<input type="hidden" name="form_trove_cat_id" value="<?php
  print $GLOBALS['trove_cat_id']; ?>">
<input type="hidden" name="form_shortname" value="<?php
  print $GLOBALS['shortname']; ?>">

<table border="1" cellpadding="2">
<tr><td>Category short name</td><td> <?php print $row_cat["shortname"]; ?></td></tr>
<tr><td>Category full name</td><td> <?php print $row_cat["fullname"]; ?></td></tr>
<tr><td>Category description</td><td> <?php print $row_cat["description"]; ?></td></tr>
</table>

<?php
// See if there are childs
$child_list = array();
listallchilds($GLOBALS['trove_cat_id'],$GLOBALS['shortname'], $child_list);

if (($nb_child = count($child_list)) > 0) {
    echo "<p><span class=\"feedback\">**CAUTION**</span>";
    echo " This category has <b>$nb_child child categories</b>.";
    echo " Deleting the category will also delete all the childs recursively!!";
} else {
    echo "<p>This category has NO child category";
}

// See if projects are using this category or one of his child
$child_list[] = $GLOBALS['trove_cat_id'];
$res_proj = db_query("SELECT DISTINCT group_id FROM trove_group_link "
		     ."WHERE trove_cat_id IN (".join(',',$child_list).")");
$nb_proj = db_numrows($res_proj);

if ($nb_proj > 0) {
    echo "<p><span class=\"feedback\">**CAUTION**</span>";
    echo " This category and its childs (if any) are being used by <b>$nb_proj project(s)</b>.";
    echo " Deleting this category will reset existing project categories to the parent category (or 'None' if the parent is a root category)";
} else {
    echo "<p>This category is NOT used by any project";
}
?>

<h3>To confirm deletion click on 'Delete'.</h3>
<p>
<br><input type="submit" name="Delete" value="Delete">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="Cancel" value="Cancel">
</form>

<?php
$HTML->footer(array());

?>
