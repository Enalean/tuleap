<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
        Docmentation Manager
        by Quentin Cregan, SourceForge 06/2000
*/

require($DOCUMENT_ROOT.'/include/pre.php');
require('./doc_utils.php');

if (!$group_id) {
    exit_no_group();
}

$Language->loadLanguageMsg('docman/docman');

$params=array('title'=>$Language->getText('docman_index','title',array(group_getname($group_id))),
              'help'=>'DocumentManager.html',
              'pv'=>$pv);
docman_header($params);

if ($pv) {
    echo "<h2>".$Language->getText('docman_index','header')."</h2>";
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H2>'.$Language->getText('docman_index','header').'</H2>';
    echo "</TD>";
    echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".
        util_get_image_theme("msg.png")."' border='0'>&nbsp;".
        $Language->getText('global','printer_version')."</A> ) </TD>";
    echo "</TR></TABLE>";    

}

display_doc_list($group_id);

docman_footer($params);

?>
