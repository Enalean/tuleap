<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require_once('pre.php');
require_once('bookmarks.php');

$Language->loadLanguageMsg('my/my');

$HTML->header(array("title"=>$Language->getText('bookmark_delete', 'title')));

print "<H3>".$Language->getText('bookmark_delete', 'title')."</H3>\n";

if ($bookmark_id) {
	bookmark_delete ($bookmark_id);
	print "<p>".$Language->getText('bookmark_delete', 'deleted').
	    "<P><A HREF=\"/my/\">[".$Language->getText('global', 'back_home')."]</A>";
}

$HTML->footer(array());

?>
