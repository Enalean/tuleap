<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright (c) Enalean, 2015-Present. All rights reserved
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/bookmarks.php';
require_once __DIR__ . '/my_utils.php';

$request = HTTPRequest::instance();

$vId = new Valid_UInt('bookmark_id');
$vId->setErrorMessage('bookmark_id is required');
$vId->required();
if (! $request->valid($vId)) {
    $GLOBALS['Response']->redirect('/my');
} else {
    $bookmark_id = (int) $request->get('bookmark_id');
}

$vUrl = new Valid_String('bookmark_url');
$vUrl->setErrorMessage('Url is required');
$vUrl->required();
$vTitle = new Valid_String('bookmark_title');
$vTitle->setErrorMessage('Title is required');
$vTitle->required();

$bookmark_url_id = '/my/bookmark_edit.php?bookmark_id=' . $bookmark_id;
$csrf_token      = new CSRFSynchronizerToken($bookmark_url_id);

$bookmark_url   = '';
$bookmark_title = '';

if (
    $request->isPost() &&
    $request->valid($vUrl) &&
    $request->valid($vTitle)
) {
    $csrf_token->check();

    $bookmark_url   = $request->get('bookmark_url');
    $bookmark_title = $request->get('bookmark_title');

    my_check_bookmark_URL($bookmark_url, $bookmark_url_id);

    bookmark_edit($bookmark_id, $bookmark_url, $bookmark_title);
    $GLOBALS['Response']->redirect('/my');
}

$purifier = Codendi_HTMLPurifier::instance();

$HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('bookmark_edit', 'title')));

print "<H3>" . $Language->getText('bookmark_edit', 'title') . "</H3>\n";

$result = db_query("SELECT * from user_bookmarks where "
                   . "bookmark_id=" . db_ei($bookmark_id) . " and user_id=" . db_ei(UserManager::instance()->getCurrentUser()->getId()));
if ($result) {
    $bookmark_url   = db_result($result, 0, 'bookmark_url');
    $bookmark_title = db_result($result, 0, 'bookmark_title');
}
?>
<FORM METHOD="POST">
<?php echo $Language->getText('bookmark_add', 'bkm_url'); ?>:<br>
<input type="text" name="bookmark_url" size="60" value="<?php echo $purifier->purify($bookmark_url); ?>">
<p>
<?php echo $Language->getText('bookmark_add', 'bkm_title'); ?>:<br>
<input type="text" name="bookmark_title" size="60" value="<?php echo $purifier->purify($bookmark_title); ?>">
<p>
<?php echo $csrf_token->fetchHTMLInput(); ?>
<input type="submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
</form>
<?php

print "<P><A HREF=\"/my/\">[" . $Language->getText('global', 'back_home') . "]</A>";

$HTML->footer([]);
