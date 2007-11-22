<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require_once('pre.php');
require_once('bookmarks.php');

$Language->loadLanguageMsg('my/my');

$request =& HTTPRequest::instance();

//@filtertodo: check bookmark as int.
$bookmark_id    = (int) $request->get('bookmark_id');
$bookmark_url   = $request->get('bookmark_url');
$bookmark_title = $request->get('bookmark_title');

if ($request->exist('bookmark_url') && $request->exist('bookmark_title')) {
	bookmark_edit($bookmark_id, $bookmark_url, $bookmark_title);
    header ("Location: /my/");
}

$purifier =& CodeX_HTMLPurifier::instance();

$HTML->header(array("title"=>$Language->getText('bookmark_edit', 'title')));

print "<H3>".$Language->getText('bookmark_edit', 'title')."</H3>\n";

$result = db_query("SELECT * from user_bookmarks where "
	. "bookmark_id='".$bookmark_id."' and user_id='".user_getid()."'");
if ($result) {
	$bookmark_url = db_result($result,0,'bookmark_url');
	$bookmark_title = db_result($result,0,'bookmark_title');
}
?>
<FORM METHOD="POST">
<?php echo $Language->getText('bookmark_add', 'bkm_url'); ?>:<br>
<input type="text" name="bookmark_url" size="60" value="<?php echo $purifier->purify($bookmark_url); ?>">
<p>
<?php echo $Language->getText('bookmark_add', 'bkm_title'); ?>:<br>
<input type="text" name="bookmark_title" size="60" value="<?php echo $purifier->purify($bookmark_title); ?>">
<p>

<input type="submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
</form>
<?php

print "<P><A HREF=\"/my/\">[".$Language->getText('global', 'back_home')."]</A>";

$HTML->footer(array());

?>
