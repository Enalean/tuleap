<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('vars.php');
require_once('pre.php');
require('../snippet/snippet_utils.php');
require_once('cache.php');



snippet_header(array('title'=>$Language->getText('snippet_browse','s_library'), 
		     'header'=>$Language->getText('snippet_browse','s_library'),
		     'help' => 'TheCodendiMainMenu.html#TheCodeSnippetLibrary'));

echo cache_display('snippet_mainpage','4',1800);

snippet_footer(array());

?>
