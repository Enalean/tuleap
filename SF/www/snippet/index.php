<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('vars.php');
require ('pre.php');
require ('../snippet/snippet_utils.php');
require ('cache.php');

snippet_header(array('title'=>'Snippet Library', 'header'=>'Snippet Library'));

echo cache_display('snippet_mainpage','4',1800);

snippet_footer(array());

?>
