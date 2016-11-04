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

// #######################################################

function printnode($nodeid, $text, $depth = 0, $delete_ok = false) {
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();

    echo '<tr>';
    echo '<td class="siteadmin-trovecat-list-category">';
    for ($i = 0; $i < $depth; $i++) {
        print "&nbsp; &nbsp; ";
    }
    echo '<i class="fa fa-folder-o"></i> '. $purifier->purify($text);
    echo '</td>';
    echo '<td class="siteadmin-trovecat-list-actions">';
    if ($nodeid != 0) {
        echo '<a href="trove_cat_edit.php?trove_cat_id=' . $nodeid . '" class="tlp-button-primary tlp-button-outline tlp-button-mini">';
        echo '<i class="fa fa-edit"></i> '. $Language->getText('admin_trove_cat_list', 'edit') . '</a> ';
    }
    if ($delete_ok) {
        echo '<a href="trove_cat_delete.php?trove_cat_id=' . $nodeid . '" class="tlp-button-danger tlp-button-outline tlp-button-mini">';
        echo '<i class="fa fa-remove"></i> '. $Language->getText('admin_trove_cat_list', 'delete') . '</a> ';
    }
    echo '</td>';
    echo '</tr>';

    $res_child = db_query("SELECT trove_cat_id,fullname,parent FROM trove_cat "
            . "WHERE parent='" . db_ei($nodeid) . "' ORDER BY fullpath");
    while ($row_child = db_fetch_array($res_child)) {
        $delete_ok = ($row_child["parent"] != 0);
        printnode($row_child["trove_cat_id"], $row_child["fullname"], $depth+1, $delete_ok);
    }
}

// ########################################################

$HTML->header(array('title'=>$Language->getText('admin_trove_cat_list','title'), 'main_classes' => array('tlp-framed', 'tlp-centered')));

echo "<h1>".$Language->getText('admin_trove_cat_list','header')."</h1>";

echo '<p><a href="/admin/trove/trove_cat_add.php" class="tlp-button-primary">';
echo '<i class="fa fa-plus"></i> '. $GLOBALS['Language']->getText('admin_trove_cat_list', 'add') .'</a>';
echo '</p>';

echo '<table class="tlp-table">';
echo '<thead><tr><th>Category</th><th></th></thead>';
echo '<tbody>';
printnode(0, $Language->getText('admin_trove_cat_edit','root'));
echo '</tbody>';
echo '</table>';

$HTML->footer(array());
