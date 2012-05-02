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

require_once('pre.php');
require_once('www/docman/doc_utils.php');


$group_id = 1;

echo $HTML->header(array('title'=> $Language->getText('docs_site_index','title')));

echo '<H2>'.$Language->getText('docs_site_index','title').'</H2>';


display_doc_list($group_id);

$HTML->footer(array());

?>
