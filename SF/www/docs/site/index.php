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
require($DOCUMENT_ROOT.'/docman/doc_utils.php');

$Language->loadLanguageMsg('docman/docman');

$group_id = 1;

echo $HTML->header(array('title'=> $Language->getText('docs_site_index','title')));

echo '<H2>'.$Language->getText('docs_site_index','title').'</H2>';


display_doc_list($group_id);

$HTML->footer(array());

?>
