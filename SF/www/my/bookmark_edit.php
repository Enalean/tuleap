<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require ('pre.php');
require ('bookmarks.php');

$HTML->header(array("title"=>"Edit Bookmark"));

print "<H3>Edit Bookmark</H3>";

if ($bookmark_url && $bookmark_title) {
	bookmark_edit($bookmark_id, $bookmark_url, $bookmark_title);
}

$result = db_query("SELECT * from user_bookmarks where "
	. "bookmark_id='".$bookmark_id."' and user_id='".user_getid()."'");
if ($result) {
	$bookmark_url = db_result($result,0,'bookmark_url');
	$bookmark_title = db_result($result,0,'bookmark_title');
}
?>
<FORM METHOD="POST">
Bookmark URL:<br>
<input type="text" name="bookmark_url" value="<?php echo $bookmark_url; ?>">
<p>
Bookmark Title:<br>
<input type="text" name="bookmark_title" value="<?php echo $bookmark_title; ?>">
<p>
<input type="submit" value=" Submit Form ">
</form>
<?php

print "<P><A HREF=\"/my/\">Return</A>";

$HTML->footer(array());

?>
