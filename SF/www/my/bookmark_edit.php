<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/bookmarks.php');

$LANG->loadLanguageMsg('my/my');

if ($bookmark_url && $bookmark_title) {
	bookmark_edit($bookmark_id, $bookmark_url, $bookmark_title);
        header ("Location: /my/");
}

$HTML->header(array("title"=>$LANG->getText('bookmark_edit', 'title')));

print "<H3>".$LANG->getText('bookmark_edit', 'title')."</H3>\n";

$result = db_query("SELECT * from user_bookmarks where "
	. "bookmark_id='".$bookmark_id."' and user_id='".user_getid()."'");
if ($result) {
	$bookmark_url = db_result($result,0,'bookmark_url');
	$bookmark_title = db_result($result,0,'bookmark_title');
}
?>
<FORM METHOD="POST">
<?php echo $LANG->getText('bookmark_add', 'bkm_url'); ?>:<br>
<input type="text" name="bookmark_url" size="60" value="<?php echo $bookmark_url; ?>">
<p>
<?php echo $LANG->getText('bookmark_add', 'bkm_title'); ?>:<br>
<input type="text" name="bookmark_title" size="60" value="<?php echo $bookmark_title; ?>">
<p>
<input type="submit" value="<?php echo $LANG->getText('global', 'btn_submit'); ?>">
</form>
<?php

print "<P><A HREF=\"/my/\">[".$LANG->getText('global', 'back_home')."]</A>";

$HTML->footer(array());

?>
