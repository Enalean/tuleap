<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/include/pre.php');
require('../snippet/snippet_utils.php');
require($DOCUMENT_ROOT.'/include/cache.php');

snippet_header(array('title'=>'Snippet Library', 
		     'header'=>'Snippet Library',
		     'help' => 'TheCodeXMainMenu.html#TheCodeSnippetLibrary'));

echo cache_display('snippet_mainpage','4',1800);

snippet_footer(array());

?>
