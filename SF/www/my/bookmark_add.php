<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/bookmarks.php');

$HTML->header(array("title"=>"Add New Bookmark"));

print "<H3>Add New Bookmarks</H3>";

if ($bookmark_url) {
	print "Added bookmark for <b>'$bookmark_url'</b> with title <b>'$bookmark_title'</b>.<p>";

	$bookmark_id = bookmark_add ($bookmark_url, $bookmark_title);
	print "<A HREF=\"$bookmark_url\">Visit the bookmarked page</A> - ";
	print "<A HREF=\"/my/bookmark_edit.php?bookmark_id=$bookmark_id\">Edit this bookmark</A> - ";
	print "<A HREF=\"/my/\">Back to your homepage</A>";
} else {
	?>
	<FORM METHOD=POST>
	Bookmark URL:<br>
	<input type="text" name="bookmark_url" value="http://">
	<p>
	Bookmark Title:<br>
	<input type="text" name="bookmark_title" value="My Fav Site">
	<p>
	<input type="submit" value=" Submit Form ">
	</form>
	<?php
}

$HTML->footer(array());

?>
