<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/bookmarks.php');

$LANG->loadLanguageMsg('my/my');

$HTML->header(array("title"=>$LANG->getText('bookmark_delete', 'title')));

print "<H3>".$LANG->getText('bookmark_delete', 'title')."</H3>\n";

if ($bookmark_id) {
	bookmark_delete ($bookmark_id);
	print "<p>".$LANG->getText('bookmark_delete', 'deleted').
	    "<P><A HREF=\"/my/\">[".$LANG->getText('global', 'back_home')."]</A>";
}

$HTML->footer(array());

?>
