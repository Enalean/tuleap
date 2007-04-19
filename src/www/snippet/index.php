<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: index.php 1405 2005-03-21 14:41:41Z guerin $

require_once('vars.php');
require_once('pre.php');
require('../snippet/snippet_utils.php');
require_once('cache.php');

$Language->loadLanguageMsg('snippet/snippet');


snippet_header(array('title'=>$Language->getText('snippet_browse','s_library'), 
		     'header'=>$Language->getText('snippet_browse','s_library'),
		     'help' => 'TheCodeXMainMenu.html#TheCodeSnippetLibrary'));

echo cache_display('snippet_mainpage','4',1800);

snippet_footer(array());

?>
