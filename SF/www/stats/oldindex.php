<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$ 

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/cache.php');

$Language->loadLanguageMsg('stats/stats');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('stats_oldindex','stats')));

echo '<P><B> <A HREF="/stats/project.php">'.$Language->getText('stats_oldindex','project_stats').'</A> | <A HREF="/stats/">'.$Language->getText('stats_oldindex','sf_stats').'</A> </B>';
echo '<P>';

echo cache_display("stats_sf_stats",'1',1800);

$HTML->footer(array());
?>
