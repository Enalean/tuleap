<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require_once('pre.php');
require_once('bookmarks.php');

$Language->loadLanguageMsg('my/my');

$HTML->header(array("title"=>$Language->getText('bookmark_add', 'title')));

print "<H3>".$Language->getText('bookmark_add', 'title')."</H3>";

$request =& HTTPRequest::instance();
$vUrl = new Valid_String('bookmark_url');
$vUrl->required();
$vTitle = new Valid_String('bookmark_title');
$vTitle->required();
if ($request->valid($vUrl) && $request->valid($vTitle)) {
    $purifier =& CodeX_HTMLPurifier::instance();

    $bookmark_url = $request->get('bookmark_url');
    $bookmark_title = $request->get('bookmark_title');

    print $Language->getText('bookmark_add', 'message', array($purifier->purify($bookmark_url), $purifier->purify($bookmark_title)))."<p>\n";

	$bookmark_id = bookmark_add ($bookmark_url, $bookmark_title);
	print '<A HREF="'. $purifier->purify($bookmark_url) .'">'.$Language->getText('bookmark_add', 'visit')."</A> - ";
	print '<A HREF="/my/bookmark_edit.php?bookmark_id='. $bookmark_id .'">'.$Language->getText('bookmark_add', 'edit')."</A>";
	print '<p><A HREF="/my/">['.$Language->getText('global', 'back_home')."]</A>";
} else {
	?>
	<FORM METHOD=POST>
	<?php echo $Language->getText('bookmark_add', 'bkm_url'); ?>:<br>
	<input type="text" size="60" name="bookmark_url" value="http://">
	<p>
	<?php echo $Language->getText('bookmark_add', 'bkm_title'); ?>:<br>
	<input type="text" size="60" name="bookmark_title" value="<?php echo $Language->getText('bookmark_add', 'favorite'); ?>">
	<p>
	<input type="submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
	</form>
	<?php
}

$HTML->footer(array());

?>
