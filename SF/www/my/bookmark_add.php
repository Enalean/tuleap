<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/bookmarks.php');

$LANG->loadLanguageMsg('my/my');

$HTML->header(array("title"=>$LANG->getText('bookmark_add', 'title')));

print "<H3>".$LANG->getText('bookmark_add', 'title')."</H3>";

if ($bookmark_url) {
    print $LANG->getText('bookmark_add', 'message', array($bookmark_url,$bookmark_title))."<p>\n";

	$bookmark_id = bookmark_add ($bookmark_url, $bookmark_title);
	print "<A HREF=\"$bookmark_url\">".$LANG->getText('bookmark_add', 'visit')."</A> - ";
	print "<A HREF=\"/my/bookmark_edit.php?bookmark_id=$bookmark_id\">".$LANG->getText('bookmark_add', 'edit')."</A>";
	print "<p><A HREF=\"/my/\">[".$LANG->getText('global', 'back_home')."]</A>";
} else {
	?>
	<FORM METHOD=POST>
	<?php echo $LANG->getText('bookmark_add', 'bkm_url'); ?>:<br>
	<input type="text" size="60" name="bookmark_url" value="http://">
	<p>
	<?php echo $LANG->getText('bookmark_add', 'bkm_title'); ?>:<br>
	<input type="text" size="60" name="bookmark_title" value="<?php echo $LANG->getText('bookmark_add', 'favorite'); ?>">
	<p>
	<input type="submit" value="<?php echo $LANG->getText('global', 'btn_submit'); ?>">
	</form>
	<?php
}

$HTML->footer(array());

?>
