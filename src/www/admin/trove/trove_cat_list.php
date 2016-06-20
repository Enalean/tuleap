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

    // print current node, then all subnodes
    print ('<BR>');
    for ($i = 0; $i < $depth; $i++) {
        print "&nbsp; &nbsp; ";
    }
    html_image('ic/cfolder15.png', array());
    print ('&nbsp; ' . $purifier->purify($text) . " ");
    if ($nodeid != 0) {
        print ('&nbsp; <A href="trove_cat_edit.php?trove_cat_id=' . $nodeid . '">[' . $Language->getText('admin_trove_cat_list', 'edit') . ']</A> ');
    }
    if ($delete_ok) {
        print ('&nbsp; <A href="trove_cat_delete.php?trove_cat_id=' . $nodeid . '">[' . $Language->getText('admin_trove_cat_list', 'delete') . ']</A> ');
    }
    if ($nodeid != 0) {
        print ('&nbsp;' . help_button('trove_cat', $nodeid) . "\n");
    }

    $res_child = db_query("SELECT trove_cat_id,fullname,parent FROM trove_cat "
            . "WHERE parent='" . db_ei($nodeid) . "' ORDER BY fullpath");
    while ($row_child = db_fetch_array($res_child)) {
        $delete_ok = ($row_child["parent"] != 0);
        printnode($row_child["trove_cat_id"], $row_child["fullname"], $depth+1, $delete_ok);
    }
}

// ########################################################

$HTML->header(array('title'=>$Language->getText('admin_trove_cat_list','title'), 'main_classes' => array('framed')));

echo "<H2>".$Language->getText('admin_trove_cat_list','header')."</H2>";

printnode(0, $Language->getText('admin_trove_cat_edit','root'));

$HTML->footer(array());

?>
